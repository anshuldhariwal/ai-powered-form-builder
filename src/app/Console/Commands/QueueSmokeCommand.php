<?php

namespace App\Console\Commands;

use App\Jobs\ConfirmQueueWorker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class QueueSmokeCommand extends Command
{
    protected $signature = 'queue:smoke {--timeout=15 : Seconds to wait for Horizon}';

    protected $description = 'Dispatch a job and verify that a queue worker processes it';

    public function handle(): int
    {
        $timeout = filter_var($this->option('timeout'), FILTER_VALIDATE_INT);

        if ($timeout === false || $timeout < 1 || $timeout > 60) {
            $this->error('The timeout must be an integer between 1 and 60 seconds.');

            return self::INVALID;
        }

        $token = (string) Str::uuid();
        $key = ConfirmQueueWorker::key($token);

        Redis::del($key);
        ConfirmQueueWorker::dispatch($token);

        $deadline = microtime(true) + $timeout;

        do {
            if (Redis::get($key) === 'processed') {
                Redis::del($key);
                $this->info('Queue worker processed the smoke-test job.');

                return self::SUCCESS;
            }

            usleep(100_000);
        } while (microtime(true) < $deadline);

        Redis::del($key);
        $this->error("Queue worker did not process the smoke-test job within {$timeout} seconds.");

        return self::FAILURE;
    }
}
