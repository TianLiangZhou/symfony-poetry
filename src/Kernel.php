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
            ->registerTaxonomy('dynasty', 'post', [
                'label' => '朝代',
            ]);
        $bridger->getPost()
            ->registerType('question', [
                'label' => '问答',
                'supports' => ['title'],
                'taxonomies' => ['tag'],
            ]);
        $bridger->getMeta()
            ->registerPost('post', 'comment', [], new Control('', [
                'type' => AbstractControl::TEXTAREA,
                'label' => '注释',
                'settings' => ['rows' => 10],
            ]))
            ->registerPost('post', 'translation', [], new Control('', [
                'type' => AbstractControl::TEXTAREA,
                'label' => '译文',
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
