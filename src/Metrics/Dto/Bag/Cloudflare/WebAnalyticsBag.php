<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag;

class WebAnalyticsBag extends Bag
{
    public int $from {
        get => $this->getInt('from', 0) ?? 0;
    }

    public int $to {
        get => $this->getInt('to', 0) ?? 0;
    }

    public int $pageViews {
        get => $this->getInt('page_views', 0) ?? 0;
    }

    public int $visits {
        get => $this->getInt('visits', 0) ?? 0;
    }

    /** @var array<string, int> */
    public array $byHost {
        get => is_array($this->get('by_host')) ? array_map('intval', $this->all('by_host')) : [];
    }

    public ?float $lcpP75Ms {
        get => $this->vitals->getFloat('lcp_p75_ms');
    }

    public ?float $inpP75Ms {
        get => $this->vitals->getFloat('inp_p75_ms');
    }

    public ?float $clsP75 {
        get => $this->vitals->getFloat('cls_p75');
    }

    public ?float $ttfbP75Ms {
        get => $this->vitals->getFloat('ttfb_p75_ms');
    }

    public ?float $fcpP75Ms {
        get => $this->vitals->getFloat('fcp_p75_ms');
    }

    public ?float $pageLoadP50Ms {
        get => $this->timings->getFloat('page_load_p50_ms');
    }

    public ?float $pageLoadP75Ms {
        get => $this->timings->getFloat('page_load_p75_ms');
    }

    public ?float $dnsP75Ms {
        get => $this->timings->getFloat('dns_p75_ms');
    }

    public ?float $connectionP75Ms {
        get => $this->timings->getFloat('connection_p75_ms');
    }

    public ?float $requestP75Ms {
        get => $this->timings->getFloat('request_p75_ms');
    }

    public ?float $responseP75Ms {
        get => $this->timings->getFloat('response_p75_ms');
    }

    public ?float $pageRenderP75Ms {
        get => $this->timings->getFloat('page_render_p75_ms');
    }

    private Bag $vitals {
        get => $this->vitals ??= new Bag(is_array($this->get('vitals')) ? $this->all('vitals') : []);
    }

    private Bag $timings {
        get => $this->timings ??= new Bag(is_array($this->get('timings')) ? $this->all('timings') : []);
    }

    public function vital(WebVital $vital): ?float
    {
        return match ($vital) {
            WebVital::Lcp => $this->lcpP75Ms,
            WebVital::Inp => $this->inpP75Ms,
            WebVital::Cls => $this->clsP75,
            WebVital::Ttfb => $this->ttfbP75Ms,
        };
    }

    public function rating(WebVital $vital): ?VitalRating
    {
        return $vital->rate($this->vital($vital));
    }
}
