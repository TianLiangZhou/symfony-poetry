<?php
namespace OctopusPress\Plugin\Rewriter;

use OctopusPress\Bundle\Bridge\Bridger;
use OctopusPress\Bundle\Customize\Draw;
use OctopusPress\Bundle\Plugin\Manifest;
use OctopusPress\Bundle\Plugin\PluginInterface;
use OctopusPress\Bundle\Plugin\PluginProviderInterface;

class Rewriter implements PluginInterface
{

    public static function manifest(): Manifest
    {
        // TODO: Implement manifest() method.
        return Manifest::builder()
            ->setName("伪静态")
            ->addAuthor('OctopusPress.dev', 'https://octopuspress.dev')
            ->setVersion('1.0.0')
            ->setMinVersion('1.0.0')
            ->setMinPhpVersion('8.1')
            ->setDescription('站点URL以html后缀结尾，此插件需要配合服务器设置。')
            ;
    }

    public function launcher(Bridger $bridger): void
    {
        // TODO: Implement launcher() method.
        $plugin = $bridger->getPlugin();
        $plugin->registerSetting('/rewriter/setting-form', [$this, 'setting'], '伪静态', 'rewriter');
    }

    public function setting(): Draw
    {
        $draw = Draw::builder();
        $form = $draw->form()->setDirection('row');
        $form->add('_rewriter_taxonomy', '开启类别', 'checkbox', [
            'description' => '',
        ]);
        $form->add('_rewriter_posts', '开启帖子', 'checkbox', [
            'description' => '',
        ]);
        return $draw;
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
