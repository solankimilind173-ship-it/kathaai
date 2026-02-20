<?php

namespace Tests\Unit\Modules\Admin\Services;

use App\Models\User;
use App\Modules\Admin\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserService;
    }

    public function test_create_user_creates_user_with_random_password(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'email_verified_at' => now(),
        ];
        $user = $this->service->createUser($data, 0);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Test User', $user->name);
        $this->assertSame('newuser@example.com', $user->email);
        $this->assertNotNull($user->password);
        $this->assertNotSame('', $user->password);
    }

    public function test_create_user_with_initial_credits_creates_transaction_and_sets_credits(): void
    {
        $data = [
            'name' => 'Credited User',
            'email' => 'credited@example.com',
            'email_verified_at' => now(),
        ];
        $user = $this->service->createUser($data, 100);
        $user->refresh();
        $this->assertSame(100, $user->credits);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'admin_adjustment',
        ]);
    }

    public function test_import_from_csv_rows_creates_new_users(): void
    {
        $rows = [
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ];
        $result = $this->service->importFromCsvRows($rows);
        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['skipped']);
        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'bob@example.com']);
    }

    public function test_import_from_csv_rows_skips_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);
        $rows = [
            ['name' => 'New', 'email' => 'existing@example.com'],
        ];
        $result = $this->service->importFromCsvRows($rows);
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['skipped']);
    }

    public function test_adjust_credits_increments_user_balance_and_creates_transaction(): void
    {
        $user = User::factory()->create(['credits' => 50]);
        $this->service->adjustCredits($user, 30, 'Bonus');
        $user->refresh();
        $this->assertSame(80, $user->credits);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 30,
            'type' => 'admin_adjustment',
            'description' => 'Bonus',
        ]);
    }
}
