<?php

namespace BCC\CronManagerBundle;

use BCC\CronManagerBundle\DependencyInjection\Compiler\CronCommandCompilerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\HttpKernel\KernelInterface;

class BCCCronManagerBundle extends Bundle
{
    public function __construct(KernelInterface $kernel){
        $this->kernel = $kernel;
    }

    public function build(ContainerBuilder $container){
        parent::build($container);

        $container->addCompilerPass(new CronCommandCompilerPass($this->kernel));
    }

}
