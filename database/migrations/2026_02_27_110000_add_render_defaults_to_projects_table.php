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
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'default_video_format')) {
                $table->string('default_video_format', 32)->nullable()->after('video_frame');
            }
            if (! Schema::hasColumn('projects', 'default_fps')) {
                $table->unsignedTinyInteger('default_fps')->nullable()->after('default_video_format');
            }
            if (! Schema::hasColumn('projects', 'default_subtitle_style')) {
                $table->string('default_subtitle_style', 64)->nullable()->after('default_fps');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'default_subtitle_style')) {
                $table->dropColumn('default_subtitle_style');
            }
            if (Schema::hasColumn('projects', 'default_fps')) {
                $table->dropColumn('default_fps');
            }
            if (Schema::hasColumn('projects', 'default_video_format')) {
                $table->dropColumn('default_video_format');
            }
        });
    }
};

