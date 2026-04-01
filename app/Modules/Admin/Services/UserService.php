<?php

namespace App\Modules\Admin\Services;

use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Admin user business logic: create, import CSV, export, credit adjustments.
 */
class UserService
{
    /**
     * Create user with optional initial credits. Returns the created user.
     */
    public function createUser(array $data, int $initialCredits = 0): User
    {
        return DB::transaction(function () use ($data, $initialCredits) {
            $user = User::create([
                ...$data,
                'password' => bcrypt(Str::random(32)),
            ]);

            if ($initialCredits > 0) {
                CreditTransaction::create([
                    'user_id' => $user->id,
                    'amount' => $initialCredits,
                    'type' => 'admin_adjustment',
                    'description' => 'Initial credits (admin)',
                ]);
                $user->update(['credits' => $initialCredits]);
            }

            return $user;
        });
    }

    /**
     * Import users from CSV rows. Returns ['created' => int, 'skipped' => int].
     * Rows must be pre-validated (e.g. by CsvUserImportStructure). Inputs sanitized.
     *
     * @param  array<int, array{name: string, email: string}>  $rows
     */
    public function importFromCsvRows(array $rows): array
    {
        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, &$created, &$skipped) {
            foreach ($rows as $row) {
                $name = trim(strip_tags((string) ($row['name'] ?? '')));
                $email = trim(strip_tags((string) ($row['email'] ?? '')));
                if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;

                    continue;
                }
                if (User::where('email', $email)->exists()) {
                    $skipped++;

                    continue;
                }
                User::create([
                    'name' => $name !== '' ? $name : 'User',
                    'email' => $email,
                    'password' => bcrypt(Str::random(32)),
                    'role' => 'user',
                ]);
                $created++;
            }
        });

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Adjust user credits: create transaction and update balance.
     */
    public function adjustCredits(User $user, int $amount, string $description = 'Admin adjustment'): void
    {
        $description = trim(strip_tags($description));
        if ($description === '') {
            $description = 'Admin adjustment';
        }

        DB::transaction(function () use ($user, $amount, $description) {
            CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'admin_adjustment',
                'description' => $description,
            ]);
            $user->increment('credits', $amount);
        });
    }
}
