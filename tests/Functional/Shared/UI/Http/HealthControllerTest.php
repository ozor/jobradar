<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared\UI\Http;

use JsonException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use const JSON_THROW_ON_ERROR;

final class HealthControllerTest extends WebTestCase
{
    /**
     * @throws JsonException
     */
    public function testGetReturnsOkWithNestedChecks(): void
    {
        $client = self::createClient();
        $client->request('GET', '/health');

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $cacheControl = $client->getResponse()->headers->get('Cache-Control');
        self::assertIsString($cacheControl);
        self::assertStringContainsString('no-store', $cacheControl);

        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(
            [
                'status' => 'ok',
                'checks' => [
                    'postgres' => 'ok',
                    'redis' => 'ok',
                    'rabbitmq' => 'ok',
                ],
            ],
            $data,
        );
    }

    public function testPostIsNotAllowed(): void
    {
        $client = self::createClient();
        $client->request('POST', '/health');

        self::assertResponseStatusCodeSame(405);

        $allow = $client->getResponse()->headers->get('Allow');
        self::assertIsString($allow);
        self::assertStringContainsString('GET', $allow);
    }
}
