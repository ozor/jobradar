<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Checker;

use App\Shared\Infrastructure\Dto\HealthCheckDto;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

readonly class PostgresChecker
{
    public function __construct(
        private Connection $postgres,
        private LoggerInterface $logger,
    ) {
    }

    public function check(): string
    {
        try {
            $check = '1' === (string) $this->postgres->fetchOne('SELECT 1');

            return $check ? HealthCheckDto::STATUS_OK : HealthCheckDto::STATUS_FAIL;
        } catch (\Throwable $e) {
            $this->logger->warning('Health check failed', [
                'service' => 'postgres',
                'exception' => $e,
            ]);

            return HealthCheckDto::STATUS_FAIL;
        }
    }
}
