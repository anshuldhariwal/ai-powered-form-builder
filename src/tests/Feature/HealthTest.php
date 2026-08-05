<?php

use Illuminate\Support\Facades\Redis;

test('readiness succeeds when MySQL and Redis are available', function () {
    config(['cache.default' => 'redis']);
    $connection = Mockery::mock();
    $connection->shouldReceive('ping')->once()->andReturn(true);
    Redis::shouldReceive('connection')->once()->andReturn($connection);

    $this->getJson('/health')
        ->assertOk()
        ->assertExactJson([
            'status' => 'ok',
            'checks' => [
                'mysql' => 'ok',
                'redis' => 'ok',
            ],
        ]);
});

test('readiness fails without exposing details when Redis is unavailable', function () {
    config(['cache.default' => 'redis']);
    Redis::shouldReceive('connection')->once()->andThrow(new RuntimeException('sensitive connection details'));

    $this->getJson('/health')
        ->assertServiceUnavailable()
        ->assertExactJson([
            'status' => 'degraded',
            'checks' => [
                'mysql' => 'ok',
                'redis' => 'unavailable',
            ],
        ])
        ->assertDontSee('sensitive connection details');
});

test('readiness skips Redis when no application subsystem uses it', function () {
    config(['cache.default' => 'array', 'queue.default' => 'sync', 'session.driver' => 'array']);
    Redis::shouldReceive('connection')->never();

    $this->getJson('/health')->assertOk()->assertExactJson([
        'status' => 'ok',
        'checks' => ['mysql' => 'ok'],
    ]);
});

test('the shallow liveness endpoint remains available', function () {
    $this->getJson('/up')->assertOk();
});
