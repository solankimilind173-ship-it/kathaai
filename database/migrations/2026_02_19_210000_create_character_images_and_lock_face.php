<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('image_url', 500);
            $table->boolean('is_selected')->default(false);
            $table->timestamps();

            $table->index(['character_id', 'is_selected']);
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->string('locked_face', 500)->nullable()->after('description');
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->foreignId('selected_image_id')->nullable()->after('locked_face')->constrained('character_images')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropForeign(['selected_image_id']);
            $table->dropColumn(['locked_face', 'selected_image_id']);
        });

        Schema::dropIfExists('character_images');
    }
};
