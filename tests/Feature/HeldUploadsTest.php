<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\Quote;
use App\Support\HeldUploads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** A form that comes back with an error keeps the files that were fine; nobody attaches them twice. */
class HeldUploadsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
    }

    /** A small file with real PDF bytes, so its type is recognised from its content. */
    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n");
    }

    /** @return array<string, mixed> */
    private function quote(array $overrides = []): array
    {
        return array_merge([
            'service' => 'hiab', 'contact_name' => 'Gary Robson', 'email' => 'g@example.com', 'phone' => '01325 466000',
            'collection_postcode' => 'DL5 6XF', 'collection_date' => today()->addDays(3)->format('Y-m-d'), 'delivery_postcode' => 'YO26 6RU',
            'goods' => 'Steel sections', 'weight_kg' => 9000, 'length_m' => 12, 'width_m' => 2.4, 'height_m' => 1.2,
        ], $overrides);
    }

    public function test_quote_attachments_survive_a_validation_error(): void
    {
        $files = [$this->pdf('drawing.pdf'), $this->pdf('lift-plan.pdf')];
        $this->from('/quote')->post('/quote', $this->quote(['goods' => '', 'files' => $files]))->assertSessionHasErrors('goods');
        $this->assertCount(2, Storage::disk('local')->allFiles(HeldUploads::DIR));
        $this->get('/quote')->assertSee('Attached:')->assertSee('drawing.pdf, lift-plan.pdf');

        $this->post('/quote', $this->quote())->assertRedirect('/thanks');
        $quote = Quote::sole();
        $this->assertSame(['drawing.pdf', 'lift-plan.pdf'], $quote->files()->orderBy('original_name')->pluck('original_name')->all());
        foreach ($quote->files as $f) {
            Storage::disk('local')->assertExists($f->path);
            $this->assertStringStartsWith('quotes/'.$quote->id.'/', $f->path);
        }
        $this->assertSame([], Storage::disk('local')->allFiles(HeldUploads::DIR));
    }

    public function test_a_cv_survives_a_validation_error(): void
    {
        $data = ['role' => 'transport_office', 'depot' => 'yorkshire', 'first_name' => 'Kirsty', 'last_name' => 'Dodds', 'phone' => '07700 900456',
            'email' => 'k@example.com', 'experience' => '2', 'right_to_work' => '1', 'consent' => '1'];
        $this->from('/careers/apply')->post('/careers/apply', array_merge($data, ['first_name' => '', 'cv' => $this->pdf('cv.pdf')]))->assertSessionHasErrors('first_name');
        $this->get('/careers/apply')->assertSee('cv.pdf');
        $this->post('/careers/apply', $data)->assertRedirect('/thanks');
        $this->assertSame('cv.pdf', JobApplication::sole()->cv_name);
        $this->assertSame([], Storage::disk('local')->allFiles(HeldUploads::DIR));
    }

    public function test_held_files_are_pruned_after_a_day(): void
    {
        $this->from('/quote')->post('/quote', $this->quote(['goods' => '', 'files' => [$this->pdf('a.pdf')]]));
        $this->assertCount(1, Storage::disk('local')->allFiles(HeldUploads::DIR));
        $this->travel(HeldUploads::HOURS + 1)->hours();
        $this->artisan('app:prune-held-uploads')->assertSuccessful();
        $this->assertSame([], Storage::disk('local')->allFiles(HeldUploads::DIR));
    }

    public function test_applicant_documents_survive_a_validation_error(): void
    {
        $a = JobApplication::factory()->create(['status' => ApplicationStatus::Checks, 'documents_requested_at' => now()]);
        $url = $a->documentsUrl();
        $this->from($url)->post($url, ['docs' => ['licence_front' => $this->pdf('front.pdf'), 'licence_back' => UploadedFile::fake()->create('back.exe', 3)]])
            ->assertSessionHasErrors('docs.licence_back');
        $this->assertCount(0, $a->documents()->get());
        $this->post($url, ['docs' => ['licence_back' => $this->pdf('back.pdf')]])->assertRedirect();
        $this->assertEqualsCanonicalizing(['licence_front', 'licence_back'], $a->currentDocuments()->get()->map(fn ($d) => $d->kind->value)->all());
    }
}
