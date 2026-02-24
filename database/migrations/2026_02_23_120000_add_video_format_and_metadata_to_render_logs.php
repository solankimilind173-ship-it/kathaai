<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('render_logs', function (Blueprint $table) {
            $table->string('video_format', 32)->nullable()->after('status');
            $table->string('thumbnail_url', 500)->nullable()->after('output_url');
            $table->string('video_title', 255)->nullable()->after('thumbnail_url');
            $table->text('video_description')->nullable()->after('video_title');
            $table->string('hashtags', 500)->nullable()->after('video_description');
        });
    }

    public function down(): void
    {
        Schema::table('render_logs', function (Blueprint $table) {
            $table->dropColumn([
                'video_format',
                'thumbnail_url',
                'video_title',
                'video_description',
                'hashtags',
            ]);
        });
    }
};
