<?php

namespace App\Console\Commands;

use App\Models\Episode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ClearEpisodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-episodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Schema::withoutForeignKeyConstraints(function (): void {
            Episode::query()->delete();
        });

        $this->info('Episodes cleared!');

        return self::SUCCESS;
    }
}
