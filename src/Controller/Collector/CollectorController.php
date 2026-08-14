<?php

declare(strict_types=1);

namespace App\Controller\Collector;

use App\Collector\CollectorRateLimiterProvider;
use App\Message\MetricsReceivedMessage;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class CollectorController extends AbstractController
{
    // far above the number of collectors a single agent can send in one batch
    private const MAX_BATCH_SIZE = 50;

    // Agents pace their loop on X-RateLimit-Retry-After: never advertise less
    // than this on an accepted push (the self-hosted no_limit policy yields 0).
    private const int MIN_RETRY_AFTER_SECONDS = 10;

    #[Route('/metrics', name: 'collector', methods: ['POST'])]
    public function collectMetrics(Request $request, MessageBusInterface $bus, ProjectRepository $projectRepository, CollectorRateLimiterProvider $rateLimiterProvider): Response
    {
        $apiKey = $request->headers->get('x-jmonitor-api-key');
        $jmonitorVersion = $request->headers->get('x-jmonitor-version');
        // Only sent by agents running a framework integration; absent means there is none.
        $bundleVersion = $request->headers->get('x-jmonitor-bundle-version');

        if (!$apiKey) {
            return new JsonResponse([
                'error' => 'Missing API key',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$jmonitorVersion) {
            return new JsonResponse([
                'error' => 'Malformed request',
            ], Response::HTTP_BAD_REQUEST);
        }

        $project = $projectRepository->findOneBy(['apiKey' => $apiKey]);

        if (!$project) {
            return new JsonResponse([
                'error' => 'Project not found for the provided API key',
            ], Response::HTTP_FORBIDDEN);
        }

        $rateLimiter = $rateLimiterProvider->getRateLimiterFactory($project)->create('COLLECTOR_' . $project->getId());
        $limit = $rateLimiter->consume();

        $headers = [
            'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
            'X-RateLimit-Retry-After' => $limit->getRetryAfter()->getTimestamp() - time(),
            'X-RateLimit-Limit' => $limit->getLimit(),
        ];

        if (!$limit->isAccepted()) {
            return new Response(null, Response::HTTP_TOO_MANY_REQUESTS, $headers);
        }

        // check for the next token: on success Retry-After is 0 even when the remaining tokens are also 0
        $limit = $rateLimiter->consume(0);
        $headers['X-RateLimit-Retry-After'] = max($limit->getRetryAfter()->getTimestamp() - time(), self::MIN_RETRY_AFTER_SECONDS);

        try {
            $batch = $request->toArray();
        } catch (JsonException $e) {
            throw new BadRequestHttpException(previous: $e);
        }

        // structural validation only, each entry is validated in depth when consumed (see Consumer::consume())
        $isValidBatch = $batch !== []
            && count($batch) <= self::MAX_BATCH_SIZE
            && array_is_list($batch)
            && array_all($batch, static fn($entry): bool => is_array($entry));

        if (!$isValidBatch) {
            return new JsonResponse([
                'error' => 'Invalid payload, expected a non-empty list of collector entries',
            ], Response::HTTP_BAD_REQUEST, $headers);
        }

        $bus->dispatch(new MetricsReceivedMessage($project->getId(), $batch, $jmonitorVersion, $bundleVersion));

        return new JsonResponse(status: Response::HTTP_ACCEPTED, headers: $headers);
    }
}
