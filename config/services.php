<?php

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

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
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();
    $services->load("App\\", "../src/*")
         ->exclude('../src/{DependencyInjection,Entity,Tests,Kernel.php}');
    $container->register('app.redis.provider', \Redis::class)
        ->setFactory([RedisAdapter::class, 'createConnection'])
        ->addArgument('redis://%env(REDIS_HOST)%:%env(int:REDIS_PORT)%')
        ->addArgument([
            'retry_interval' => 2,
            'timeout' => 10
        ]);
};
