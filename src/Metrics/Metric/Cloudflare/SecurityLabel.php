<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

/**
 * Display names of the raw action and source identifiers of Cloudflare security events.
 */
final class SecurityLabel
{
    private const array ACTIONS = [
        'block' => 'Block',
        'managed_challenge' => 'Managed challenge',
        'js_challenge' => 'JS challenge',
        'challenge' => 'Interactive challenge',
        'skip' => 'Skip',
        'log' => 'Log',
        'allow' => 'Allow',
    ];

    private const array SOURCES = [
        'firewallcustom' => 'Custom rules',
        'firewallmanaged' => 'Managed rules',
        'firewallrules' => 'Firewall rules',
        'ratelimit' => 'Rate limiting',
        'botfight' => 'Bot fight mode',
        'botmanagement' => 'Bot management',
        'bic' => 'Browser integrity check',
        'hot' => 'Hotlink protection',
        'l7ddos' => 'HTTP DDoS protection',
        'securitylevel' => 'Security level',
        'zonelockdown' => 'Zone lockdown',
        'uablock' => 'User agent blocking',
        'waf' => 'WAF',
        'ip' => 'IP access rules',
        'iprange' => 'IP access rules (range)',
        'asn' => 'IP access rules (ASN)',
        'country' => 'IP access rules (country)',
        'validation' => 'Request validation',
        'apishield' => 'API Shield',
    ];

    public static function action(string $action): string
    {
        return self::ACTIONS[strtolower($action)] ?? ucfirst(str_replace('_', ' ', $action));
    }

    public static function source(string $source): string
    {
        return self::SOURCES[strtolower($source)] ?? $source;
    }
}
