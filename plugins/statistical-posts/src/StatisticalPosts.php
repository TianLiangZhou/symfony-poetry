<?php
namespace OctopusPress\Plugin\StatisticalPosts;


use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use OctopusPress\Bundle\Bridge\Bridger;
use OctopusPress\Bundle\Entity\Post;
use OctopusPress\Bundle\Plugin\Manifest;
use OctopusPress\Bundle\Plugin\PluginInterface;
use OctopusPress\Bundle\Plugin\PluginProviderInterface;
use OctopusPress\Plugin\StatisticalPosts\EventListener\StatisticalListener;
use OctopusPress\Plugin\StatisticalPosts\Provider\StatisticalProvider;
use OctopusPress\Plugin\StatisticalPosts\Widget\HighAuthor;
use OctopusPress\Plugin\StatisticalPosts\Widget\HighAuthorPosts;
use OctopusPress\Plugin\StatisticalPosts\Widget\HighPosts;
use OctopusPress\Plugin\StatisticalPosts\Widget\HighTaxonomyPosts;

class StatisticalPosts implements PluginInterface
{

    public static function manifest(): Manifest
    {
        // TODO: Implement manifest() method.
        return Manifest::builder()
            ->setName("稿子统计")
            ->addAuthor('OctopusPress.dev', 'https://octopuspress.dev')
            ->setVersion('1.0.0')
            ->setMinVersion('1.0.0')
            ->setMinPhpVersion('8.1')
            ->setDescription('稿子统计能帮你统计帖子的浏览量，作者的帖子数量。')
            ;
    }

    public function launcher(Bridger $bridger): void
    {
        // TODO: Implement launcher() method.
        $bridger->getHook()->add('setup_theme', function () use ($bridger) {
            $bridger->getWidget()
                ->registerForClassName(HighPosts::class)
                ->registerForClassName(HighAuthor::class)
                ->registerForClassName(HighAuthorPosts::class)
                ->registerForClassName(HighTaxonomyPosts::class);
        });

    }

    /**
     * @throws Exception
     * @throws SchemaException
     */
    public function activate(Bridger $bridger): void
    {
        // TODO: Implement activate() method.
        $connection = $bridger->getEntityManager()->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $tables = $this->getTables();
        foreach ($tables as $table) {
            $name = $table->getName();
            if (!$schemaManager->tablesExist($name)) {
                $schemaManager->createTable($table);
            }
        }
        $date = date('Y-m-d H:i:s');
        $results = $connection->executeQuery(
            'SELECT count(1) as cnt, author FROM posts WHERE status = ? OR status = ? OR (status = ? AND created_at < ?) GROUP BY author',
            [Post::STATUS_PUBLISHED, Post::STATUS_PRIVATE, Post::STATUS_FUTURE, $date],
            [ParameterType::STRING, ParameterType::STRING, ParameterType::STRING, ParameterType::STRING]
        )->fetchAllAssociative();
        foreach ($results as $item) {
            $connection->executeStatement(
                'INSERT INTO statistical_posts (type, sub_type, object_id, count, updated_at) VALUE (?, ?, ?, ?, ?)',
                ['user', 'creation', $item['author'], $item['cnt'], $date],
                [ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::STRING]
            );
        }
    }

    public function uninstall(Bridger $bridger): void
    {
        // TODO: Implement uninstall() method.
    }

    public function getServices(Bridger $bridger): array
    {
        // TODO: Implement getServices() method.
        return [
            new StatisticalListener($bridger),
        ];
    }

    public function provider(Bridger $bridger): ?PluginProviderInterface
    {
        return new StatisticalProvider($bridger);
    }

    /**
     * @return Table[]
     * @throws SchemaException
     */
    private function getTables(): array
    {
        $statisticalPost = new Table('statistical_posts');
        $statisticalPost->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $statisticalPost->addColumn('type', 'string', ['length' => 32]);
        $statisticalPost->addColumn('sub_type', 'string', ['length' => 32]);
        $statisticalPost->addColumn('object_id', 'integer', ['unsigned' => true]);
        $statisticalPost->addColumn('count', 'integer', ['unsigned' => true]);
        $statisticalPost->addColumn('updated_at', 'datetime');
        $statisticalPost->setPrimaryKey(['id']);
        $statisticalPost->addIndex(['type']);
        $statisticalPost->addIndex(['object_id']);
        $statisticalPost->addIndex(['type', 'sub_type', 'count']);
        return [$statisticalPost];
    }
}
