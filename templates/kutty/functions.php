<?php

use OctopusPress\Bundle\Bridge\Bridger;
use OctopusPress\Bundle\Customize\Control;
use OctopusPress\Bundle\Customize\GroupControl;
use OctopusPress\Bundle\Customize\ImageControl;
use OctopusPress\Bundle\Entity\User;
use OctopusPress\Bundle\Event\FilterEvent;
use OctopusPress\Bundle\Model\CustomizeManager;
use Twig\TwigFunction;

function registerThemeCustomize(CustomizeManager $manager): void
{
    $section = $manager->addDefaultSection('kutty', [
        'label' => 'Kutty'
    ]);
    $section->addControl(Control::create('beian', '备案号'));

    $top = new GroupControl('home_banners', [
        'label' => '头条推荐',
        'multiple' => false,
    ]);
    $top->addChild(new ImageControl('bg', [
            'label' => '背景'
        ]))
        ->addChild(new Control('post', [
            'label' => '文章ID',
            'inputType' => 'number',
        ]))
        ->addChild(new Control('title', [
            'label' => '标题'
        ]));
    $section->addControl($top);


    $section->addControl(new Control('home_authors', [
        'label' => '推荐作者',
        'description' => '作者ID,多个请以\',\'分隔'
    ]));


    $group = new GroupControl('home_types', [
        'label' => '首页显示的分类',
        'multiple' => true,
    ]);
    $group->addChild(new Control('title', ['label' => '标题', 'required'=>true]))
        ->addChild(new Control('color', ['label' => '颜色', 'description' => '请查看:https://tailwindcss.com/docs/customizing-colors','required' => true]))
        ->addChild(new Control('collection', ['label' => '数据集','description' => '内容ID,多个请以\',\'分隔', 'required' => true]));
    $section->addControl($group);
}


return function (Bridger $bridger) {
    $bridger->getHook()
        ->add('body_class', function (array $classes) {
            return array_merge($classes, ['bg-slate-50', 'dark:bg-slate-900']);
        })
        ->add('setup_theme', function(string $theme, FilterEvent $event) {
            $event->getBridger()->getWidget()
                ->get('breadcrumb')
                ->addTemplate('kutty/breadcrumb.html.twig');
            $event->getBridger()->getWidget()
                ->get('pagination')
                ->addTemplate('kutty/pagination.html.twig')
            ;
        })
        ->add('customize_register', registerThemeCustomize(...))
        ->add('html_attributes', function(array $attributes = []) {
            $attributes[] = 'x-data';
            $attributes[] = ':class="{\'dark\': $store.theme.current == \'Dark\'||$store.theme.system==\'Dark\'}"';
            return $attributes;
        })
        ;

    $bridger->getTwig()
        ->addFunction(new TwigFunction('dynasty', function (User $user) {
            $userMeta = $user->getMeta('dynasty');
            if ($userMeta == null) {
                return null;
            }
            $metaValue = (int) $userMeta->getMetaValue();
            if ($metaValue < 1) {
                return null;
            }
            return $metaValue;
        }));
    $theme = $bridger->getTheme();
    $theme->registerThemeNavigation([
        'primary' => '主导航',
    ]);
};
