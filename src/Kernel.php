<?php

namespace App;

use OctopusPress\Bundle\Bridge\Bridger;
use OctopusPress\Bundle\Customize\AbstractControl;
use OctopusPress\Bundle\Customize\Control;
use OctopusPress\Bundle\Entity\Post;
use OctopusPress\Bundle\Event\FilterEvent;
use OctopusPress\Bundle\OctopusPressKernel;
use OctopusPress\Bundle\Plugin\PluginInterface;
use OctopusPress\Bundle\Plugin\PluginProviderInterface;
use OctopusPress\Bundle\Twig\OctopusRuntime;
use OctopusPress\Plugin\OctopusSeo\OctopusSeo;

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
                'supports' => ['title', 'parent', 'excerpt', 'editor', 'author'],
                'taxonomies' => ['category', 'tag',],
                'parentType' => ['book', 'chapter'],
            ])
            ->registerType('article', [
                'label' => '文章',
                'supports' => ['title', 'editor', 'author', 'excerpt', 'thumbnail'],
                'taxonomies' => ['category', 'tag',],
            ])
        ;
        $plugin = $bridger->getPlugin();

        $plugin->addTypeMenu('question', '问答', ['parent' => 'backend_post', 'sort' => 6])
            ->addTypeMenu('book', '书籍', ['parent' => 'backend_post', 'sort' => 7])
            ->addTypeMenu('article', '文章', ['parent' => 'backend_post', 'sort' => 8])
            ->addTaxonomyMenu('dynasty', '朝代', ['parent' => 'backend_post', 'sort' => 5])
            ;

        $bridger
            ->getHook()->add('_seo_graph_title', $this->tempHandleTitle(...))
            ->add('_seo_title', $this->tempHandleTitle(...))
            ->add('_seo_schema_supports_qa', function (bool $supports) use ($bridger) {
                if ($bridger->getActivatedRoute()->isSingular()) {
                    /**
                     * @var $controllerResult Post
                     */
                    $controllerResult = $bridger->getControllerResult();
                    return $controllerResult->getType() === 'question';
                }
                return false;
            })
            ->add('_seo_schema_qa', function (array $data) use ($bridger) {
                $data['@id'] = $bridger->getRequest()->getUri() . '/#question';
                /**
                 * @var $controllerResult Post
                 */
                $controllerResult = $bridger->getControllerResult();
                $data['name'] = $controllerResult->getTitle();

                $tags = [];
                foreach ($controllerResult->getTags() as $tag) {
                    $tags[] = $tag->getId();
                }
                $runtime = $bridger->getTwig()->getRuntime(OctopusRuntime::class);
                $taxonomyPosts = $runtime->getTaxonomyPosts($tags, ['type' => 'post', 'article', 'book']);
                $data["answerCount"] = count($taxonomyPosts);
                $data["datePublished"] = $controllerResult->getCreatedAt()->format(DATE_ATOM);
                $siteUrl = $bridger->getRequest()->getSchemeAndHttpHost();
                $data["author"] = [
                    '@type' => 'Organization',
                    'name'  => OctopusSeo::getOption('organization')['name'] ?? '',
                    'url'   => $siteUrl,
                ];
                $answers = [];
                foreach ($taxonomyPosts as $post) {
                    /**
                     * @var  $post Post
                     */
                    $answers[] = [
                        "@type" => "Answer",
                        "text" => $post->getTitle(),
                        "url" => $siteUrl . $runtime->permalink($post),
                        "datePublished" => $post->getCreatedAt()->format(DATE_ATOM),
                        "author" => [
                            "@type" => "Person",
                            "name" => $post->getAuthor()->getNickname(),
                            "url" => $siteUrl . $runtime->permalink($post->getAuthor())
                        ],
                    ];
                }
                $data['suggestedAnswer'] = $answers;
                unset($data['acceptedAnswer'],$data['upvoteCount'], $data['text']);
                return $data;
            })
            ->add('_seo_schema_webpage', function (array $data) use ($bridger) {

                /**
                 * @var $controllerResult Post
                 */
                $controllerResult = $bridger->getControllerResult();
                if ($controllerResult instanceof Post && $controllerResult->getType() === 'question') {
                    $data['mainEntity'] = [
                        "@type" => 'Question',
                        "@id" => $bridger->getRequest()->getUri() . '/#question',
                    ];
                }
                return $data;
            })
        ;

        $taxonomies = $bridger->getTaxonomyRepository()->taxonomies('dynasty');
        $options = [];
        foreach ($taxonomies as $taxonomy) {
            $options[] = ['label' => $taxonomy->getName(), 'value' => $taxonomy->getId()];
        }
        $bridger->getMeta()
            ->registerPost(['post', 'article'], 'comment', [], Control::create('comment', '注释', [
                'type' => AbstractControl::TEXTAREA,
                'settings' => ['rows' => 10],
            ]))
            ->registerPost(['post', 'article'], 'translation', [], Control::create('translation', '译文', [
                'type' => AbstractControl::TEXTAREA,
                'settings' => ['rows' => 10],
            ]))
            ->registerUser('dynasty', [], Control::create('dynasty', '朝代', [
                'type' => 'select',
                'options' => $options,
            ]))
            ;
    }

    /**
     * @param string $title
     * @param object $presentation
     * @param FilterEvent $event
     * @return string
     */
    public function tempHandleTitle(string $title, object $presentation, FilterEvent $event): string
    {
        if (stripos($title, '%parent%') !== false) {
            if ($event->getBridger()->getActivatedRoute()->isSingular()) {
                /**
                 * @var $controllerResult Post
                 */
                $controllerResult = $event->getBridger()->getControllerResult();
                $parent = $controllerResult->getParent();
                if ($parent != null) {
                    return str_replace('%parent%', $parent->getTitle(), $title);
                }
            }
        }
        return $title;
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
