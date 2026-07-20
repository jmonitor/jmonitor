<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // https://getrector.com/documentation/config-configuration#content-nicer-fluent-call-output
    ->withFluentCallNewLine()
    // ->withPhpSets()
    ->withImportNames(
        importShortClasses: false,
        removeUnusedImports: true,
    )
    ->withComposerBased(symfony: true)
//    ->withSymfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml')
    ->withSymfonyContainerPhp(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.php')
;
