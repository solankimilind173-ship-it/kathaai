<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('video_minutes')->nullable()->after('status');
            $table->string('quality', 10)->nullable()->default('1080p')->after('video_minutes');
            $table->unsignedInteger('reels_per_episode')->nullable()->default(0)->after('quality');
            $table->boolean('intro_song')->default(false)->after('reels_per_episode');
            $table->boolean('background_music')->default(false)->after('intro_song');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'video_minutes',
                'quality',
                'reels_per_episode',
                'intro_song',
                'background_music',
            ]);
        });
    }
};
