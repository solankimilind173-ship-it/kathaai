<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'onboarding_status')) {
                $table->string('onboarding_status')->default('not_started')->after('preferences');
            }
            if (! Schema::hasColumn('users', 'last_onboarding_step')) {
                $table->string('last_onboarding_step')->nullable()->after('onboarding_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_onboarding_step')) {
                $table->dropColumn('last_onboarding_step');
            }
            if (Schema::hasColumn('users', 'onboarding_status')) {
                $table->dropColumn('onboarding_status');
            }
        });
    }
};

