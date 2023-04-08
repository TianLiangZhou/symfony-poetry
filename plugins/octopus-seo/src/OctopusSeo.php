<?php
namespace OctopusPress\Plugin\OctopusSeo;


use OctopusPress\Bundle\Bridge\Bridger;
use OctopusPress\Bundle\Plugin\Manifest;
use OctopusPress\Bundle\Plugin\PluginInterface;
use OctopusPress\Bundle\Plugin\PluginProviderInterface;

class OctopusSeo implements PluginInterface
{

    public static function manifest(): Manifest
    {
        // TODO: Implement manifest() method.
        return Manifest::builder()
            ->setName("SEO")
            ->addAuthor('OctopusPress.dev', 'https://octopuspress.dev')
            ->setDescription("可帮助您为站点提供搜索引擎优化建议。");
    }

    /**
     * @param Bridger $bridger
     * @return void
     */
    public function launcher(Bridger $bridger): void
    {
        // TODO: Implement launcher() method.
        $hook = $bridger->getHook();
        $hook->add('head', [$this, 'head'], 128);
    }

    /**
     * @param Bridger $bridger
     * @return void
     */
    public function head(Bridger $bridger): void
    {
        echo '<!-- octopus seo start -->';
        echo '<meta name="keywords" content="" />';
        echo '<meta name="description"  content=""/>';
        echo '<!-- octopus seo end -->';
    }

    public function activate(Bridger $bridger): void
    {
        // TODO: Implement activate() method.
    }

    public function uninstall(Bridger $bridger): void
    {
        // TODO: Implement uninstall() method.
    }

    public function getServices(Bridger $bridger): array
    {
        // TODO: Implement getServices() method.
        return [];
    }

    public function provider(Bridger $bridger): ?PluginProviderInterface
    {
        // TODO: Implement provider() method.
        return null;
    }
}
