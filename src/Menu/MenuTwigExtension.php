<?php

declare(strict_types=1);

namespace App\Menu;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Attribute\AsTwigFunction;
use Twig\Environment;

class MenuTwigExtension
{
    public function __construct(
        private readonly MenuManager $menuManager,
        private readonly CacheInterface $cache,
        #[Autowire(param: 'menu.default_cache_tag')]
        private readonly ?string $defaultCacheTag = null,
    ) {}

    /**
     * @param mixed[] $options
     */
    #[AsTwigFunction('renderMenu', needsEnvironment: true, isSafe: ['html'])]
    public function renderMenu(Environment $twig, string $name, ?string $template = null, array $options = []): string
    {
        $menu = $this->menuManager->getMenu($name);

        $optionResolver = new OptionsResolver();
        $menu->configureOptions($optionResolver);
        $options = $optionResolver->resolve($options);

        // TODO a CachableMenuInterface ?
        $cacheKey = $menu->getCacheKey($options);
        $cacheTag = $this->defaultCacheTag; // or provided by the menu itself

        if ($cacheKey) {
            $links = $this->cache->get($cacheKey, function (ItemInterface $item) use ($menu, $options, $cacheTag): iterable {
                $item->expiresAfter(24 * 3600);
                $cacheTag && $item->tag($cacheTag);

                return $menu->getLinks($options);
            });
        } else {
            $links = $menu->getLinks($options);
        }

        return $twig->render($template ?: $menu->getDefaultTemplate(), [
            'links' => $links,
            'options' => $options,
        ]);
    }
}
