<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications_log', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64)->index(); // welcome, password_changed, project_step
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient', 255);
            $table->string('subject', 255)->nullable();
            $table->string('step_name', 128)->nullable(); // for project steps
            $table->unsignedBigInteger('project_id')->nullable();
            $table->json('meta')->nullable(); // location, device_id, etc.
            $table->timestamp('sent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_log');
    }
};
