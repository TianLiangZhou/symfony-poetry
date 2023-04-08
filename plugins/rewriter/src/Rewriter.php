<?php
namespace OctopusPress\Plugin\Rewriter;

use OctopusPress\Bundle\Bridge\Bridger;
use OctopusPress\Bundle\Customize\Draw;
use OctopusPress\Bundle\Entity\Post;
use OctopusPress\Bundle\Entity\TermTaxonomy;
use OctopusPress\Bundle\Entity\User;
use OctopusPress\Bundle\Plugin\Manifest;
use OctopusPress\Bundle\Plugin\PluginInterface;
use OctopusPress\Bundle\Plugin\PluginProviderInterface;
use OctopusPress\Bundle\Repository\OptionRepository;

class Rewriter implements PluginInterface
{

    private bool|null $rewriterTaxonomy = null;

    private bool|null $rewriterPosts = null;

    private ?OptionRepository $option = null;


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
        $hook = $bridger->getHook();
        $this->option = $bridger->getOptionRepository();
        $plugin->registerSetting('/rewriter/setting-form', [$this, 'setting'], '伪静态', 'rewriter');
        $hook->add('post_type_link', [$this, 'permalink'], -32);
        $hook->add('taxonomy_link', [$this, 'permalink'], -32);
        $hook->add('author_link', [$this, 'permalink'], -32);
    }

    /**
     * @param string $url
     * @param mixed $object
     * @return string
     */
    public function permalink(string $url, mixed $object): string
    {
        if ($this->rewriterPosts === null && $this->option) {
            $this->rewriterPosts = (bool) $this->option->value('_rewriter_posts');
        }
        if ($this->rewriterTaxonomy === null) {
            $this->rewriterTaxonomy = (bool) $this->option->value('_rewriter_taxonomy');
        }
        if ($object instanceof Post && $this->rewriterPosts) {
            return $url . '.html';
        }
        if (($object instanceof TermTaxonomy || $object instanceof User) && $this->rewriterTaxonomy) {
            return $url . '.html';
        }
        return $url;
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
