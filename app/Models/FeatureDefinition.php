<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureDefinition extends Model
{
    protected $fillable = [
        'key',
        'label',
        'type',
        'options',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class, 'feature_definition_id');
    }
}
