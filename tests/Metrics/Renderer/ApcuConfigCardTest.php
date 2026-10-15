<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Renderer;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\MetricBagDto;
use App\Metrics\MetricsBagProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Environment;

class ApcuConfigCardTest extends KernelTestCase
{
    public function testSegmentsComeFromSmaInfo(): void
    {
        $lines = $this->renderCard([
            'apc.shm_size' => '32M',
            'apc.ttl' => 0,
        ], numSeg: 2);

        $this->assertSame('2', $lines['segments']);
        $this->assertArrayNotHasKey('shm_segments', $lines);
    }

    /**
     * Older collectors still send apc.shm_segments, which reflects php.ini rather than what APCu allocated.
     */
    public function testLegacyShmSegmentsDirectiveIsIgnored(): void
    {
        $lines = $this->renderCard([
            'apc.shm_size' => '32M',
            'apc.shm_segments' => 4,
            'apc.ttl' => 0,
        ], numSeg: 1);

        $this->assertSame('1', $lines['segments']);
        $this->assertArrayNotHasKey('shm_segments', $lines);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, string> label => value
     */
    private function renderCard(array $config, int $numSeg): array
    {
        self::bootKernel();

        $bag = MetricBagDto::create($this->createMock(Project::class), Consumer::PHP, 1, [
            'apcu' => [
                'config' => $config,
                'sma_info' => ['num_seg' => $numSeg, 'seg_size' => 33554432, 'avail_mem' => 1048576],
            ],
        ], new \DateTimeImmutable(), false);

        $bagProvider = $this->createMock(MetricsBagProvider::class);
        $bagProvider->method('getLastBag')->willReturn($bag);
        self::getContainer()->set(MetricsBagProvider::class, $bagProvider);

        $html = self::getContainer()->get(Environment::class)->render('dash/project/metrics/basic/custom/php.apcu_config.html.twig');

        $lines = [];
        new Crawler($html)->filter('.dldt')->each(function (Crawler $line) use (&$lines): void {
            $lines[trim($line->filter('.dl')->text())] = trim($line->filter('.dt')->text());
        });

        return $lines;
    }
}
