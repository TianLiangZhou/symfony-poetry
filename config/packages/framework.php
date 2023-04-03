<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\FrameworkConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (FrameworkConfig $frameworkConfig,  ContainerConfigurator $container) {

    $sessionConfig = $frameworkConfig->secret(env('APP_SECRET'))
        ->httpMethodOverride(false)
        ->session();
    $sessionConfig->handlerId(null)
        ->cookieSecure('auto')
        ->cookieSamesite('lax')
        ->storageFactoryId('session.storage.factory.native');

    $frameworkConfig->phpErrors()
        ->log();

    $frameworkConfig->csrfProtection()
        ->enabled(false);

    if ($container->env() === 'test') {
        $frameworkConfig->test(true);
        $sessionConfig->storageFactoryId('session.storage.factory.mock_file');
    }
    $assetsConfig = $frameworkConfig->assets();
    $assetsConfig->baseUrls(env('ASSETS_URL'));
    $assetsConfig->version(OctopusPress\Bundle\OctopusPressKernel::OCTOPUS_PRESS_VERSION);
    $assetsConfig->versionFormat("%%s?v=%%s");

    $frameworkConfig->httpClient()
        ->maxHostConnections(3)
    ;
};
