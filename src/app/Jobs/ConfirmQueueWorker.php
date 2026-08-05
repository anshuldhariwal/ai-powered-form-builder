<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Redis;

class ConfirmQueueWorker implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    public function handle(): void
    {
        Redis::setex(self::key($this->token), 60, 'processed');
    }

    public static function key(string $token): string
    {
        return "formforge:queue-smoke:{$token}";
    }
}
