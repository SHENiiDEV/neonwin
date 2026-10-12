<?php

namespace App\Console\Commands;

use App\Services\FlexrixService;
use Illuminate\Console\Command;

class FlexrixSyncCommand extends Command
{
    protected $signature = 'flexrix:sync';

    protected $description = 'Import the complete Flexrix catalogue without deleting historical games';

    public function handle(FlexrixService $flexrix): int
    {
        if (! $flexrix->configured('casino')) {
            $this->error('Set FLEXRIX_CASINO_MERCHANT_KEY and FLEXRIX_CASINO_API_SECRET before importing games.');

            return self::FAILURE;
        }
        $result = $flexrix->sync();
        if ($result['errors']) {
            $this->error('Flexrix sync failed. Check credentials, API availability and whitelist. The previous catalogue was retained.');

            return self::FAILURE;
        }
        $this->info('Imported '.$result['games'].' games from '.$result['providers'].' providers.');

        return self::SUCCESS;
    }
}
