<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'yearly_price',
        'stripe_price_id',
        'stripe_yearly_price_id',
        'max_projects',
        'max_dubbing_languages',
        'max_reels_per_episode',
        'max_video_minutes',
        'allow_multiple_video_styles',
        'allow_4k',
        'allow_voice_style_selection',
        'allow_background_music',
        'allow_intro_song_generation',
        'monthly_credits',
        'credit_rollover',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credit_rollover' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function featureDefinitions(): BelongsToMany
    {
        return $this->belongsToMany(FeatureDefinition::class, 'plan_features', 'plan_id', 'feature_definition_id')
            ->withPivot('value')
            ->withTimestamps();
    }

    /**
     * Get feature value by definition key. Checks plan_features first, then legacy plan columns.
     */
    public function getFeatureValue(string $key): mixed
    {
        $pf = $this->planFeatures()->whereHas('featureDefinition', fn ($q) => $q->where('key', $key))->first();
        if ($pf !== null) {
            $def = $pf->featureDefinition;
            return $this->castFeatureValue($pf->value, $def->type ?? 'string');
        }
        if (array_key_exists($key, $this->getAttributes())) {
            return $this->getAttributeFromArray($key);
        }
        return null;
    }

    public function getAttribute($key): mixed
    {
        $featureKeys = [
            'voice_languages', 'max_export_resolution', 'allow_background_music', 'allow_intro_song_generation',
            'max_episodes_per_project', 'priority_rendering_queue', 'max_dubbing_languages', 'allow_4k',
            'allow_voice_style_selection', 'allow_multiple_video_styles', 'max_reels_per_episode',
            'max_video_minutes', 'max_projects',
        ];
        if (in_array($key, $featureKeys, true)) {
            $value = $this->getFeatureValue($key);
            if ($value !== null) {
                return $value;
            }
        }
        return parent::getAttribute($key);
    }

    /**
     * Resolve Stripe Price ID for checkout (plan column first, then config).
     */
    public function getStripePriceIdForInterval(string $interval): ?string
    {
        if ($interval === 'yearly') {
            return $this->stripe_yearly_price_id ?? config("services.stripe.plans.{$this->slug}.yearly");
        }
        return $this->stripe_price_id ?? config("services.stripe.plans.{$this->slug}.monthly");
    }

    private function castFeatureValue(string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }
}
