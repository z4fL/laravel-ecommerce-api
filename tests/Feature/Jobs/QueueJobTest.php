<?php

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

it('can connect to redis', function () {
    expect(Redis::ping("PONG"))->toBe('PONG');
});

it('uses redis as the queue connection', function () {
    expect(config('queue.default'))->toBe('redis');

    expect(Queue::connection())
        ->toBeInstanceOf(\Illuminate\Queue\RedisQueue::class);
});
