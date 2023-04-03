<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator, ContainerBuilder $builder) {
    if ($configurator->env() === 'prod') {
        $configurator->parameters()->set('container.dumper.inline_factories', true);
    }
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();
    $services->load("App\\", "../src/*")
         ->exclude('../src/{DependencyInjection,Entity,Tests,Kernel.php}');
};
