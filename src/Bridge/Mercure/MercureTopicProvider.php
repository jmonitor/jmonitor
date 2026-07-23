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

    /**
     * EventSource subscription URL for public embed pages (no session): the subscriber
     * JWT rides in the `authorization` query parameter instead of a cookie, because
     * cookies are not sent from cross-site iframes.
     */
    #[AsTwigFunction('consumed_metric_public_subscribe_url')]
    public function getPublicConsumedMetricSubscribeUrl(?Project $project = null): string
    {
        $project ??= $this->projectContext->getCurrentProject();
        $topic = $this->getConsumedMetricUrl($project);

        $url = $this->hub->getPublicUrl() . '?topic=' . rawurlencode($topic);

        // Subscribe-only, single-topic, expiring token. The page re-mints one on each
        // (auto-)refresh, so a short lifetime does not break long-running widgets.
        $jwt = $this->hub->getFactory()?->create(
            subscribe: [$topic],
            publish: [],
            additionalClaims: ['exp' => new \DateTimeImmutable('+1 day')],
        );

        if ($jwt !== null) {
            $url .= '&authorization=' . rawurlencode($jwt);
        }

        return $url;
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
