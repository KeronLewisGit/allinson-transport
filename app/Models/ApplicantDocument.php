<?php

namespace App\Models;

use App\Enums\ApplicantDocumentKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A copy of a licence, CPC card, tacho card or right-to-work document, uploaded by the
 * applicant through their signed link. Re-uploads supersede, never delete.
 *
 * @property int $id
 * @property int $job_application_id
 * @property ApplicantDocumentKind $kind
 * @property string $path
 * @property string $original_name
 * @property string|null $mime
 * @property int $size
 * @property Carbon|null $superseded_at
 * @property Carbon $created_at
 * @property-read JobApplication $application
 */
class ApplicantDocument extends Model
{
    public const array FILE_TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'heic'];

    protected $fillable = ['kind', 'path', 'original_name', 'mime', 'size', 'superseded_at'];

    #[\Override]
    protected function casts(): array
    {
        return ['kind' => ApplicantDocumentKind::class, 'superseded_at' => 'datetime'];
    }

    /** @return BelongsTo<JobApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    /** @param Builder<self> $query */
    public function scopeCurrent(Builder $query): void
    {
        $query->whereNull('superseded_at');
    }
}
