<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'ticker',
    'name',
    'isin',
    'current_price',
    'current_price_currency',
    'current_price_updated_at',
    'current_data_provider_id',
])]
class Security extends Model
{
    /**
     * @param  Builder<Security>  $query
     * @return Builder<Security>
     */
    public function scopeWhereTickerPresent(Builder $query): Builder
    {
        return $query
            ->whereNotNull('ticker')
            ->where('ticker', '!=', '');
    }

    protected function casts(): array
    {
        return [
            'current_price' => 'decimal:10',
            'current_price_updated_at' => 'datetime',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function splits(): HasMany
    {
        return $this->hasMany(SecuritySplit::class);
    }

    public function currentDataProvider(): BelongsTo
    {
        return $this->belongsTo(SecurityDataProvider::class, 'current_data_provider_id');
    }

    public function hasPlaceholderNameComparedToTicker(string $ticker): bool
    {
        return $this->name === '' || $this->name === $ticker;
    }

    public function isCurrentPriceStale(Carbon $now, int $ttlHours): bool
    {
        if ($this->current_price_updated_at === null) {
            return true;
        }

        return $this->current_price_updated_at->copy()->addHours($ttlHours)->lte($now);
    }
}
