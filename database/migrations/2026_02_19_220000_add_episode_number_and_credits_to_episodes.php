<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->unsignedInteger('episode_number')->default(1)->after('title');
            $table->unsignedBigInteger('total_credits_used')->default(0)->after('status');
        });

        // Backfill episode_number by project and id order (MySQL 5.7 compatible)
        $projects = DB::table('episodes')->distinct()->pluck('project_id');
        foreach ($projects as $projectId) {
            $ids = DB::table('episodes')->where('project_id', $projectId)->orderBy('id')->pluck('id');
            foreach ($ids as $i => $id) {
                DB::table('episodes')->where('id', $id)->update(['episode_number' => $i + 1]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn(['episode_number', 'total_credits_used']);
        });
    }
};
