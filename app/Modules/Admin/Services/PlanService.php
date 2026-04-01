<?php

namespace App\Modules\Admin\Services;

use App\Models\FeatureDefinition;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\User;

/**
 * Admin plan business logic: sync plan features, delete guard.
 */
class PlanService
{
    /**
     * Whether the plan can be deleted (no users currently assigned to it).
     */
    public function canDelete(Plan $plan): bool
    {
        return ! User::where('plan_id', $plan->id)->exists();
    }

    /**
     * Sync plan_features from form (object or array). Removes features not in payload.
     */
    public function syncPlanFeatures(Plan $plan, array $features): void
    {
        $features = is_array($features) && ! isset($features[0]) && ! empty($features)
            ? collect($features)->map(fn ($value, $key) => ['key' => $key, 'value' => $value])->values()->all()
            : $features;

        $keys = array_unique(array_column($features, 'key'));
        $defs = FeatureDefinition::whereIn('key', $keys)->get()->keyBy('key');

        foreach ($features as $f) {
            $key = trim(strip_tags((string) ($f['key'] ?? '')));
            $value = $f['value'] ?? '';
            if ($key === '' || ! $defs->has($key)) {
                continue;
            }
            $def = $defs->get($key);
            if ($def->type === 'boolean') {
                $value = $value ? '1' : '0';
            } else {
                $value = trim(strip_tags((string) $value));
            }

            PlanFeature::updateOrCreate(
                [
                    'plan_id' => $plan->id,
                    'feature_definition_id' => $def->id,
                ],
                ['value' => $value]
            );
        }

        $syncedKeys = array_column($features, 'key');
        $plan->planFeatures()->whereHas('featureDefinition', fn ($q) => $q->whereNotIn('key', $syncedKeys))->delete();
    }
}
