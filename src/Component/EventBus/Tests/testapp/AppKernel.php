<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
        ];
    }

    public function getCacheDir()
    {
        return sprintf('/home/php/cache/%s/%s', get_class($this), $this->getEnvironment());
    }

    public function getLogDir()
    {
        return '/home/php/logs';
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/services.yml');
        $c->loadFromExtension('framework', ['secret' => 'S0ME_SECRET']);
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
    }
}
