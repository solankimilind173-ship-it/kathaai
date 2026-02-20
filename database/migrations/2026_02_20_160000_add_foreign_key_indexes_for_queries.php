<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add composite indexes for common query patterns (FK + filter).
     */
    public function up(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->index(['project_id', 'type']);
        });

        Schema::table('render_logs', function (Blueprint $table) {
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'type']);
        });

        Schema::table('render_logs', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'status']);
        });
    }
};
