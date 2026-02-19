<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->decimal('yearly_price', 10, 2)->nullable()->after('price');
            $table->boolean('credit_rollover')->default(false)->after('monthly_credits');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['yearly_price', 'credit_rollover']);
        });
    }
};
