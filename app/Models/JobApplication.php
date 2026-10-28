<?php

namespace App\Models;

use App\Contracts\Submission;
use App\Enums\ApplicationStatus;
use App\Enums\Area;
use App\Enums\AssessmentResult;
use App\Enums\JobRole;
use App\Enums\ReferenceStatus;
use App\Models\Concerns\HasReference;
use App\Models\Concerns\HasTimeline;
use App\Support\Depots;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * An application for a driving, warehouse or transport-office role at one depot, replacing
 * the old Word form. Licence, CPC, tacho card and endorsements are self-declared by the
 * applicant; HR records the right-to-work check, references and the assessment drive.
 *
 * @property int $id
 * @property string $reference
 * @property string $public_id
 * @property int|null $job_posting_id
 * @property JobRole $role
 * @property string $depot
 * @property ApplicationStatus $status
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property string|null $email
 * @property string|null $postcode
 * @property string $experience
 * @property list<string>|null $licence_categories
 * @property Carbon|null $cpc_expires_on
 * @property Carbon|null $tacho_card_expires_on
 * @property int|null $licence_points
 * @property string|null $endorsements
 * @property string|null $hiab_qualification
 * @property list<string>|null $forklift
 * @property bool $right_to_work
 * @property Carbon|null $available_from
 * @property string|null $message
 * @property string|null $cv_path
 * @property string|null $cv_name
 * @property string|null $cv_mime
 * @property int|null $cv_size
 * @property Carbon|null $consented_at
 * @property Carbon|null $rtw_checked_at
 * @property int|null $rtw_checked_by
 * @property string|null $rtw_method
 * @property ReferenceStatus $references_status
 * @property Carbon|null $references_updated_at
 * @property Carbon|null $assessment_at
 * @property AssessmentResult|null $assessment_result
 * @property Carbon|null $documents_requested_at
 * @property Carbon|null $status_changed_at
 * @property Carbon $created_at
 * @property-read string $full_name
 * @property-read JobPosting|null $jobPosting
 * @property-read User|null $rtwChecker
 * @property-read Collection<int, ApplicantDocument> $documents
 * @property-read Collection<int, ApplicantDocument> $currentDocuments
 * @property-read StaffMember|null $staffMember
 */
class JobApplication extends Model implements Submission
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory, HasReference, HasTimeline, HasUlids;

    public const array CV_TYPES = ['pdf', 'doc', 'docx'];

    /** New applications older than this many days count as waiting on the HR dashboard. */
    public const int REVIEW_DAYS = 2;

    /** A self-declared CPC or tacho card expiring within this many days is flagged. */
    public const int CARD_WARNING_DAYS = 60;

    /** How long the documents link sent to an applicant works. */
    public const int DOCUMENTS_LINK_DAYS = 14;

    public const array EXPERIENCE = ['none' => 'New to the role', '1' => 'Under 2 years', '2' => '2 to 5 years', '5' => '5 to 10 years', '10' => 'Over 10 years'];

    public const array RTW_METHODS = [
        'share_code' => 'Home Office online check (share code)',
        'uk_passport' => 'UK or Irish passport seen in person',
        'idsp' => 'Digital identity check (IDSP)',
        'other_document' => 'Other List A or B document',
    ];

    protected $fillable = [
        'job_posting_id', 'role', 'depot', 'status', 'first_name', 'last_name', 'phone', 'email', 'postcode', 'experience',
        'licence_categories', 'cpc_expires_on', 'tacho_card_expires_on', 'licence_points', 'endorsements', 'hiab_qualification',
        'forklift', 'right_to_work', 'available_from', 'message', 'cv_path', 'cv_name', 'cv_mime', 'cv_size', 'consented_at',
        'rtw_checked_at', 'rtw_checked_by', 'rtw_method', 'references_status', 'references_updated_at', 'assessment_at',
        'assessment_result', 'documents_requested_at', 'status_changed_at',
    ];

    public static function referencePrefix(): string
    {
        return 'JA';
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'role' => JobRole::class,
            'status' => ApplicationStatus::class,
            'licence_categories' => 'array',
            'forklift' => 'array',
            'cpc_expires_on' => 'date',
            'tacho_card_expires_on' => 'date',
            'licence_points' => 'integer',
            'right_to_work' => 'boolean',
            'available_from' => 'date',
            'consented_at' => 'datetime',
            'rtw_checked_at' => 'datetime',
            'references_status' => ReferenceStatus::class,
            'references_updated_at' => 'datetime',
            'assessment_at' => 'datetime',
            'assessment_result' => AssessmentResult::class,
            'documents_requested_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $application): void {
            $application->status ??= ApplicationStatus::New;
            $application->references_status ??= ReferenceStatus::NotRequested;
        });
        static::saving(function (self $application): void {
            $application->setAttribute('phone_digits', preg_replace('/\D+/', '', $application->phone) ?: null);
        });
    }

    /** @return BelongsTo<JobPosting, $this> */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    /** @return BelongsTo<User, $this> */
    public function rtwChecker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rtw_checked_by');
    }

    /** @return HasMany<ApplicantDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ApplicantDocument::class)->orderByDesc('id');
    }

    /** @return HasMany<ApplicantDocument, $this> */
    public function currentDocuments(): HasMany
    {
        return $this->hasMany(ApplicantDocument::class)->whereNull('superseded_at')->orderBy('kind');
    }

    /** @return HasOne<StaffMember, $this> */
    public function staffMember(): HasOne
    {
        return $this->hasOne(StaffMember::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function drives(): bool
    {
        return $this->role->drives();
    }

    public function depotName(): string
    {
        return Depots::name($this->depot);
    }

    public function position(): string
    {
        return $this->jobPosting->title ?? $this->role->label().', '.$this->depotName().' (general application)';
    }

    /** "C+E, C, B" in the usual order. */
    public function licenceList(): string
    {
        $labels = ['CE' => 'C+E', 'C' => 'C', 'C1E' => 'C1+E', 'C1' => 'C1', 'B' => 'B'];
        $have = $this->licence_categories ?? [];

        return implode(', ', array_values(array_filter(array_map(fn ($k) => in_array($k, $have, true) ? $labels[$k] : null, array_keys($labels)))));
    }

    /** "expired", "expiring" (within CARD_WARNING_DAYS), "valid" or "missing" for a self-declared card date. */
    public static function cardState(?Carbon $date): string
    {
        return match (true) {
            $date === null => 'missing',
            $date->lt(today()) => 'expired',
            $date->lte(today()->addDays(self::CARD_WARNING_DAYS)) => 'expiring',
            default => 'valid',
        };
    }

    /**
     * Moves to $status with a conditional UPDATE, so a double submit is applied (and logged) once.
     */
    public function claimStatus(ApplicationStatus $status): bool
    {
        if ($this->status === $status) {
            return false;
        }
        $changes = ['status' => $status, 'status_changed_at' => now()];
        $claimed = static::query()->whereKey($this->getKey())->where('status', $this->getRawOriginal('status'))
            ->update(['status' => $status->value, 'status_changed_at' => $changes['status_changed_at']]) === 1;
        if ($claimed) {
            $this->forceFill($changes)->syncOriginal();
        }

        return $claimed;
    }

    /**
     * What still stands between this applicant and an offer.
     *
     * @return list<string>
     */
    public function outstanding(): array
    {
        $out = [];
        if (! $this->rtw_checked_at) {
            $out[] = 'Right-to-work check';
        }
        if ($this->references_status !== ReferenceStatus::Received) {
            $out[] = 'References';
        }
        if ($this->drives() && $this->assessment_result !== AssessmentResult::Passed) {
            $out[] = 'Assessment drive';
        }

        return $out;
    }

    /** The link HR emails so the applicant can upload copies of their cards before the assessment. */
    public function documentsUrl(): string
    {
        return URL::temporarySignedRoute('careers.documents', now()->addDays(self::DOCUMENTS_LINK_DAYS), ['application' => $this->public_id]);
    }

    public function canUploadDocuments(): bool
    {
        return $this->status->isActive() && $this->documents_requested_at !== null;
    }

    public function isWaiting(): bool
    {
        return $this->status === ApplicationStatus::New && $this->created_at->lt(now()->subDays(self::REVIEW_DAYS));
    }

    /** @param Builder<self> $query */
    public function scopeWaiting(Builder $query): void
    {
        $query->where('status', ApplicationStatus::New)->where('created_at', '<', now()->subDays(self::REVIEW_DAYS));
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNotIn('status', [ApplicationStatus::Hired, ApplicationStatus::Declined, ApplicationStatus::Withdrawn]);
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $f
     */
    public function scopeFilter(Builder $query, array $f): void
    {
        $query
            ->when($f['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($f['role'] ?? null, fn (Builder $q, $v) => $q->where('role', $v))
            ->when($f['depot'] ?? null, fn (Builder $q, $v) => $q->where('depot', $v))
            ->when($f['job'] ?? null, fn (Builder $q, $v) => $q->where('job_posting_id', $v))
            ->when($f['waiting'] ?? null, fn (Builder $q) => $q->waiting())
            ->when($f['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $term).'%';
                $digits = preg_replace('/\D+/', '', (string) $term);
                $q->where(function (Builder $w) use ($like, $digits) {
                    $w->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                        ->orWhere('reference', 'like', $like)->orWhere('email', 'like', $like);
                    if (strlen((string) $digits) >= 4) {
                        $w->orWhere('phone_digits', 'like', '%'.$digits.'%');
                    }
                });
            });
    }

    public function referenceNumber(): string
    {
        return $this->reference;
    }

    public function kindLabel(): string
    {
        return 'Job application';
    }

    public function area(): Area
    {
        return Area::Recruitment;
    }

    public function senderName(): string
    {
        return $this->full_name;
    }

    public function senderEmail(): ?string
    {
        return $this->email;
    }

    public function summary(): array
    {
        return array_filter([
            'Applicant' => $this->full_name,
            'Position' => $this->position(),
            'Experience' => self::EXPERIENCE[$this->experience] ?? $this->experience,
            'Licence' => $this->licenceList(),
            'Driver CPC expires' => $this->cpc_expires_on?->format('j M Y') ?? '',
            'Tacho card expires' => $this->tacho_card_expires_on?->format('j M Y') ?? '',
            'Penalty points' => $this->licence_points !== null ? (string) $this->licence_points : '',
            'Phone' => format_phone($this->phone),
            'CV attached' => $this->cv_path ? 'Yes' : '',
        ]);
    }

    public function adminUrl(): string
    {
        return route('admin.applications.show', $this);
    }

    public function nextSteps(): string
    {
        return $this->drives()
            ? 'Our HR team reads every application. If your experience fits, we will phone you, then ask for copies of your licence, Driver CPC and tacho cards and book an assessment drive at the '.$this->depotName().' depot.'
            : 'Our HR team reads every application. If your experience fits, we will phone you to arrange the next step.';
    }
}
