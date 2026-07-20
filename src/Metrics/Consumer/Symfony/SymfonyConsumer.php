<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Symfony;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\Dto\MetricBagDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;

/**
 */
#[AsTaggedItem(Consumer::SYMFONY->value)]
class SymfonyConsumer implements ConsumerInterface
{
    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        return $bag;
    }

    public function getMetricsToCache(MetricBagDto $bag): array
    {
        return $bag->all();
    }

    public function getInfluxPoints(MetricBagDto $bag): array
    {
        return [];
    }

    public function getConstraints(int $version): Constraint|array
    {
        return new Collection(
            fields: [
                'version' => [new Assert\Type('string'), new Assert\Length(max: 10)],
                'env' => [new Assert\Type('string'), new Assert\Length(max: 100)],
                'debug' => [new Assert\Type('boolean')],
                'bundles' => [new Assert\Type('array')],
                'project_dir' => new Assert\Type('string'),
                'cache_dir' => new Assert\Type('string'),
                'log_dir' => new Assert\Type('string'),
                'build_dir' => new Assert\Type('string'),
                'share_dir' => new Assert\Type('string'),
                'charset' => new Assert\Type('string'),
                'components' => [
                    new Assert\Type('array'),
                    new Assert\Collection(
                        fields: [
                            'scheduler' => [
                                new Assert\Type('array'),
                                new Assert\All(constraints: [
                                    new Assert\Collection(
                                        fields: [
                                            'trigger' => [new Assert\NotBlank(), new Assert\Type('string')],
                                            'command' => [new Assert\NotBlank(), new Assert\Type('string')],
                                            'next_run' => [new Assert\NotBlank(), new Assert\Type('integer')],
                                            'description' => [new Assert\Optional(constraints: [new Assert\Type('string')])],
                                        ],
                                        allowMissingFields: false,
                                    ),
                                ]),
                            ],
                            'flex_recipes' => [
                                new Assert\Type('array'),
                                new Assert\Collection(
                                    fields: [
                                        'up_to_date' => [new Assert\NotNull(), new Assert\Type('boolean')],
                                        'outdated_recipes' => [
                                            new Assert\Optional([
                                                new Assert\Type('array'),
                                                new Assert\All(constraints: [new Assert\Type('string')]),
                                            ]),
                                        ],
                                    ],
                                    allowMissingFields: false,
                                ),
                            ],
                            'messenger' => [new Assert\Type('array')],
                        ],
                        allowMissingFields: true,
                    ),
                ],
            ],
            allowMissingFields: true,
        );
    }
}
