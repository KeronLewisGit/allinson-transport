<?php

namespace App\Models;

use App\Enums\JobRole;
use App\Models\Concerns\HasTimeline;
use App\Support\Depots;
use Database\Factories\StaffMemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A driver or member of staff whose driving licence photocard, Driver CPC (DQC) and digital
 * tachograph card expiries HR tracks. app:compliance-reminders emails HR as each date
 * crosses REMINDER_DAYS.
 *
 * @property int $id
 * @property string $name
 * @property JobRole $role
 * @property string $depot
 * @property string|null $employee_number
 * @property string|null $phone
 * @property string|null $email
 * @property list<string>|null $licence_categories
 * @property Carbon|null $licence_expires_on
 * @property Carbon|null $cpc_expires_on
 * @property Carbon|null $tacho_card_expires_on
 * @property int|null $licence_reminded_at_days
 * @property int|null $cpc_reminded_at_days
 * @property int|null $tacho_reminded_at_days
 * @property Carbon|null $cards_checked_on
 * @property bool $active
 * @property int|null $job_application_id
 * @property Carbon $created_at
 * @property-read JobApplication|null $application
 */
class StaffMember extends Model
{
    /** @use HasFactory<StaffMemberFactory> */
    use HasFactory, HasTimeline;

    /**
     * Reminder thresholds, in days before expiry. Driver CPC needs 35 hours of periodic training
     * done before the date, so the first warning is early; a new tacho card takes up to 15
     * working days to arrive from the DVLA.
     */
    public const array REMINDER_DAYS = [90, 60, 30, 14, 0];

    /** The dashboard counts dates expired or inside this many days. */
    public const int WARNING_DAYS = 60;

    /** card key => [date column, reminder column, label] */
    public const array CARDS = [
        'cpc' => ['cpc_expires_on', 'cpc_reminded_at_days', 'Driver CPC (DQC)'],
        'tacho' => ['tacho_card_expires_on', 'tacho_reminded_at_days', 'Tacho driver card'],
        'licence' => ['licence_expires_on', 'licence_reminded_at_days', 'Licence photocard'],
    ];

    protected $fillable = [
        'name', 'role', 'depot', 'employee_number', 'phone', 'email', 'licence_categories', 'licence_expires_on', 'cpc_expires_on',
        'tacho_card_expires_on', 'licence_reminded_at_days', 'cpc_reminded_at_days', 'tacho_reminded_at_days', 'cards_checked_on', 'active', 'job_application_id',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'role' => JobRole::class,
            'licence_categories' => 'array',
            'licence_expires_on' => 'date',
            'cpc_expires_on' => 'date',
            'tacho_card_expires_on' => 'date',
            'licence_reminded_at_days' => 'integer',
            'cpc_reminded_at_days' => 'integer',
            'tacho_reminded_at_days' => 'integer',
            'cards_checked_on' => 'date',
            'active' => 'boolean',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        // A renewed card (a new date) starts its reminders again.
        static::saving(function (self $staff): void {
            foreach (self::CARDS as [$date, $reminded]) {
                if ($staff->isDirty($date) && $staff->exists) {
                    $staff->setAttribute($reminded, null);
                }
            }
        });
    }

    /** @return BelongsTo<JobApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function drives(): bool
    {
        return $this->role->drives();
    }

    public function depotName(): string
    {
        return Depots::name($this->depot);
    }

    /** @return list<string> which cards this person is tracked for */
    public function trackedCards(): array
    {
        return $this->drives() ? ['cpc', 'tacho', 'licence'] : [];
    }

    public function expiry(string $card): ?Carbon
    {
        $value = $this->getAttribute(self::CARDS[$card][0]);

        return $value instanceof Carbon ? $value : null;
    }

    public function daysLeft(string $card): ?int
    {
        $d = $this->expiry($card);

        return $d ? (int) today()->diffInDays($d, false) : null;
    }

    /** "expired", "due" (inside WARNING_DAYS), "valid" or "missing". */
    public function state(string $card): string
    {
        $days = $this->daysLeft($card);

        return match (true) {
            $days === null => 'missing',
            $days < 0 => 'expired',
            $days <= self::WARNING_DAYS => 'due',
            default => 'valid',
        };
    }

    /** Worst state across the tracked cards. */
    public function worstState(): string
    {
        $states = array_map(fn (string $c) => $this->state($c), $this->trackedCards());
        foreach (['expired', 'missing', 'due'] as $s) {
            if (in_array($s, $states, true)) {
                return $s;
            }
        }

        return 'valid';
    }

    /**
     * The earliest tracked expiry: [card, date] or null.
     *
     * @return array{0: string, 1: Carbon}|null
     */
    public function nextExpiry(): ?array
    {
        $next = null;
        foreach ($this->trackedCards() as $card) {
            $d = $this->expiry($card);
            if ($d && ($next === null || $d->lt($next[1]))) {
                $next = [$card, $d];
            }
        }

        return $next;
    }

    /** The reminder threshold a date has reached today (90, 60, 30, 14 or 0), or null if none yet. */
    public static function thresholdFor(int $daysLeft): ?int
    {
        foreach (array_reverse(self::REMINDER_DAYS) as $t) {
            if ($daysLeft <= $t) {
                return $t;
            }
        }

        return null;
    }

    /**
     * Active drivers with a card expired or expiring within WARNING_DAYS.
     *
     * @param  Builder<self>  $query
     */
    public function scopeDue(Builder $query): void
    {
        $limit = today()->addDays(self::WARNING_DAYS);
        $query->where('active', true)->whereIn('role', array_map(fn (JobRole $r) => $r->value, array_filter(JobRole::cases(), fn (JobRole $r) => $r->drives())))
            ->where(fn (Builder $q) => $q->whereDate('cpc_expires_on', '<=', $limit)->orWhereDate('tacho_card_expires_on', '<=', $limit)->orWhereDate('licence_expires_on', '<=', $limit));
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $f
     */
    public function scopeFilter(Builder $query, array $f): void
    {
        $query
            ->when(($f['active'] ?? '1') !== 'all', fn (Builder $q) => $q->where('active', ($f['active'] ?? '1') === '1'))
            ->when($f['depot'] ?? null, fn (Builder $q, $v) => $q->where('depot', $v))
            ->when($f['role'] ?? null, fn (Builder $q, $v) => $q->where('role', $v))
            ->when($f['due'] ?? null, fn (Builder $q) => $q->due())
            ->when($f['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $term).'%';
                $q->where(fn (Builder $w) => $w->where('name', 'like', $like)->orWhere('employee_number', 'like', $like));
            });
    }
}
