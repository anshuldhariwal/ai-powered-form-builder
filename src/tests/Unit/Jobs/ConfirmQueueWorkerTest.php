<?php

use App\Jobs\ConfirmQueueWorker;
use Illuminate\Support\Facades\Redis;

test('the queue smoke job writes a short-lived completion marker', function () {
    Redis::shouldReceive('setex')
        ->once()
        ->with('formforge:queue-smoke:test-token', 60, 'processed');

    (new ConfirmQueueWorker('test-token'))->handle();
});
