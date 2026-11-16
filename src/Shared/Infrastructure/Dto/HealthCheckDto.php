<?php

namespace App\Shared\Infrastructure\Dto;

class HealthCheckDto
{
    public const STATUS_OK = 'ok';
    public const STATUS_FAIL = 'fail';

    public function __construct(
        public readonly string $status,
        public readonly string $postgres,
        public readonly string $redis,
        public readonly string $rabbitmq,
    ) {
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'checks' => [
                'postgres' => $this->postgres,
                'redis' => $this->redis,
                'rabbitmq' => $this->rabbitmq,
            ],
        ];
    }
}
