<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes to speed up admin dashboard and analytics queries (date ranges, aggregations).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('episodes', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['project_id', 'created_at']);
        });

        Schema::table('scenes', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['amount', 'created_at']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['plan_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'created_at']);
        });

        Schema::table('episodes', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['project_id', 'created_at']);
        });

        Schema::table('scenes', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['amount', 'created_at']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['plan_id', 'created_at']);
        });
    }
};
