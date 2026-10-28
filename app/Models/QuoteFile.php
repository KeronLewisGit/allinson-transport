<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A drawing, photo or packing list attached to a quote request, on the private disk.
 *
 * @property int $id
 * @property int $quote_id
 * @property string $path
 * @property string $original_name
 * @property string|null $mime
 * @property int $size
 * @property-read Quote $quote
 */
class QuoteFile extends Model
{
    protected $fillable = ['path', 'original_name', 'mime', 'size'];

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
