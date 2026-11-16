<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Checker;

use App\Shared\Infrastructure\Dto\HealthCheckDto;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class RedisChecker
{
    public function __construct(
        #[Autowire('%env(REDIS_URL)%')]
        private string $redisDsn,
        private LoggerInterface $logger,
    ) {
    }

    public function check(): string
    {
        try {
            $redis = RedisAdapter::createConnection($this->redisDsn, [
                'timeout' => 1,
                'read_timeout' => 1,
            ]);

            return false !== $redis->ping()
                ? HealthCheckDto::STATUS_OK
                : HealthCheckDto::STATUS_FAIL;
        } catch (\Throwable $e) {
            $this->logger->warning('Health check failed', [
                'service' => 'redis',
                'exception' => $e,
            ]);

            return HealthCheckDto::STATUS_FAIL;
        }
    }
}
