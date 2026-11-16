<?php

namespace App\Shared\UI\Http;

use App\Shared\Infrastructure\Dto\HealthCheckDto;
use App\Shared\Infrastructure\Checker\HealthChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/health', name: 'health_check', methods: ['GET'])]
    public function get(
        HealthChecker $healthChecker,
    ): Response
    {
        $response = $healthChecker->check();
        $code = $response->status === HealthCheckDto::STATUS_OK
            ? Response::HTTP_OK
            : Response::HTTP_SERVICE_UNAVAILABLE;

        $jsonResponse = $this->json($response->toArray(), $code);
        $jsonResponse->headers->set('Cache-Control', 'no-store');

        return $jsonResponse;
    }
}
