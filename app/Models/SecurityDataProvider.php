<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
])]
class SecurityDataProvider extends Model
{
    protected $table = 'security_data_providers';

    public function securities(): HasMany
    {
        return $this->hasMany(Security::class, 'current_data_provider_id');
    }
}
