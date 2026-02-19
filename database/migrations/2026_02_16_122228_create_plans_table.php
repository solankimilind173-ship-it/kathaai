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
        Schema::create('plans', function (Blueprint $table) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->decimal('price', 10, 2);

                // Limits
                $table->integer('max_projects')->nullable();
                $table->integer('max_dubbing_languages')->default(1);
                $table->integer('max_reels_per_episode')->default(1);
                $table->integer('max_video_minutes')->default(5);

                // Feature Flags
                $table->boolean('allow_multiple_video_styles')->default(false);
                $table->boolean('allow_4k')->default(false);
                $table->boolean('allow_voice_style_selection')->default(false);
                $table->boolean('allow_background_music')->default(false);
                $table->boolean('allow_intro_song_generation')->default(false);

                // Credits
                $table->integer('monthly_credits')->default(100);

                $table->boolean('is_active')->default(true);

                $table->timestamps();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
