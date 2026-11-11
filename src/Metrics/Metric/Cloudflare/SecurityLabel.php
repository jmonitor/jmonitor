<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\SecurityActionKind;

/**
 * Display names of the raw action and source identifiers of Cloudflare security events.
 */
final class SecurityLabel
{
    public const string NO_EVENTS = 'No security events over the window.';

    private const array ACTIONS = [
        'block' => 'Block',
        'managedchallenge' => 'Managed challenge',
        'jschallenge' => 'JS challenge',
        'challenge' => 'Interactive challenge',
        'connectionclose' => 'Connection close',
        'forceconnectionclose' => 'Force connection close',
        'challengesolved' => 'Challenge solved',
        'challengebypassed' => 'Challenge bypassed',
        'jschallengesolved' => 'JS challenge solved',
        'jschallengebypassed' => 'JS challenge bypassed',
        'managedchallengenoninteractivesolved' => 'Managed challenge solved',
        'managedchallengeinteractivesolved' => 'Managed challenge solved (interactive)',
        'managedchallengebypassed' => 'Managed challenge bypassed',
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
        return self::ACTIONS[SecurityActionKind::normalize($action)]
            ?? ucfirst(strtolower((string) preg_replace('/(?<=[a-z])(?=[A-Z])|_/', ' ', $action)));
    }

    public static function source(string $source): string
    {
        return self::SOURCES[SecurityActionKind::normalize($source)] ?? $source;
    }
}
