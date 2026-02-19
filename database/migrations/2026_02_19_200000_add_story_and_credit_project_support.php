<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->longText('content')->nullable()->after('description');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->longText('story_source')->nullable()->after('description');
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('content');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('story_source');
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });
    }
};
