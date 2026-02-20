<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->string('action_type', 64)->nullable()->after('project_id');
            $table->unsignedInteger('credits')->nullable()->after('action_type');
        });

        // Backfill from existing usage rows: action_type = feature, credits = ABS(amount)
        \DB::table('credit_transactions')
            ->where('type', 'usage')
            ->where('amount', '<', 0)
            ->update([
                'action_type' => \DB::raw('COALESCE(`feature`, "usage")'),
                'credits' => \DB::raw('ABS(amount)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropColumn(['action_type', 'credits']);
        });
    }
};
