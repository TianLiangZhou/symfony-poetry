<?php

use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework) {
    $cache = $framework->cache();
    $cache->app('cache.adapter.filesystem')
        ->system('cache.adapter.system')
        ->directory('%kernel.cache_dir%/pools')
    ;
    $cache->pool('doctrine.metadata_cache_driver')
        ->adapters('cache.app');
    $cache->pool('doctrine.system_cache_pool')
        ->adapters('cache.system');
    $cache->pool('doctrine.result_cache_pool')
        ->adapters('cache.system');
};
