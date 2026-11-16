<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Checker;

use App\Shared\Infrastructure\Dto\HealthCheckDto;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class RabbitMQChecker
{
    public function __construct(
        #[Autowire('%env(MESSENGER_TRANSPORT_DSN)%')]
        private string $rabbitDsn,
        private LoggerInterface $logger,
    ) {
    }

    public function check(): string
    {
        try {
            $parts = parse_url($this->rabbitDsn);

            if (!is_array($parts) || !isset($parts['host'])) {
                throw new \InvalidArgumentException('Invalid RabbitMQ DSN');
            }

            $path = explode('/', trim($parts['path'] ?? '', '/'));
            $amqp = new \AMQPConnection([
                'host' => $parts['host'],
                'port' => $parts['port'] ?? (($parts['scheme'] ?? 'amqp') === 'amqps' ? 5671 : 5672),
                'vhost' => rawurldecode($path[0]),
                'login' => rawurldecode($parts['user'] ?? 'guest'),
                'password' => rawurldecode($parts['pass'] ?? 'guest'),
                'connect_timeout' => 1,
                'read_timeout' => 1,
                'write_timeout' => 1,
            ]);

            $amqp->connect();

            return $amqp->isConnected()
                ? HealthCheckDto::STATUS_OK
                : HealthCheckDto::STATUS_FAIL;
        } catch (\Throwable $e) {
            $this->logger->warning('Health check failed', [
                'service' => 'rabbitmq',
                'exception' => $e,
            ]);

            return HealthCheckDto::STATUS_FAIL;
        } finally {
            if (isset($amqp) && $amqp->isConnected()) {
                $amqp->disconnect();
            }
        }
    }
}
