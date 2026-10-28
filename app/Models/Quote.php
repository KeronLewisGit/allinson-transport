<?php

namespace App\Models;

use App\Contracts\Submission;
use App\Enums\Area;
use App\Enums\QuoteService;
use App\Enums\QuoteStatus;
use App\Models\Concerns\HasReference;
use App\Models\Concerns\HasTimeline;
use App\Support\Depots;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * A haulage quote request from the website. Routed to a depot by collection postcode
 * (App\Support\Depots), priced by traffic, and sent to the customer as a signed link they
 * can accept or decline.
 *
 * @property int $id
 * @property string $reference
 * @property string $public_id
 * @property QuoteStatus $status
 * @property QuoteService $service
 * @property string $depot
 * @property int|null $assigned_to
 * @property string|null $company
 * @property string $contact_name
 * @property string $email
 * @property string $phone
 * @property string $collection_postcode
 * @property string|null $collection_town
 * @property Carbon $collection_date
 * @property string $delivery_postcode
 * @property string|null $delivery_town
 * @property Carbon|null $delivery_date
 * @property string $goods
 * @property int|null $pallets
 * @property int $weight_kg
 * @property string|null $length_m
 * @property string|null $width_m
 * @property string|null $height_m
 * @property bool $adr
 * @property string|null $adr_details
 * @property bool $tail_lift
 * @property bool $regular
 * @property string|null $message
 * @property string|null $price
 * @property string|null $price_notes
 * @property Carbon|null $valid_until
 * @property Carbon|null $quoted_at
 * @property Carbon|null $responded_at
 * @property string|null $customer_note
 * @property Carbon|null $status_changed_at
 * @property string|null $source_url
 * @property Carbon $created_at
 * @property-read User|null $assignee
 * @property-read Collection<int, QuoteFile> $files
 */
class Quote extends Model implements Submission
{
    /** @use HasFactory<QuoteFactory> */
    use HasFactory, HasReference, HasTimeline, HasUlids;

    /** A request still waiting for a price after this many working hours is flagged. */
    public const int RESPONSE_HOURS = 4;

    /** Collections this many days away or closer are urgent on the traffic dashboard. */
    public const int URGENT_DAYS = 2;

    /** How long the customer's quote link works, at most (it also stops at valid_until). */
    public const int LINK_DAYS = 30;

    public const array FILE_TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];

    public const int MAX_FILES = 3;

    protected $fillable = [
        'status', 'service', 'depot', 'assigned_to', 'company', 'contact_name', 'email', 'phone',
        'collection_postcode', 'collection_town', 'collection_date', 'delivery_postcode', 'delivery_town', 'delivery_date',
        'goods', 'pallets', 'weight_kg', 'length_m', 'width_m', 'height_m', 'adr', 'adr_details', 'tail_lift', 'regular', 'message',
        'price', 'price_notes', 'valid_until', 'quoted_at', 'responded_at', 'customer_note', 'status_changed_at', 'source_url',
    ];

    public static function referencePrefix(): string
    {
        return 'QR';
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
            'status' => QuoteStatus::class,
            'service' => QuoteService::class,
            'collection_date' => 'date',
            'delivery_date' => 'date',
            'adr' => 'boolean',
            'tail_lift' => 'boolean',
            'regular' => 'boolean',
            'pallets' => 'integer',
            'weight_kg' => 'integer',
            'price' => 'decimal:2',
            'valid_until' => 'date',
            'quoted_at' => 'datetime',
            'responded_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $quote): void {
            $quote->status ??= QuoteStatus::New;
            $quote->depot = Depots::exists($quote->depot ?? null) ? $quote->depot : Depots::forPostcode($quote->collection_postcode);
        });
        static::saving(function (self $quote): void {
            $quote->setAttribute('phone_digits', preg_replace('/\D+/', '', $quote->phone) ?: null);
        });
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<QuoteFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(QuoteFile::class);
    }

    public function depotName(): string
    {
        return Depots::name($this->depot);
    }

    public function route(): string
    {
        return $this->collection_postcode.' → '.$this->delivery_postcode;
    }

    /** "3.2 × 2.4 × 2.6 m", or null. */
    public function dimensions(): ?string
    {
        $parts = array_filter([$this->length_m, $this->width_m, $this->height_m], fn ($v) => $v !== null && $v !== '');

        return $parts === [] ? null : implode(' × ', array_map(fn ($v) => plain_number((float) $v), $parts)).' m';
    }

    /**
     * Moves to $status with a conditional UPDATE, so the same change sent twice at once (or by two
     * people) is applied and logged only once.
     *
     * @param  array<string, mixed>  $extra
     */
    public function claimStatus(QuoteStatus $status, array $extra = []): bool
    {
        if ($this->status === $status) {
            return false;
        }
        $changes = ['status' => $status, 'status_changed_at' => now()] + $extra;
        $claimed = static::query()->whereKey($this->getKey())->where('status', $this->getRawOriginal('status'))
            ->update(array_map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $changes)) === 1;
        if ($claimed) {
            $this->forceFill($changes)->syncOriginal();
        }

        return $claimed;
    }

    /** Still waiting for a price, and older than RESPONSE_HOURS. */
    public function isOverdue(): bool
    {
        return $this->status->isOpen() && $this->created_at->lt(now()->subHours(self::RESPONSE_HOURS));
    }

    public function collectsSoon(): bool
    {
        return $this->collection_date->lte(today()->addDays(self::URGENT_DAYS));
    }

    /** "Collect today", "Collect in 3 days", "Collection passed". */
    public function collectionLabel(): string
    {
        $days = (int) today()->diffInDays($this->collection_date, false);

        return match (true) {
            $days < 0 => 'Collection date passed',
            $days === 0 => 'Collect today',
            $days === 1 => 'Collect tomorrow',
            default => 'Collect in '.$days.' days',
        };
    }

    public function collectionTone(): string
    {
        if (! $this->status->isOpen()) {
            return 'neutral';
        }

        return match (true) {
            $this->collection_date->lt(today()) => 'critical',
            $this->collectsSoon() => 'attention',
            default => 'neutral',
        };
    }

    /** Whether the customer can still accept the price sent to them. */
    public function canBeAccepted(): bool
    {
        return $this->status === QuoteStatus::Quoted && $this->price !== null
            && ($this->valid_until === null || $this->valid_until->gte(today()));
    }

    /** The link in the quote email: the customer sees the price and accepts or declines it. */
    public function responseUrl(): string
    {
        $until = now()->addDays(self::LINK_DAYS);
        if ($this->valid_until && $this->valid_until->copy()->endOfDay()->lt($until)) {
            $until = $this->valid_until->copy()->endOfDay();
        }

        return URL::temporarySignedRoute('quotes.response', $until, ['quote' => $this->public_id]);
    }

    /** @param Builder<self> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', QuoteStatus::open());
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $f
     */
    public function scopeFilter(Builder $query, array $f): void
    {
        $query
            ->when($f['status'] ?? null, fn (Builder $q, $v) => $v === 'open' ? $q->open() : $q->where('status', $v))
            ->when($f['depot'] ?? null, fn (Builder $q, $v) => $q->where('depot', $v))
            ->when($f['service'] ?? null, fn (Builder $q, $v) => $q->where('service', $v))
            ->when($f['adr'] ?? null, fn (Builder $q) => $q->where('adr', true))
            ->when($f['assigned'] ?? null, fn (Builder $q, $v) => $v === 'none' ? $q->whereNull('assigned_to') : $q->where('assigned_to', $v))
            ->when($f['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $term).'%';
                $digits = preg_replace('/\D+/', '', (string) $term);
                $q->where(function (Builder $w) use ($like, $digits) {
                    $w->where('reference', 'like', $like)->orWhere('company', 'like', $like)->orWhere('contact_name', 'like', $like)
                        ->orWhere('email', 'like', $like)->orWhere('collection_postcode', 'like', $like)->orWhere('delivery_postcode', 'like', $like)
                        ->orWhere('goods', 'like', $like);
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
        return 'Quote request';
    }

    public function area(): Area
    {
        return Area::Quotes;
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
            'Service' => $this->service->label(),
            'Depot' => $this->depotName(),
            'Route' => $this->route(),
            'Collection' => $this->collection_date->format('D j M Y'),
            'Goods' => $this->goods,
            'Weight' => number_format($this->weight_kg).' kg'.($this->pallets ? ', '.$this->pallets.' pallets' : ''),
            'Dimensions' => (string) $this->dimensions(),
            'ADR' => $this->adr ? 'Yes'.($this->adr_details ? ': '.$this->adr_details : '') : '',
            'Customer' => trim(($this->company ? $this->company.' · ' : '').$this->contact_name.' · '.format_phone($this->phone)),
        ]);
    }

    public function adminUrl(): string
    {
        return route('admin.quotes.show', $this);
    }

    public function nextSteps(): string
    {
        return 'The traffic office at our '.$this->depotName().' depot will price your request and email the quote to you, usually the same working day. Collections needed sooner are best phoned through on '.ga('phone').'.';
    }
}
