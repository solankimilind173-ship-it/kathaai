<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Normalize legacy status before adding enum-backed usage
        DB::table('projects')->where('status', 'processing')->update(['status' => 'generating']);

        if (! Schema::hasColumn('projects', 'book_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->foreignId('book_id')->nullable()->after('user_id')->constrained('books')->nullOnDelete();
                $table->text('description')->nullable()->after('title');
                $table->string('source_type', 32)->default('uploaded')->after('description'); // library | uploaded
                $table->boolean('is_public')->default(false)->after('source_type');
                $table->unsignedBigInteger('total_credits_used')->default(0)->after('is_public');
            });
        }

        $driver = Schema::getConnection()->getDriverName();
        $existingNames = [];
        if ($driver === 'mysql') {
            $existingIndexes = DB::select('SHOW INDEX FROM projects');
            $existingNames = array_unique(array_column($existingIndexes, 'Key_name'));
        } elseif ($driver === 'sqlite') {
            $existingIndexes = DB::select("PRAGMA index_list(projects)");
            $existingNames = array_column($existingIndexes, 'name');
        }

        Schema::table('projects', function (Blueprint $table) use ($existingNames) {
            if (! in_array('projects_status_index', $existingNames, true)) {
                $table->index('status');
            }
            if (! in_array('projects_user_id_status_index', $existingNames, true)) {
                $table->index(['user_id', 'status']);
            }
            if (! in_array('projects_is_public_status_index', $existingNames, true)) {
                $table->index(['is_public', 'status']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['is_public', 'status']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['status']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->dropColumn([
                'book_id',
                'description',
                'source_type',
                'is_public',
                'total_credits_used',
            ]);
        });
    }
};
