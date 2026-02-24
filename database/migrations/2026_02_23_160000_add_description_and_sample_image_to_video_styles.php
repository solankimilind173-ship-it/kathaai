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
        Schema::table('video_styles', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('sample_image_url', 512)->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_styles', function (Blueprint $table) {
            $table->dropColumn(['description', 'sample_image_url']);
        });
    }
};
