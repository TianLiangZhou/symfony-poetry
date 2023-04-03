<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $router) {
    $router->import("../src/Controller", 'attribute');
};
