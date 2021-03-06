<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Foundation\Console\OptimizeCommand as BaseOptimizeCommand;

/**
 * Extend the deploy command, to do stuff before the application goes live.
 */
class OptimizeCommand extends BaseOptimizeCommand
{
    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        // Create versioned pages
        $this->call('gumbo:update-content');
        $this->call('cloudflare:reload');

        // Forward
        parent::handle();

        // Purge caches
        if (config('cloudflare.token')) {
            $this->call('cloudflare:cache:purge');
        }
    }
}
