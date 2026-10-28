<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\AssessmentResult;
use App\Enums\QuoteStatus;
use App\Enums\ReferenceStatus;
use App\Enums\Role;
use App\Models\Enquiry;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Quote;
use App\Models\StaffMember;
use App\Models\User;
use App\Models\WarehousingEnquiry;
use App\Notifications\DocumentsRequested;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/quotes')->assertRedirect('/login');
        $this->get('/admin/staff')->assertRedirect('/login');
    }

    public function test_login_and_inactive_accounts(): void
    {
        $user = User::factory()->create(['email' => 'traffic@example.com']);
        $this->post('/login', ['email' => 'TRAFFIC@example.com', 'password' => 'password'])->assertRedirect('/admin');
        $user->update(['active' => false]);
        $this->app['auth']->forgetGuards();
        $this->get('/admin')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', ['email' => 'traffic@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
    }

    /** @return array<string, array{Role, array<string, int>}> */
    public static function matrix(): array
    {
        $all = ['/admin/quotes', '/admin/enquiries', '/admin/warehousing', '/admin/applications', '/admin/jobs', '/admin/staff', '/admin/users'];
        $allow = fn (array $ok) => array_combine($all, array_map(fn ($u) => in_array($u, $ok, true) ? 200 : 403, $all));

        return [
            'admin' => [Role::Admin, $allow($all)],
            'traffic' => [Role::Traffic, $allow(['/admin/quotes', '/admin/enquiries'])],
            'warehouse' => [Role::Warehouse, $allow(['/admin/warehousing'])],
            'hr' => [Role::Hr, $allow(['/admin/applications', '/admin/jobs', '/admin/staff'])],
        ];
    }

    /** @param array<string, int> $expect */
    #[DataProvider('matrix')]
    public function test_each_role_sees_only_its_areas(Role $role, array $expect): void
    {
        $this->actingAs($this->staff($role));
        $this->get('/admin')->assertOk();
        foreach ($expect as $url => $status) {
            $this->get($url)->assertStatus($status);
        }
    }

    public function test_record_pages_and_files_follow_the_same_boundaries(): void
    {
        Storage::fake('local');
        $quote = Quote::factory()->create();
        Storage::disk('local')->put('quotes/'.$quote->id.'/a.pdf', '%PDF');
        $file = $quote->files()->create(['path' => 'quotes/'.$quote->id.'/a.pdf', 'original_name' => 'a.pdf', 'mime' => 'application/pdf', 'size' => 4]);
        $app = JobApplication::factory()->create(['cv_path' => 'cvs/x.pdf', 'cv_name' => 'x.pdf']);
        Storage::disk('local')->put('cvs/x.pdf', '%PDF');
        $wh = WarehousingEnquiry::factory()->create();
        $person = StaffMember::factory()->create();

        $this->actingAs($this->staff(Role::Traffic));
        $this->get(route('admin.quotes.file', [$quote, $file]))->assertOk();
        $this->get(route('admin.applications.cv', $app))->assertForbidden();
        $this->get(route('admin.warehousing.show', $wh))->assertForbidden();
        $this->get(route('admin.staff.show', $person))->assertForbidden();

        $this->actingAs($this->staff(Role::Hr));
        $this->get(route('admin.quotes.show', $quote))->assertForbidden();
        $this->get(route('admin.quotes.file', [$quote, $file]))->assertForbidden();
        $this->get(route('admin.applications.cv', $app))->assertOk();
        $this->get(route('admin.staff.show', $person))->assertOk();

        $this->actingAs($this->staff(Role::Warehouse));
        $this->get(route('admin.warehousing.show', $wh))->assertOk();
        $this->patch(route('admin.quotes.update', $quote), ['status' => 'declined'])->assertForbidden();
        $this->assertSame(QuoteStatus::New, $quote->refresh()->status);
        $this->assertTrue($app->notes()->where('body', 'Opened the CV.')->exists(), 'CV downloads are logged');
    }

    public function test_a_file_from_another_quote_is_refused(): void
    {
        $a = Quote::factory()->create();
        $b = Quote::factory()->create();
        $file = $b->files()->create(['path' => 'quotes/x.pdf', 'original_name' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 4]);
        $this->actingAs($this->staff(Role::Admin))->get(route('admin.quotes.file', [$a, $file]))->assertForbidden();
    }

    public function test_dashboard_leads_with_the_number_for_the_role(): void
    {
        Quote::factory()->count(2)->create(['collection_postcode' => 'DL1 4PZ']);
        Quote::factory()->create(['collection_postcode' => 'LS10 1RJ']);
        Quote::factory()->quoted()->create();
        $this->actingAs($this->staff(Role::Traffic, 'darlington'))->get('/admin')->assertSee('quote requests are waiting for a price at Darlington')->assertSee('>02<', false);
        $this->actingAs($this->staff(Role::Admin))->get('/admin')->assertSee('>03<', false);

        WarehousingEnquiry::factory()->create();
        $this->actingAs($this->staff(Role::Warehouse))->get('/admin')->assertSee('warehousing enquiry needs a call back')->assertSee('>01<', false);

        StaffMember::factory()->create(['cpc_expires_on' => today()->addDays(20)]);
        StaffMember::factory()->create(['tacho_card_expires_on' => today()->subDay()]);
        StaffMember::factory()->create();
        $this->actingAs($this->staff(Role::Hr))->get('/admin')->assertSee('drivers have a CPC, tacho card or licence expired or expiring within 60 days')->assertSee('>02<', false)->assertSee('1 already expired');
    }

    public function test_traffic_starts_on_its_own_depot_and_can_move_a_request(): void
    {
        $dl = Quote::factory()->create(['collection_postcode' => 'DL1 4PZ', 'goods' => 'Darlington load']);
        $ls = Quote::factory()->create(['collection_postcode' => 'LS10 1RJ', 'goods' => 'Leeds load']);
        $user = $this->staff(Role::Traffic, 'yorkshire');
        $this->actingAs($user)->get('/admin/quotes')->assertSee($ls->reference)->assertDontSee($dl->reference);
        $this->get('/admin/quotes?depot=')->assertSee($dl->reference);
        $this->patch(route('admin.quotes.update', $dl), ['depot' => 'yorkshire', 'assigned_to' => $user->id])->assertRedirect();
        $this->assertSame('yorkshire', $dl->refresh()->depot);
        $hr = $this->staff(Role::Hr);
        $this->patch(route('admin.quotes.update', $dl), ['assigned_to' => $hr->id])->assertSessionHasErrors('assigned_to');
        $this->assertSame($user->id, $dl->refresh()->assigned_to);
    }

    public function test_hr_runs_an_applicant_through_checks_documents_and_hire(): void
    {
        Notification::fake();
        $hr = $this->staff(Role::Hr);
        $a = JobApplication::factory()->create();
        $this->actingAs($hr);

        $this->patch(route('admin.applications.update', $a), ['status' => 'hired'])->assertSessionHasErrors('status');
        $this->patch(route('admin.applications.update', $a), ['status' => 'checks'])->assertRedirect();
        $this->post(route('admin.applications.request-documents', $a))->assertRedirect();
        Notification::assertSentOnDemand(DocumentsRequested::class, fn ($n, $c, $to) => $to->routes['mail'] === [$a->email]);
        $this->assertNotNull($a->refresh()->documents_requested_at);

        $this->post(route('admin.applications.hire', $a))->assertSessionHasErrors('hire');
        $this->patch(route('admin.applications.checks', $a), ['rtw_method' => 'share_code', 'references_status' => 'received', 'assessment_at' => today()->addDay()->format('Y-m-d').'T09:30'])->assertRedirect();
        $a->refresh();
        $this->assertSame($hr->id, $a->rtw_checked_by);
        $this->assertSame(ReferenceStatus::Received, $a->references_status);
        $this->assertSame('09:30', $a->assessment_at?->format('H:i'));
        $this->patch(route('admin.applications.checks', $a), ['references_status' => 'received', 'assessment_at' => $a->assessment_at->format('Y-m-d\TH:i'), 'assessment_result' => 'passed'])->assertRedirect();
        $this->assertSame(AssessmentResult::Passed, $a->refresh()->assessment_result);

        $this->post(route('admin.applications.hire', $a), ['employee_number' => 'S0100', 'licence_expires_on' => today()->addYears(4)->format('Y-m-d')])->assertRedirect();
        $a->refresh();
        $this->assertSame(ApplicationStatus::Hired, $a->status);
        $staff = StaffMember::sole();
        $this->assertSame($a->id, $staff->job_application_id);
        $this->assertSame($a->cpc_expires_on?->toDateString(), $staff->cpc_expires_on?->toDateString());
        $this->assertSame('S0100', $staff->employee_number);
        // Twice at once: still one staff record.
        $this->post(route('admin.applications.hire', $a))->assertSessionHasErrors('hire');
        $this->assertSame(1, StaffMember::count());
    }

    public function test_staff_compliance_crud(): void
    {
        $this->actingAs($this->staff(Role::Hr));
        $this->post(route('admin.staff.store'), ['name' => 'Wayne Shaw', 'role' => 'ce_driver', 'depot' => 'liverpool', 'licence_categories' => ['C', 'CE'],
            'cpc_expires_on' => today()->addDays(20)->format('Y-m-d'), 'active' => '1'])->assertRedirect();
        $s = StaffMember::sole();
        $this->assertSame('due', $s->state('cpc'));
        $this->get('/admin/staff?due=1')->assertSee(route('admin.staff.show', $s));
        $this->put(route('admin.staff.update', $s), ['name' => 'Wayne Shaw', 'role' => 'ce_driver', 'depot' => 'liverpool', 'cpc_expires_on' => today()->addYears(5)->format('Y-m-d'), 'active' => '1'])->assertRedirect();
        $this->get('/admin/staff?due=1')->assertDontSee(route('admin.staff.show', $s));
        $this->post(route('admin.staff.store'), ['name' => 'x', 'role' => 'pilot', 'depot' => 'mars', 'cards_checked_on' => today()->addDay()->format('Y-m-d')])->assertSessionHasErrors(['role', 'depot', 'cards_checked_on']);
    }

    public function test_csv_exports_are_formula_safe(): void
    {
        Quote::factory()->create(['company' => '=HYPERLINK("x")']);
        $csv = $this->actingAs($this->staff(Role::Traffic))->get(route('admin.quotes.export', ['depot' => '']))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        WarehousingEnquiry::factory()->create(['company' => '+cmd']);
        $this->assertStringContainsString("'+cmd", $this->actingAs($this->staff(Role::Warehouse))->get(route('admin.warehousing.export'))->streamedContent());
        $this->actingAs($this->staff(Role::Hr))->get(route('admin.applications.export'))->assertOk();
        $this->get(route('admin.staff.export'))->assertOk();
    }

    public function test_every_admin_screen_renders_with_data(): void
    {
        $this->seed(DemoSeeder::class);
        foreach (['admin', 'traffic', 'warehouse', 'hr'] as $who) {
            $this->actingAs(User::where('email', $who.'@'.DemoSeeder::DOMAIN)->firstOrFail())->get('/admin')->assertOk();
        }
        $this->actingAs(User::where('email', 'admin@'.DemoSeeder::DOMAIN)->firstOrFail());
        $urls = ['/admin', '/admin/quotes', '/admin/quotes?status=open', '/admin/quotes?adr=1&depot=', '/admin/warehousing', '/admin/warehousing?waiting=1', '/admin/applications', '/admin/applications?waiting=1',
            '/admin/jobs', '/admin/jobs/create', '/admin/staff', '/admin/staff?due=1', '/admin/staff?active=0', '/admin/staff/create', '/admin/enquiries', '/admin/users'];
        foreach (Quote::all() as $q) {
            $urls[] = route('admin.quotes.show', $q);
        }
        foreach (JobApplication::all() as $a) {
            $urls[] = route('admin.applications.show', $a);
        }
        $urls[] = route('admin.warehousing.show', WarehousingEnquiry::firstOrFail());
        $urls[] = route('admin.jobs.edit', JobPosting::firstOrFail());
        $urls[] = route('admin.staff.show', StaffMember::firstOrFail());
        $urls[] = route('admin.staff.edit', StaffMember::firstOrFail());
        $urls[] = route('admin.enquiries.show', Enquiry::firstOrFail());
        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
        // The quote and documents links customers and applicants receive also render.
        $this->get(Quote::whereNotNull('price')->firstOrFail()->responseUrl())->assertOk();
        $this->get(JobApplication::whereNotNull('documents_requested_at')->where('status', 'checks')->firstOrFail()->documentsUrl())->assertOk();
    }

    public function test_vacancies_crud(): void
    {
        $this->actingAs($this->staff(Role::Hr));
        $this->post(route('admin.jobs.store'), ['title' => 'Hiab Driver', 'role' => 'hiab_driver', 'depot' => 'darlington', 'employment_type' => 'full_time', 'status' => 'open', 'openings' => 2, 'summary' => 'Crane deliveries.'])
            ->assertRedirect(route('admin.jobs.index'));
        $job = JobPosting::sole();
        $this->assertSame('hiab-driver', $job->slug);
        $this->get('/careers/hiab-driver')->assertOk();
        $this->put(route('admin.jobs.update', $job), ['title' => 'Hiab Driver', 'role' => 'hiab_driver', 'depot' => 'darlington', 'employment_type' => 'full_time', 'status' => 'closed', 'openings' => 2, 'summary' => 'Crane deliveries.'])->assertRedirect();
        $this->get('/careers/hiab-driver')->assertNotFound();
    }

    public function test_staff_accounts_are_admin_only_and_admins_keep_their_access(): void
    {
        $admin = $this->staff(Role::Admin);
        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Traffic Person', 'email' => 'T@x.com', 'role' => 'traffic', 'depot' => 'liverpool', 'password' => 'long-password'])->assertRedirect();
        $u = User::where('email', 't@x.com')->firstOrFail();
        $this->assertSame(Role::Traffic, $u->role);
        $this->assertSame('liverpool', $u->depot);
        $this->patch(route('admin.users.update', $admin), ['role' => 'hr', 'active' => '1'])->assertSessionHasErrors('role');
        $this->actingAs($this->staff(Role::Hr))->post(route('admin.users.store'), [])->assertForbidden();
    }

    public function test_admin_forms_survive_junk_array_input(): void
    {
        $this->actingAs($this->staff(Role::Admin));
        $this->get('/admin/quotes?status[]=x&depot[]=y&q[]=z')->assertOk();
        $this->get('/admin/applications?role[]=x&job[]=1')->assertOk();
        $this->get('/admin/staff?depot[]=x&active[]=1')->assertOk();
    }

    public function test_enquiry_status(): void
    {
        $e = Enquiry::create(['topic' => 'general', 'name' => 'A', 'email' => 'a@example.com', 'message' => 'Hello there (sample)']);
        $this->actingAs($this->staff(Role::Traffic))->patch(route('admin.enquiries.update', $e), ['status' => 'answered'])->assertRedirect();
        $this->assertSame('answered', $e->refresh()->status->value);
        $this->actingAs($this->staff(Role::Warehouse))->get(route('admin.enquiries.show', $e))->assertForbidden();
    }
}
