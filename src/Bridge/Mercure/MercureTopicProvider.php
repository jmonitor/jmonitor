<?php

declare(strict_types=1);

namespace App\Bridge\Mercure;

use App\Entity\Project;
use App\Project\ProjectContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mercure\Authorization;
use Twig\Attribute\AsTwigFunction;

readonly class MercureTopicProvider
{
    public function __construct(
        #[Autowire(env: 'APP_DOMAIN')]
        private string $domain,
        private ProjectContext $projectContext,
        private Authorization $authorization,
        private RequestStack $requestStack,
        private LoggerInterface $logger,
    ) {}

    /**
     * Project-wide topic published server-side (see AutoRefreshListener). Carries the
     * full list of components updated in a push; only authenticated project members
     * subscribe to it.
     */
    #[AsTwigFunction('consumed_metric_topic')]
    public function getConsumedMetricUrl(?Project $project = null): string
    {
        $project ??= $this->projectContext->getCurrentProject();

        return sprintf('https://%s/metrics/consumed/%s', $this->domain, $project->getUuid());
    }

    /**
     * Per-component sub-topic, also published by AutoRefreshListener with a single-component
     * payload. Public embeds subscribe here instead of the project-wide topic, so a public
     * viewer never learns which other components the project monitors.
     */
    public function getConsumedMetricComponentUrl(Project $project, string $component): string
    {
        return $this->getConsumedMetricUrl($project) . '/' . rawurlencode($component);
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

        return $this->subscribeUrl($topic);
    }

    /**
     * EventSource subscription URL for public embed pages: anonymous, as cookies are not
     * sent from cross-site iframes. Their component sub-topic is published as a public
     * update, unlike the project-wide topic.
     */
    #[AsTwigFunction('consumed_metric_public_subscribe_url')]
    public function getPublicConsumedMetricSubscribeUrl(string $component, ?Project $project = null): string
    {
        $project ??= $this->projectContext->getCurrentProject();

        return $this->subscribeUrl($this->getConsumedMetricComponentUrl($project, $component));
    }

    /**
     * Root-relative on purpose: the hub is embedded in the app's own web server, so the
     * browser resolves this against the page it is on — same scheme, host and port, with
     * nothing to configure and no cross-origin request.
     */
    private function subscribeUrl(string $topic): string
    {
        return SameOriginHub::PATH . '?match=' . rawurlencode($topic);
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
            // Cookie already set on this request (double render): not blocking for the
            // page rendering.
            $this->logger->warning('Unable to set Mercure subscriber authorization cookie', ['exception' => $e]);
        }
    }
}
