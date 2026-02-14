<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
    public function handle()
    {
        \App\Models\Episode::truncate();
        $this->info('Episodes cleared!');
    }
}
