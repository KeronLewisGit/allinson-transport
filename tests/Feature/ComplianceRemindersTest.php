<?php

namespace Tests\Feature;

use App\Enums\JobRole;
use App\Models\StaffMember;
use App\Notifications\ComplianceDigest;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** The daily digest that tells HR about Driver CPC, tacho card and licence expiry. */
class ComplianceRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config(['allinson.notify.compliance' => 'hr@example.com']);
    }

    public function test_hr_gets_one_digest_at_each_threshold_and_never_twice(): void
    {
        $soon = StaffMember::factory()->create(['name' => 'Due Driver', 'cpc_expires_on' => today()->addDays(30)]);
        $gone = StaffMember::factory()->create(['name' => 'Expired Driver', 'tacho_card_expires_on' => today()->subDays(2)]);
        StaffMember::factory()->create(['name' => 'Fine Driver']);
        StaffMember::factory()->create(['name' => 'Left Driver', 'active' => false, 'cpc_expires_on' => today()->addDays(10)]);
        StaffMember::factory()->create(['name' => 'Warehouse', 'role' => JobRole::Warehouse, 'cpc_expires_on' => today()->addDays(10)]);

        $this->artisan('app:compliance-reminders')->assertSuccessful();
        Notification::assertSentOnDemandTimes(ComplianceDigest::class, 1);
        Notification::assertSentOnDemand(ComplianceDigest::class, function (ComplianceDigest $n, $c, $to) {
            $names = array_column($n->rows, 'name');
            sort($names);

            return $to->routes['mail'] === ['hr@example.com'] && $names === ['Due Driver', 'Expired Driver'];
        });
        $this->assertSame(30, $soon->refresh()->cpc_reminded_at_days);
        $this->assertSame(0, $gone->refresh()->tacho_reminded_at_days);
        $this->assertStringContainsString('reminder sent to HR', (string) $soon->notes()->value('body'));

        // The next day nothing new has crossed a threshold: no email.
        $this->travel(1)->days();
        $this->artisan('app:compliance-reminders')->assertSuccessful();
        Notification::assertSentOnDemandTimes(ComplianceDigest::class, 1);

        // Sixteen days later the CPC crosses the 14-day threshold: one more digest.
        $this->travel(16)->days();
        $this->artisan('app:compliance-reminders')->assertSuccessful();
        Notification::assertSentOnDemandTimes(ComplianceDigest::class, 2);
        $this->assertSame(14, $soon->refresh()->cpc_reminded_at_days);
    }

    public function test_renewing_a_card_rearms_its_reminders(): void
    {
        $s = StaffMember::factory()->create(['cpc_expires_on' => today()->addDays(10)]);
        $this->artisan('app:compliance-reminders');
        $this->assertSame(14, $s->refresh()->cpc_reminded_at_days);
        $s->update(['cpc_expires_on' => today()->addYears(5)]);
        $this->assertNull($s->refresh()->cpc_reminded_at_days);
    }

    public function test_dry_run_records_nothing(): void
    {
        $s = StaffMember::factory()->create(['cpc_expires_on' => today()->addDays(5)]);
        $this->artisan('app:compliance-reminders --dry-run')->expectsOutputToContain('1 reminder(s).')->assertSuccessful();
        Notification::assertNothingSent();
        $this->assertNull($s->refresh()->cpc_reminded_at_days);
    }

    public function test_it_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($e) => str_contains((string) $e->command, 'app:compliance-reminders'));
        $this->assertCount(1, $events);
        $this->assertSame('0 7 * * *', $events->first()->expression);
    }
}
