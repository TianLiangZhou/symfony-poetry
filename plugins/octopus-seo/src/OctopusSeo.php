<?php

namespace OctopusPress\Plugin\OctopusSeo;


use DateTimeInterface;
use OctopusPress\Bundle\Bridge\Bridger;
use OctopusPress\Bundle\Entity\Post;
use OctopusPress\Bundle\Entity\TermTaxonomy;
use OctopusPress\Bundle\Entity\User;
use OctopusPress\Bundle\Event\FilterEvent;
use OctopusPress\Bundle\Plugin\Manifest;
use OctopusPress\Bundle\Plugin\PluginInterface;
use OctopusPress\Bundle\Plugin\PluginProviderInterface;
use OctopusPress\Bundle\Support\ArchiveDataSet;
use OctopusPress\Bundle\Twig\OctopusRuntime;
use OctopusPress\Plugin\OctopusSeo\Controller\DashboardController;

class OctopusSeo implements PluginInterface
{

    private array $config = [];
    private ?OctopusRuntime $twigRuntime = null;

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
     * @throws \Exception
     */
    public function launcher(Bridger $bridger): void
    {
        // TODO: Implement launcher() method.
        $hook = $bridger->getHook();
        $this->config = $bridger->getOptionRepository()->value('_octopus_seo', []);
        if (empty($this->config)) {
            $this->config = $bridger->get(DashboardController::class)->getDefaults();
        }
        $hook->remove('head', [$bridger->getDefaultFilter(), 'getTitleTag']);
        $hook->add('head', [$this, 'head'], 128);
        $this->twigRuntime = $bridger->getTwig()->getRuntime(OctopusRuntime::class);
        $bridger->getPlugin()->registerRoute(DashboardController::class);

    }

    /**
     * @param FilterEvent $event
     * @return void
     */
    public function head(FilterEvent $event): void
    {
        $bridger = $event->getBridger();
        $activatedRoute = $bridger->getActivatedRoute();

        $sep = $this->config['separator'] ?? '-';
        $createdAt = $modifiedAt = $author = "";
        $tags = $attachment = [];
        $ogType = 'article';
        $title = $desc = $excerpt = $intro = $category = $level = "";
        /**
         * @var $entity ArchiveDataSet|Post
         */
        $entity = $bridger->getControllerResult();
        if ($activatedRoute->isSingular()) {
            $author = $entity->getAuthor()->getNickname();
            foreach ($entity->getTags() as $tag) {
                $tags[] = $tag->getName();
            }
            $createdAt = $entity->getCreatedAt()->format(DateTimeInterface::ATOM);
            $modifiedAt = $entity->getModifiedAt()->format(DateTimeInterface::ATOM);
            $title = $entity->getTitle();
            $excerpt = $entity->getExcerpt();
            if (mb_strlen($excerpt) > 155) {
                $excerpt = mb_substr($excerpt, 0, 154);
            }
            $arrayCollection = $entity->getCategories();
            if ($arrayCollection->count() > 0) {
                $category = $arrayCollection->get(0)->getName();
            }
            $option = $this->config['type_' . $entity->getType()];
        } elseif ($activatedRoute->isArchives()) {
            $classify = $entity->getArchiveTaxonomy();
            if ($classify instanceof TermTaxonomy) {
                $title = $classify->getName();
                $option = $this->config['taxonomy_' . $classify->getTaxonomy()];
                $desc = $classify->getDescription();
                if (mb_strlen($desc) > 155) {
                    $desc = mb_substr($desc, 0, 154);
                }
            } elseif ($classify instanceof User) {
                $ogType = 'profile';
                $title = $author = $classify->getNickname();
                $intro = $classify->getMeta('description')?->getMetaValue();
                if (mb_strlen($intro) > 155) {
                    $intro = mb_substr($intro, 0, 154);
                }
                $option = $this->config['author'];
            } else {
                $option = $this->config['home'];
            }
        } else {
            $option = $this->config['home'];
            $ogType = 'website';
        }
        $rep = $bridger->getOptionRepository();
        $siteTitle = $rep->title();
        $siteSubtitle = $rep->subtitle();
        $siteDescription = $rep->description();
        $lang = $rep->lang();
        $currentUrl = $bridger->getRequest()->getUriForPath(
            $bridger->getRequest()->server->get('ORIGIN_REQUEST_URI')
        );
        $search = [
            '%name%',
            '%slogan%',
            '%description%',
            '%title%',
            '%category%',
            '%excerpt%',
            '%desc%',
            '%level%',
            '%intro%',
            '%separator%'
        ];
        $replace = [
            $siteTitle, $siteSubtitle, $siteDescription, $title, $category, $excerpt, $desc, $level, $intro, $sep,
        ];
        $title = str_replace($search, $replace, $option['title']);
        $description = str_replace($search, $replace, $option['description']);
        $socialTitle = str_replace($search, $replace, $option['social_title']);
        $socialDescription = str_replace($search, $replace, $option['social_description']);
        $socialImage = $option['social_image'];
        echo '<!-- octopus seo start -->', PHP_EOL;
        echo "\t\t", '<title>' . $title . '</title>', PHP_EOL;
        echo "\t\t", '<meta name="description"  content="' . $description . '"/>', PHP_EOL;
        echo "\t\t", '<link rel="canonical" href="' . $currentUrl . '" />', PHP_EOL;
        echo "\t\t", '<meta property="og:locale" content="' . $lang . '" />', PHP_EOL;
        echo "\t\t", '<meta property="og:type" content="' . $ogType . '" />', PHP_EOL;
        echo "\t\t", '<meta property="og:title" content="' . $socialTitle . '" />', PHP_EOL;
        echo "\t\t", '<meta property="og:description" content="' . $socialDescription . '" />', PHP_EOL;
        echo "\t\t", '<meta property="og:url" content="' . $currentUrl . '" />', PHP_EOL;
        echo "\t\t", '<meta property="og:site_name" content="' . $siteTitle . '" />', PHP_EOL;
        if ($author && $ogType === 'article') {
            echo "\t\t", '<meta property="og:author" content="' . $author . '" />', PHP_EOL;
        }
        foreach ($tags as $tag) {
            echo "\t\t", '<meta property="og:tag" content="' . $tag . '" />', PHP_EOL;
        }
        if ($createdAt) {
            echo "\t\t", '<meta property="article:published_time" content="' . $createdAt . '" />', PHP_EOL;
            echo "\t\t", '<meta property="article:modified_time" content="' . $modifiedAt . '" />', PHP_EOL;
        }
        if ($author && $ogType === 'profile') {
            echo "\t\t", '<meta property="profile:first_name" content="' . mb_substr($author, 1) . '" />', PHP_EOL;
            echo "\t\t", '<meta property="profile:last_name" content="' . mb_substr($author, 0, 1) . '" />', PHP_EOL;
            echo "\t\t", '<meta property="profile:username" content="' . $author . '" />', PHP_EOL;
        }
        if ($attachment) {
            echo "\t\t", '<meta property="og:image" content="" />', PHP_EOL;
            echo "\t\t", '<meta property="og:image:width" content="300" />', PHP_EOL;
            echo "\t\t", '<meta property="og:image:height" content="336" />', PHP_EOL;
            echo "\t\t", '<meta property="og:image:type" content="image/png" />', PHP_EOL;
        }

        $verifies = [
            ['baidu_verify', 'baidu-site-verification'],
            ['google_verify', 'google-site-verification'],
            ['bing_verify', 'msvalidate.01'],
            ['yandex_verify', 'yandex-verification'],
            ['360_verify', '360-site-verification'],
            ['sogou_verify', 'sogou_site_verification'],
            ['sm_verify', 'shenma-site-verification'],
        ];
        foreach ($verifies as $params) {
            if (empty($seo[$params[0]])) {
                continue;
            }
            echo sprintf('<meta name="%s" content="%s" />', $params[1], $seo[$params[0]]) . PHP_EOL;
        }
        echo '<!-- octopus seo end -->', PHP_EOL;
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
        return [new DashboardController($bridger)];
    }

    public function provider(Bridger $bridger): ?PluginProviderInterface
    {
        // TODO: Implement provider() method.
        return null;
    }

    private function getArticleSchemaGraph(Post $entity)
    {
        $jsonLD = [
            '@context' => "https://schema.org",
            "@type" => "NewsArticle",
            "headline" => $entity->getTitle(),
            "image" => [
            ],
            "datePublished" => $entity->getCreatedAt()->format(DateTimeInterface::ATOM),
            "dateModified" => $entity->getCreatedAt()->format(DateTimeInterface::ATOM),
            "author" => [
                [
                    "@type" => "Person",
                    "name" => $entity->getAuthor()->getNickname(),
                    "url" => $this->twigRuntime->permalink($entity->getAuthor()),
                ]
            ]
        ];
        echo "\t\t", sprintf('<script type="application/ld+json" class="seo-schema-graph">%s</script>', json_encode($jsonLD)), PHP_EOL;
    }
}
