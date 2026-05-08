<?php

namespace App\Models;

use Domain\Transaction\Enums\TransactionTypeCode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopePortfolioPositionMetrics(Builder $query, int $portfolioId): Builder
    {
        return $query
            ->where('transactions.portfolio_id', $portfolioId)
            ->join('securities', 'securities.id', '=', 'transactions.security_id')
            ->join('currencies', 'currencies.id', '=', 'transactions.currency_id')
            ->join('transaction_types', 'transaction_types.id', '=', 'transactions.type_id')
            ->selectRaw(
                '
                transactions.security_id as security_id,
                transactions.currency_id as currency_id,
                securities.ticker as ticker,
                securities.name as name,
                currencies.symbol as currency,
                SUM(
                    CASE
                        WHEN transaction_types.code = ? THEN transactions.number_of_shares
                        ELSE 0
                    END
                ) as shares_bought,
                SUM(
                    CASE
                        WHEN transaction_types.code = ? THEN transactions.number_of_shares
                        ELSE 0
                    END
                ) as shares_sold,
                SUM(
                    CASE
                        WHEN transaction_types.code = ? THEN transactions.number_of_shares * transactions.price_per_share
                        ELSE 0
                    END
                ) as invested_amount,
                SUM(
                    CASE
                        WHEN transaction_types.code = ? THEN transactions.number_of_shares * transactions.price_per_share
                        ELSE 0
                    END
                ) as sold_amount,
                SUM(
                    CASE
                        WHEN transaction_types.code = ? THEN transactions.number_of_shares
                        ELSE -transactions.number_of_shares
                    END
                ) as total_shares
                ',
                [
                    TransactionTypeCode::Buy->value,
                    TransactionTypeCode::Sell->value,
                    TransactionTypeCode::Buy->value,
                    TransactionTypeCode::Sell->value,
                    TransactionTypeCode::Buy->value,
                ]
            )
            ->groupBy(
                'transactions.security_id',
                'securities.ticker',
                'securities.name',
                'transactions.currency_id',
                'currencies.symbol',
            )
            ->orderBy('securities.ticker');
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
