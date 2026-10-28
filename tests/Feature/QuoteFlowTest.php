<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Enums\Role;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\NewSubmission;
use App\Notifications\QuoteAnswered;
use App\Notifications\QuoteSent;
use App\Notifications\SubmissionReceived;
use App\Support\Depots;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class QuoteFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');
        config(['allinson.notify.quotes' => 'traffic@example.com']);
    }

    /** @return array<string, mixed> */
    private function quote(array $overrides = []): array
    {
        return array_merge([
            'service' => 'pallets', 'contact_name' => 'Gary Robson', 'company' => 'Sample Polymers', 'email' => 'Gary@Example.com', 'phone' => '01325 466000',
            'collection_postcode' => 'ts18 3ts', 'collection_date' => today()->addDays(2)->format('Y-m-d'),
            'delivery_postcode' => 'gl2 5bx', 'goods' => 'Bagged polymer', 'pallets' => 6, 'weight_kg' => 5400,
        ], $overrides);
    }

    public function test_a_quote_request_is_stored_routed_and_acknowledged(): void
    {
        $this->post('/quote', $this->quote())->assertRedirect('/thanks');
        $q = Quote::sole();
        $this->assertSame(sprintf('QR-%s-%05d', now()->format('y'), $q->id), $q->reference);
        $this->assertSame('TS18 3TS', $q->collection_postcode);
        $this->assertSame('GL2 5BX', $q->delivery_postcode);
        $this->assertSame('gary@example.com', $q->email);
        $this->assertSame('darlington', $q->depot, 'TS postcodes are priced at Darlington');
        $this->assertSame(QuoteStatus::New, $q->status);
        Notification::assertSentOnDemand(NewSubmission::class, fn ($n, $c, $to) => $to->routes['mail'] === ['traffic@example.com']);
        Notification::assertSentOnDemand(SubmissionReceived::class, fn ($n, $c, $to) => $to->routes['mail'] === ['gary@example.com']);
        $this->get('/thanks')->assertOk()->assertSee($q->reference);
    }

    public function test_requests_go_to_the_depot_that_covers_the_collection(): void
    {
        $this->assertSame('yorkshire', Depots::forPostcode('LS10 1RJ'));
        $this->assertSame('liverpool', Depots::forPostcode('L20 8DJ'));
        $this->assertSame('gloucestershire', Depots::forPostcode('GL51 9NJ'));
        $this->assertSame('cambridgeshire', Depots::forPostcode('PE2 6TG'));
        $this->assertSame('darlington', Depots::forPostcode('ZZ1 1ZZ'), 'unknown areas fall back to head office');
        $this->post('/quote', $this->quote(['collection_postcode' => 'L20 8DJ']))->assertRedirect('/thanks');
        $this->assertSame('liverpool', Quote::sole()->depot);
    }

    public function test_validation_rules_follow_the_service(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class); // many tries in a row; the limit has its own test
        $this->post('/quote', $this->quote(['collection_postcode' => 'DL3', 'pallets' => null]))->assertSessionHasErrors(['collection_postcode', 'pallets']);
        $this->post('/quote', $this->quote(['pallets' => 30]))->assertSessionHasErrors('pallets');
        $this->post('/quote', $this->quote(['service' => 'full_load', 'pallets' => 26]))->assertSessionDoesntHaveErrors();
        $this->post('/quote', $this->quote(['service' => 'hiab', 'pallets' => null]))->assertSessionHasErrors(['length_m', 'width_m', 'height_m']);
        $this->post('/quote', $this->quote(['adr' => '1']))->assertSessionHasErrors('adr_details');
        $this->post('/quote', $this->quote(['collection_date' => today()->subDay()->format('Y-m-d')]))->assertSessionHasErrors('collection_date');
        $this->post('/quote', $this->quote(['phone' => '12']))->assertSessionHasErrors('phone');
        $this->post('/quote', $this->quote(['service' => ['x'], 'goods' => ['y']]))->assertSessionHasErrors(['service', 'goods']);
        $this->assertSame(1, Quote::count());
    }

    public function test_attachments_are_stored_privately(): void
    {
        $this->post('/quote', $this->quote(['files' => [UploadedFile::fake()->create('packing-list.pdf', 40, 'application/pdf')]]))->assertRedirect('/thanks');
        $q = Quote::sole();
        $file = $q->files()->sole();
        $this->assertStringStartsWith('quotes/'.$q->id.'/', $file->path);
        Storage::disk('local')->assertExists($file->path);
        $this->post('/quote', $this->quote(['files' => [UploadedFile::fake()->create('run.exe', 10)]]))->assertSessionHasErrors('files.0');
    }

    public function test_the_form_is_rate_limited(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $last = $this->post('/quote', $this->quote(['goods' => '']));
        }
        $last->assertStatus(429);
    }

    public function test_bots_are_thanked_but_nothing_is_stored(): void
    {
        $this->post('/quote', $this->quote(['website' => 'http://spam']))->assertRedirect('/thanks');
        $this->assertSame(0, Quote::count());
    }

    public function test_traffic_prices_a_quote_and_the_customer_accepts_online(): void
    {
        $traffic = User::factory()->create(['role' => Role::Traffic, 'depot' => 'darlington']);
        $q = Quote::factory()->create();
        $this->actingAs($traffic)->post(route('admin.quotes.price', $q), ['price' => '640', 'valid_until' => today()->addDays(14)->format('Y-m-d'), 'price_notes' => 'Curtainsider'])->assertRedirect();
        $q->refresh();
        $this->assertSame(QuoteStatus::Quoted, $q->status);
        $this->assertEquals(640, $q->price);
        $this->assertSame($traffic->id, $q->assigned_to);
        $link = null;
        Notification::assertSentOnDemand(QuoteSent::class, function (QuoteSent $n, $c, $to) use (&$link, $q) {
            $link = $n->url;

            return $to->routes['mail'] === [$q->email];
        });
        $this->assertNotNull($link);

        auth()->logout();
        $this->get($link)->assertOk()->assertSee('£640')->assertSee('Accept and book')->assertHeader('Referrer-Policy', 'no-referrer');
        $this->get(route('quotes.response', ['quote' => $q->public_id]))->assertForbidden(); // unsigned
        $this->get(route('quotes.response', ['quote' => $q->id]))->assertForbidden();

        $this->post($link, ['decision' => 'accept', 'note' => 'Gate code 1234'])->assertRedirect();
        $this->assertSame(QuoteStatus::Accepted, $q->refresh()->status);
        $this->assertSame('Gate code 1234', $q->customer_note);
        Notification::assertSentOnDemand(QuoteAnswered::class);
        // A second answer changes nothing.
        $this->post($link, ['decision' => 'decline'])->assertSessionHasErrors('decision');
        $this->assertSame(QuoteStatus::Accepted, $q->refresh()->status);
    }

    public function test_an_expired_quote_cannot_be_accepted(): void
    {
        $q = Quote::factory()->quoted()->create(['valid_until' => today()->subDay()]);
        $link = URL::temporarySignedRoute('quotes.respond', now()->addDay(), ['quote' => $q->public_id]);
        $this->post($link, ['decision' => 'accept'])->assertSessionHasErrors('decision');
        $this->assertSame(QuoteStatus::Quoted, $q->refresh()->status);
    }

    public function test_a_status_claim_sent_twice_at_once_is_applied_once(): void
    {
        $q = Quote::factory()->quoted()->create();
        $a = Quote::findOrFail($q->id);
        $b = Quote::findOrFail($q->id);
        $this->assertTrue($a->claimStatus(QuoteStatus::Accepted));
        $this->assertFalse($b->claimStatus(QuoteStatus::NotTaken));
        $this->assertSame(QuoteStatus::Accepted, $q->refresh()->status);
    }
}
