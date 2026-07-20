<?php

declare(strict_types=1);

namespace App\Controller\Dash;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

class AppController extends AbstractController
{
    #[Route('/site.webmanifest', name: 'app.manifest')]
    #[Cache(maxage: 60, public: true, mustRevalidate: true)]
    public function webManifest(Packages $assets, Request $request): JsonResponse
    {
        $content = [
            'name' => 'Jmonitor',
            'short_name' => 'Jmonit',
            'icons' => [
                [
                    'src' => $assets->getUrl('im/favicons/web-app-manifest-192x192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
                [
                    'src' => $assets->getUrl('im/favicons/web-app-manifest-512x512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                ],
            ],
            'theme_color' => '#ffffff',
            'background_color' => '#ffffff',
            'display' => 'standalone',
        ];

        $etag = md5(json_encode($content));


        $response =  new JsonResponse($content, 200, ['ETag' => $etag]);
        $response->isNotModified($request);

        return $response;
    }
}
