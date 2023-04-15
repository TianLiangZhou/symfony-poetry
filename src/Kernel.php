<?php

namespace App;

use App\Widget\Question;
use OctopusPress\Bundle\Bridge\Bridger;
use OctopusPress\Bundle\Customize\AbstractControl;
use OctopusPress\Bundle\Customize\Control;
use OctopusPress\Bundle\OctopusPressKernel;
use OctopusPress\Bundle\Plugin\Manifest;
use OctopusPress\Bundle\Plugin\PluginInterface;
use OctopusPress\Bundle\Plugin\PluginProviderInterface;

class Kernel extends OctopusPressKernel implements PluginInterface
{

    public function launcher(Bridger $bridger): void
    {
        $bridger->getTaxonomy()
            ->registerTaxonomy('dynasty', ['post', 'book', 'article'], [
                'label' => '朝代',
                'showPostTable' => ['post' => false, 'book' => false, 'article' => false],
            ]);
        $bridger->getPost()
            ->registerType('question', [
                'label' => '问答',
                'supports' => ['title'],
                'taxonomies' => ['tag'],
            ])
            ->registerType('book', [
                'label' => '书籍',
                'supports' => ['title', 'author','thumbnail', 'excerpt'],
                'taxonomies' => ['category', 'tag',],
            ])
            ->registerType('chapter', [
                'label' => '章节',
                'showOnFront' => false,
                'supports' => ['title', 'parent'],
                'parentType' => ['book', 'chapter'],
            ])
            ->registerType('article', [
                'label' => '文章',
                'supports' => ['title', 'parent'],
                'taxonomies' => ['category', 'tag',],
                'parentType' => ['chapter'],
            ])
        ;
        $plugin = $bridger->getPlugin();

        $plugin->addTypeMenu('question', '问答', ['parent' => 'backend_post', 'sort' => 6])
            ->addTypeMenu('book', '书籍', ['parent' => 'backend_post', 'sort' => 7])
            ->addTypeMenu('article', '文章', ['parent' => 'backend_post', 'sort' => 8])
            ->addTaxonomyMenu('dynasty', '朝代', ['parent' => 'backend_post', 'sort' => 5])
            ;

        $bridger->getMeta()
            ->registerPost('post', 'comment', [], Control::create('comment', '注释', [
                'type' => AbstractControl::TEXTAREA,
                'settings' => ['rows' => 10],
            ]))
            ->registerPost('post', 'translation', [], Control::create('translation', '译文', [
                'type' => AbstractControl::TEXTAREA,
                'settings' => ['rows' => 10],
            ]))
            ;
    }

    public static function manifest(): Manifest
    {
        return Manifest::builder();
    }

    public function activate(Bridger $bridger): void
    {

    }

    public function uninstall(Bridger $bridger): void
    {
    }

    public function getServices(Bridger $bridger): array
    {
        return [];
    }

    public function provider(Bridger $bridger): ?PluginProviderInterface
    {
        return null;
    }

}
