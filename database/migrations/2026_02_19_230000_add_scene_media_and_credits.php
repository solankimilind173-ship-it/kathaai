<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->string('image_url', 500)->nullable()->after('description');
            $table->string('voice_url', 500)->nullable()->after('image_url');
            $table->unsignedInteger('duration')->default(0)->after('voice_url'); // seconds
            $table->unsignedInteger('credits_used')->default(0)->after('duration');
            $table->string('status', 64)->default('pending')->after('credits_used');
            $table->string('camera_style', 64)->nullable()->after('status');
            $table->string('lighting', 64)->nullable()->after('camera_style');
        });
    }

    public function down(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->dropColumn([
                'image_url',
                'voice_url',
                'duration',
                'credits_used',
                'status',
                'camera_style',
                'lighting',
            ]);
        });
    }
};
