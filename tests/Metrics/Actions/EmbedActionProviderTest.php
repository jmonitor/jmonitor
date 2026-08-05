<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Actions;

use App\AutoRefresh\AutoRefreshContext;
use App\Entity\Project;
use App\Metrics\Actions\EmbedActionProvider;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use App\Project\ProjectContext;
use App\Range\Dto\RangeDto;
use App\Range\RangeContext;
use PHPUnit\Framework\TestCase;

class EmbedActionProviderTest extends TestCase
{
    /**
     * The "Embed" entry of a per-handler / per-database / per-worker card must carry the
     * option identifying it, or the sidebar renders the metric without it and blows up.
     */
    public function testTheEmbedLinkCarriesTheCardsMetricOptions(): void
    {
        $action = $this->provider()->getDefaultEmbedAction(Metric::CaddyReqPerSec, Renderer::Line, ['handler' => 'php']);

        $this->assertSame(['handler' => 'php'], $action->routeParams['embed']['o'] ?? null);
    }

    public function testAnEmbedLinkWithoutMetricOptionsHasNoOptionsKey(): void
    {
        $action = $this->provider()->getDefaultEmbedAction(Metric::SystemCpuUsage);

        $this->assertArrayNotHasKey('o', $action->routeParams['embed']);
    }

    private function provider(): EmbedActionProvider
    {
        $rangeContext = $this->createMock(RangeContext::class);
        $rangeContext->method('getRangeDto')->willReturn(new RangeDto());

        $autoRefreshContext = $this->createMock(AutoRefreshContext::class);
        $autoRefreshContext->method('isAutoRefresh')->willReturn(false);

        $projectContext = $this->createMock(ProjectContext::class);
        $projectContext->method('getCurrentProject')->willReturn(new Project());

        return new EmbedActionProvider($rangeContext, $autoRefreshContext, $projectContext);
    }
}
