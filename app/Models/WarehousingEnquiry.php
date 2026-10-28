<?php

namespace App\Models;

use App\Contracts\Submission;
use App\Enums\Area;
use App\Enums\GoodsType;
use App\Enums\StorageDuration;
use App\Enums\WarehousingStatus;
use App\Models\Concerns\HasReference;
use App\Models\Concerns\HasTimeline;
use App\Support\Depots;
use Database\Factories\WarehousingEnquiryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A request for warehouse space: pallet spaces, how long, what goods, what handling.
 *
 * @property int $id
 * @property string $reference
 * @property WarehousingStatus $status
 * @property string $company
 * @property string $contact_name
 * @property string $email
 * @property string $phone
 * @property int $pallet_spaces
 * @property StorageDuration $duration
 * @property Carbon|null $start_on
 * @property GoodsType $goods_type
 * @property string $goods_description
 * @property list<string>|null $handling
 * @property bool $hazardous
 * @property string|null $depot
 * @property int|null $assigned_to
 * @property string|null $message
 * @property Carbon|null $status_changed_at
 * @property Carbon $created_at
 * @property-read User|null $assignee
 */
class WarehousingEnquiry extends Model implements Submission
{
    /** @use HasFactory<WarehousingEnquiryFactory> */
    use HasFactory, HasReference, HasTimeline;

    /** New enquiries untouched after this many days are flagged on the warehouse dashboard. */
    public const int RESPONSE_DAYS = 1;

    public const array HANDLING = [
        'storage' => 'Storage only (in and out by the pallet)',
        'pick_pack' => 'Pick, pack and despatch (order fulfilment)',
        'ecommerce' => 'Online orders to consumers',
        'destuffing' => 'Container destuffing',
        'cross_dock' => 'Cross-docking',
        'distribution' => 'Onward delivery by Allinson',
    ];

    protected $fillable = [
        'status', 'company', 'contact_name', 'email', 'phone', 'pallet_spaces', 'duration', 'start_on', 'goods_type',
        'goods_description', 'handling', 'hazardous', 'depot', 'assigned_to', 'message', 'status_changed_at', 'source_url',
    ];

    public static function referencePrefix(): string
    {
        return 'WH';
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'status' => WarehousingStatus::class,
            'duration' => StorageDuration::class,
            'goods_type' => GoodsType::class,
            'start_on' => 'date',
            'handling' => 'array',
            'hazardous' => 'boolean',
            'pallet_spaces' => 'integer',
            'status_changed_at' => 'datetime',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $enquiry): void {
            $enquiry->status ??= WarehousingStatus::New;
        });
        static::saving(function (self $enquiry): void {
            $enquiry->setAttribute('phone_digits', preg_replace('/\D+/', '', $enquiry->phone) ?: null);
        });
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function claimStatus(WarehousingStatus $status): bool
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

    public function isWaiting(): bool
    {
        return $this->status === WarehousingStatus::New && $this->created_at->lt(now()->subDays(self::RESPONSE_DAYS));
    }

    /** @param Builder<self> $query */
    public function scopeWaiting(Builder $query): void
    {
        $query->where('status', WarehousingStatus::New)->where('created_at', '<', now()->subDays(self::RESPONSE_DAYS));
    }

    /** @return list<string> */
    public function handlingLabels(): array
    {
        return array_map(fn (string $h) => self::HANDLING[$h] ?? $h, $this->handling ?? []);
    }

    public function depotName(): string
    {
        return $this->depot ? Depots::name($this->depot) : 'No preference';
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $f
     */
    public function scopeFilter(Builder $query, array $f): void
    {
        $query
            ->when($f['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($f['goods'] ?? null, fn (Builder $q, $v) => $q->where('goods_type', $v))
            ->when($f['depot'] ?? null, fn (Builder $q, $v) => $q->where('depot', $v))
            ->when($f['waiting'] ?? null, fn (Builder $q) => $q->waiting())
            ->when($f['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $term).'%';
                $q->where(fn (Builder $w) => $w->where('company', 'like', $like)->orWhere('contact_name', 'like', $like)
                    ->orWhere('reference', 'like', $like)->orWhere('email', 'like', $like)->orWhere('goods_description', 'like', $like));
            });
    }

    public function referenceNumber(): string
    {
        return $this->reference;
    }

    public function kindLabel(): string
    {
        return 'Warehousing enquiry';
    }

    public function area(): Area
    {
        return Area::Warehousing;
    }

    public function senderName(): string
    {
        return $this->contact_name;
    }

    public function senderEmail(): ?string
    {
        return $this->email;
    }

    public function summary(): array
    {
        return array_filter([
            'Company' => $this->company,
            'Pallet spaces' => number_format($this->pallet_spaces),
            'How long' => $this->duration->label(),
            'From' => $this->start_on?->format('j M Y') ?? '',
            'Goods' => $this->goods_type->label().': '.mb_strimwidth($this->goods_description, 0, 160, '…'),
            'Handling' => implode(', ', $this->handlingLabels()),
            'Hazardous' => $this->hazardous ? 'Yes' : '',
            'Depot' => $this->depotName(),
            'Contact' => $this->contact_name.' · '.format_phone($this->phone).' · '.$this->email,
        ]);
    }

    public function adminUrl(): string
    {
        return route('admin.warehousing.show', $this);
    }

    public function nextSteps(): string
    {
        return 'Our warehouse team will call you to talk through your product, volumes and handling, and then send you a proposal.';
    }
}
