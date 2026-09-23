<?php

namespace App\Console\Commands;

use App\Services\NexusGgrService;
use Illuminate\Console\Command;

class NexusSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nexus:sync {--provider= : Specific provider code (e.g. PRAGMATIC)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync games and providers from NexusGGR aggregator API';

    /**
     * Execute the console command.
     */
    public function handle(NexusGgrService $service): int
    {
        $provider = $this->option('provider');
        $this->info('Starting NexusGGR synchronization'.($provider ? " for provider: [{$provider}]" : ' for all providers').'...');

        $startTime = microtime(true);
        $result = $service->syncGames($provider);
        $duration = round(microtime(true) - $startTime, 2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Providers Processed', $result['providers_synced']],
                ['Games Created', $result['games_created']],
                ['Games Updated', $result['games_updated']],
                ['Errors', $result['errors']],
                ['Execution Time', "{$duration}s"],
            ]
        );

        $this->info('NexusGGR synchronization completed successfully!');

        return Command::SUCCESS;
    }
}
