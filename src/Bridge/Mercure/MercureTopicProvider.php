<?php

declare(strict_types=1);

namespace App\Bridge\Mercure;

use App\Entity\Project;
use App\Project\ProjectContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\HubInterface;
use Twig\Attribute\AsTwigFunction;

readonly class MercureTopicProvider
{
    public function __construct(
        #[Autowire(env: 'APP_DOMAIN')]
        private string $domain,
        private ProjectContext $projectContext,
        private HubInterface $hub,
        private Authorization $authorization,
        private RequestStack $requestStack,
        private LoggerInterface $logger,
    ) {}

    /**
     * Topic published server-side (see AutoRefreshListener).
     */
    #[AsTwigFunction('consumed_metric_topic')]
    public function getConsumedMetricUrl(?Project $project = null): string
    {
        $project ??= $this->projectContext->getCurrentProject();

        return sprintf('https://%s/metrics/consumed/%s', $this->domain, $project->getUuid());
    }

    /**
     * EventSource subscription URL for the current project.
     * Also sets a Mercure authorization cookie (subscriber JWT) scoped to this project's topic.
     */
    #[AsTwigFunction('consumed_metric_subscribe_url')]
    public function getConsumedMetricSubscribeUrl(?Project $project = null): string
    {
        $project ??= $this->projectContext->getCurrentProject();
        $topic = $this->getConsumedMetricUrl($project);

        $this->authorizeSubscription($topic);

        return $this->hub->getPublicUrl() . '?topic=' . rawurlencode($topic);
    }

    private function authorizeSubscription(string $topic): void
    {
        $request = $this->requestStack->getMainRequest();

        if ($request === null) {
            return;
        }

        try {
            // No publish right is granted (publish = []), only the subscription to this topic.
            $this->authorization->setCookie($request, [$topic]);
        } catch (\RuntimeException $e) {
            // Cookie already set on this request (double render) or hub on a different
            // domain (dev config): not blocking for the page rendering.
            $this->logger->warning('Unable to set Mercure subscriber authorization cookie', ['exception' => $e]);
        }
    }
}
