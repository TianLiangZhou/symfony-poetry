<?php

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler;

return function (ContainerConfigurator $configurator, ContainerBuilder $container) {
    if ($configurator->env() === 'prod') {
        $configurator->parameters()->set('container.dumper.inline_factories', true);
    }
    $container->register('Redis', \Redis::class)
        ->addMethodCall('connect', ['%env(REDIS_HOST)%', '%env(int:REDIS_PORT)%']);
    $container
        ->register(RedisSessionHandler::class)
        ->setArguments([
            new Reference('Redis'),
            ['prefix' => 'sess:', 'ttl' => 3600],
        ]);

    $container->register(RedisAdapter::class)
        ->addArgument(
            new Reference('Redis'),
        );
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();
    $services->load("App\\", "../src/*")
         ->exclude('../src/{DependencyInjection,Entity,Tests,Kernel.php}');
};
