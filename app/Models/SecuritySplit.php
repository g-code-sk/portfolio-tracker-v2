<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'security_id',
    'security_data_provider_id',
    'effective_on',
    'ratio_numerator',
    'ratio_denominator',
    'raw_ratio',
])]
class SecuritySplit extends Model
{
    protected function casts(): array
    {
        return [
            'effective_on' => 'date',
        ];
    }

    public function security(): BelongsTo
    {
        return $this->belongsTo(Security::class);
    }

    public function securityDataProvider(): BelongsTo
    {
        return $this->belongsTo(SecurityDataProvider::class, 'security_data_provider_id');
    }
}
