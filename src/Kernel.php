<?php

namespace App;

use App\DependencyInjection\CloudOnlyScheduledTasksPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        // Priority: must run before the scheduler pass, which reads the tags this one removes.
        $container->addCompilerPass(new CloudOnlyScheduledTasksPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
    }
}
