<?php

declare(strict_types=1);

namespace App\Controller\ValueResolver;

use App\Controller\Attribute\MapEmbedDto;
use App\Metrics\Dto\EmbedDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class EmbedDtoValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $attribute = $argument->getAttributes(MapEmbedDto::class, ArgumentMetadata::IS_INSTANCEOF)[0] ?? null;

        if (!$attribute instanceof MapEmbedDto) {
            return [];
        }

        $data = $request->query->all($attribute->key);

        if ($data === []) {
            if ($argument->isNullable()) {
                return [null];
            }

            throw new BadRequestHttpException(sprintf('Missing "%s" query parameter.', $attribute->key));
        }

        try {
            return [EmbedDto::fromArray($data)];
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
    }
}
