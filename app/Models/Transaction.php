<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'portfolio_id',
    'security_id',
    'type_id',
    'external_transaction_id',
    'number_of_shares',
    'price_per_share',
    'currency_id',
    'executed_at',
])]
class Transaction extends Model
{
    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
        ];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function security(): BelongsTo
    {
        return $this->belongsTo(Security::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class, 'type_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
