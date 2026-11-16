<?php

namespace App\Shared\Infrastructure\Checker;

use App\Shared\Infrastructure\Dto\HealthCheckDto;

readonly class HealthChecker
{
    public function __construct(
        private PostgresChecker $postgresChecker,
        private RedisChecker    $redisChecker,
        private RabbitMQChecker $rabbitMqChecker,
    ) {
    }

    public function check(): HealthCheckDto
    {
        $postgres = $this->postgresChecker->check();
        $redis = $this->redisChecker->check();
        $rabbitmq = $this->rabbitMqChecker->check();

        $status = in_array(HealthCheckDto::STATUS_FAIL, [$postgres, $redis, $rabbitmq], true)
            ? HealthCheckDto::STATUS_FAIL
            : HealthCheckDto::STATUS_OK;

        return new HealthCheckDto($status, $postgres, $redis, $rabbitmq);
    }
}
