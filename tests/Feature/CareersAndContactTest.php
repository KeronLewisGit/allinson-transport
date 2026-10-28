<?php

namespace Tests\Feature;

use App\Enums\ApplicantDocumentKind;
use App\Enums\ApplicationStatus;
use App\Enums\JobRole;
use App\Models\Enquiry;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\WarehousingEnquiry;
use App\Notifications\NewSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CareersAndContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');
    }

    /** @return array<string, mixed> */
    private function driver(array $overrides = []): array
    {
        return array_merge([
            'role' => 'ce_driver', 'depot' => 'darlington', 'first_name' => 'Craig', 'last_name' => 'Atkinson', 'phone' => '07700 900123',
            'email' => 'craig@example.com', 'experience' => '5', 'licence_categories' => ['B', 'C', 'CE'],
            'cpc_expires_on' => today()->addYears(2)->format('Y-m-d'), 'tacho_card_expires_on' => today()->addYears(3)->format('Y-m-d'),
            'licence_points' => 0, 'right_to_work' => '1', 'consent' => '1',
        ], $overrides);
    }

    public function test_a_driver_applies_online_with_licence_and_card_dates(): void
    {
        config(['allinson.notify.recruitment' => 'hr@example.com']);
        $this->post('/careers/apply', $this->driver())->assertRedirect('/thanks');
        $a = JobApplication::sole();
        $this->assertSame(sprintf('JA-%s-%05d', now()->format('y'), $a->id), $a->reference);
        $this->assertSame(JobRole::CeDriver, $a->role);
        $this->assertSame(['B', 'C', 'CE'], $a->licence_categories);
        $this->assertSame('C+E, C, B', $a->licenceList());
        $this->assertNotNull($a->cpc_expires_on);
        $this->assertNotNull($a->consented_at);
        $this->assertNotNull($a->public_id);
        Notification::assertSentOnDemand(NewSubmission::class, fn ($n, $c, $to) => $to->routes['mail'] === ['hr@example.com']);
    }

    public function test_drivers_must_declare_cards_categories_and_endorsements(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class); // many tries in a row; the limit has its own test
        $this->post('/careers/apply', $this->driver(['cpc_expires_on' => '', 'tacho_card_expires_on' => '', 'licence_points' => '']))
            ->assertSessionHasErrors(['cpc_expires_on', 'tacho_card_expires_on', 'licence_points']);
        $this->post('/careers/apply', $this->driver(['licence_categories' => ['B']]))->assertSessionHasErrors('licence_categories');
        $this->post('/careers/apply', $this->driver(['licence_points' => 3]))->assertSessionHasErrors('endorsements');
        $this->post('/careers/apply', $this->driver(['role' => 'hiab_driver']))->assertSessionHasErrors('hiab_qualification');
        $this->post('/careers/apply', $this->driver(['right_to_work' => '0']))->assertSessionHasErrors('right_to_work');
        $this->post('/careers/apply', $this->driver(['depot' => 'narnia']))->assertSessionHasErrors('depot');
        $this->assertSame(0, JobApplication::count());
    }

    public function test_office_roles_need_a_cv_and_skip_the_card_questions(): void
    {
        $office = ['role' => 'transport_office', 'licence_categories' => null, 'cpc_expires_on' => '2030-01-01', 'tacho_card_expires_on' => null, 'licence_points' => null];
        $this->post('/careers/apply', $this->driver($office))->assertSessionHasErrors('cv');
        $this->post('/careers/apply', $this->driver($office + ['cv' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf')]))->assertRedirect('/thanks');
        $a = JobApplication::sole();
        $this->assertNull($a->cpc_expires_on, 'office applicants are not asked about driver cards');
        $this->assertStringStartsWith('cvs/', (string) $a->cv_path);
        Storage::disk('local')->assertExists((string) $a->cv_path);
    }

    public function test_applying_for_a_vacancy_takes_its_role_and_depot(): void
    {
        $job = JobPosting::factory()->create(['role' => JobRole::Warehouse, 'depot' => 'liverpool']);
        $this->post('/careers/apply', $this->driver(['job_posting_id' => $job->id, 'role' => 'ce_driver', 'depot' => 'darlington', 'licence_categories' => ['B'], 'cpc_expires_on' => '', 'tacho_card_expires_on' => '', 'licence_points' => '']))->assertRedirect('/thanks');
        $a = JobApplication::sole();
        $this->assertSame(JobRole::Warehouse, $a->role);
        $this->assertSame('liverpool', $a->depot);
        $closed = JobPosting::factory()->create(['closes_on' => today()->subDay()]);
        $this->post('/careers/apply', $this->driver(['job_posting_id' => $closed->id]))->assertSessionHasErrors('job_posting_id');
    }

    public function test_the_documents_link_is_signed_private_and_supersedes_uploads(): void
    {
        $a = JobApplication::factory()->create(['status' => ApplicationStatus::Checks, 'documents_requested_at' => now()]);
        $url = $a->documentsUrl();
        $this->get($url)->assertOk()->assertSee('Driver CPC')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get(route('careers.documents', ['application' => $a->public_id]))->assertForbidden();
        $this->get(route('careers.documents', ['application' => $a->id]))->assertForbidden();

        $this->post($url, ['docs' => ['cpc_card' => UploadedFile::fake()->create('cpc.pdf', 30, 'application/pdf')]])->assertRedirect();
        $this->post($url, ['docs' => ['cpc_card' => UploadedFile::fake()->create('cpc-new.pdf', 30, 'application/pdf')]])->assertRedirect();
        $this->assertSame(2, $a->documents()->count());
        $this->assertSame(1, $a->currentDocuments()->count());
        $this->assertSame(ApplicantDocumentKind::CpcCard, $a->currentDocuments()->sole()->kind);
        $this->assertStringStartsWith('applicants/'.$a->id.'/', $a->currentDocuments()->sole()->path);

        $this->post($url, ['docs' => ['cpc_card' => UploadedFile::fake()->create('virus.exe', 5)]])->assertSessionHasErrors('docs.cpc_card');

        // Closed applications, or ones HR never asked, cannot upload.
        $a->claimStatus(ApplicationStatus::Withdrawn);
        $this->get($url)->assertForbidden();
        $b = JobApplication::factory()->create();
        $this->get($b->documentsUrl())->assertForbidden();
    }

    public function test_warehousing_enquiry(): void
    {
        config(['allinson.notify.warehousing' => 'warehouse@example.com']);
        $data = ['company' => 'Sample Retail', 'contact_name' => 'Leanne Bell', 'email' => 'leanne@example.com', 'phone' => '0191 496 0000',
            'pallet_spaces' => 120, 'duration' => '3_6m', 'goods_type' => 'retail', 'goods_description' => 'Boxed homeware', 'handling' => ['storage', 'pick_pack']];
        $this->post('/warehousing-enquiry', array_merge($data, ['pallet_spaces' => 0, 'handling' => ['teleport']]))->assertSessionHasErrors(['pallet_spaces', 'handling.0']);
        $this->post('/warehousing-enquiry', $data)->assertRedirect('/thanks');
        $e = WarehousingEnquiry::sole();
        $this->assertSame(sprintf('WH-%s-%05d', now()->format('y'), $e->id), $e->reference);
        $this->assertSame(['storage', 'pick_pack'], $e->handling);
        Notification::assertSentOnDemand(NewSubmission::class, fn ($n, $c, $to) => $to->routes['mail'] === ['warehouse@example.com']);
    }

    public function test_contact_form_and_benchmark_preselect(): void
    {
        $this->get('/contact?topic=benchmark')->assertOk()->assertSee('value="benchmark" checked', false);
        $this->post('/contact', ['topic' => 'benchmark', 'name' => 'Lee Hall', 'email' => 'lee@example.com', 'message' => 'Forty pallets a week to the Midlands.'])->assertRedirect('/thanks');
        $this->assertSame('lee@example.com', Enquiry::sole()->email);
        $this->post('/contact', ['topic' => 'benchmark', 'name' => 'Lee', 'email' => 'lee@example.com', 'message' => 'hi'])->assertSessionHasErrors('message');
    }
}
