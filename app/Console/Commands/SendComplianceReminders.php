<?php

namespace App\Console\Commands;

use App\Enums\Area;
use App\Models\StaffMember;
use App\Notifications\ComplianceDigest;
use App\Support\Notify;
use Illuminate\Console\Command;

/**
 * Daily: one email to HR listing every active driver whose Driver CPC, digital tachograph
 * card or licence photocard has crossed a reminder threshold (StaffMember::REMINDER_DAYS)
 * since the last run. Each threshold is claimed with a conditional UPDATE before it is sent,
 * so overlapping runs never report the same date twice; a new date starts the reminders again.
 */
class SendComplianceReminders extends Command
{
    protected $signature = 'app:compliance-reminders {--dry-run : List what would be sent without emailing or recording it}';

    protected $description = 'Email HR about Driver CPC, tacho card and licence expiries';

    public function handle(): int
    {
        $limit = today()->addDays(max(StaffMember::REMINDER_DAYS));
        $staff = StaffMember::where('active', true)
            ->where(fn ($q) => $q->whereDate('cpc_expires_on', '<=', $limit)->orWhereDate('tacho_card_expires_on', '<=', $limit)->orWhereDate('licence_expires_on', '<=', $limit))
            ->orderBy('name')->get();

        $rows = [];
        foreach ($staff as $member) {
            foreach ($member->trackedCards() as $card) {
                [$dateColumn, $remindedColumn, $label] = StaffMember::CARDS[$card];
                $days = $member->daysLeft($card);
                if ($days === null) {
                    continue;
                }
                $threshold = StaffMember::thresholdFor($days);
                $last = $member->getAttribute($remindedColumn);
                if ($threshold === null || ($last !== null && $last <= $threshold)) {
                    continue; // not at a threshold yet, or already told at this one (or a nearer one)
                }
                if (! $this->option('dry-run')) {
                    $claimed = StaffMember::whereKey($member->id)
                        ->where(fn ($q) => $q->whereNull($remindedColumn)->orWhere($remindedColumn, '>', $threshold))
                        ->update([$remindedColumn => $threshold]) === 1;
                    if (! $claimed) {
                        continue;
                    }
                    $member->log("{$label} reminder sent to HR ({$this->describe($days)}).");
                }
                $rows[] = [
                    'name' => $member->name, 'depot' => $member->depotName(), 'card' => $label,
                    'date' => $member->expiry($card)?->format('j M Y') ?? '', 'days' => $days, 'url' => route('admin.staff.show', $member),
                ];
                $this->line("{$member->name}: {$label} ".$this->describe($days));
            }
        }

        if ($rows !== [] && ! $this->option('dry-run')) {
            usort($rows, fn (array $a, array $b) => $a['days'] <=> $b['days']);
            Notify::officeNow(new ComplianceDigest($rows), Area::Compliance);
        }
        $this->info(count($rows).' reminder(s).');

        return self::SUCCESS;
    }

    private function describe(int $days): string
    {
        return match (true) {
            $days < 0 => 'expired '.abs($days).' days ago',
            $days === 0 => 'expires today',
            default => "expires in {$days} days",
        };
    }
}
