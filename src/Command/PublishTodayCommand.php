<?php

namespace App\Command;

use Doctrine\DBAL\ParameterType;
use OctopusPress\Bundle\Bridge\Bridger;
use OctopusPress\Bundle\Entity\Post;
use OctopusPress\Bundle\Model\PluginManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PublishTodayCommand extends Command
{
    private Bridger $bridger;

    public function __construct(Bridger $bridger)
    {
        parent::__construct(null);
        $this->bridger = $bridger;
    }

    protected function configure()
    {
        $this->setName('publish:today')
            ->setDescription('定时推送今日贴子');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->bridger->getEntityManager()->getConnection();
        $associative = $connection->executeQuery(
            'SELECT id, author, type, status FROM posts WHERE status = ? ORDER BY id ASC LIMIT 2',
            ['draft'],
            [ParameterType::STRING]
        )->fetchAllAssociative();

        if (empty($associative)) {
            return self::SUCCESS;
        }
        $current = time();
        foreach ($associative as $item) {
            echo "id: ". $item['id'], "\n";
            $date = date('Y-m-d H:i:s',$current - random_int(500, 2700));
            $connection->executeStatement(
                'UPDATE posts SET status = ?, created_at = ?, modified_at = ? WHERE id = ?',
                [Post::STATUS_PUBLISHED, $date, $date, $item['id']],
                [ParameterType::STRING, ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER],
            );
            $connection->executeStatement(
                'UPDATE term_relationships SET status = ?, created_at = ? WHERE object_id = ?',
                [Post::STATUS_PUBLISHED, $date, $item['id']],
                [ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER],
            );
            $creation = $connection->executeQuery(
                'SELECT * FROM statistical_analysis WHERE type = ? AND sub_type = ? AND object_id = ? LIMIT 1',
                ['user', 'creation', $item['author']],
                [ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER]
            )->fetchAssociative();
            if (empty($creation)) {
                $connection->executeStatement(
                    'INSERT INTO statistical_analysis (type, sub_type, object_id, count, updated_at) VALUE (?, ?, ?, ?, ?)',
                    ['user', 'creation', $item['author'], 1, $date],
                    [ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::STRING]
                );
            } else {
                $connection->executeStatement(
                    'UPDATE statistical_analysis SET count = `count` + 1 WHERE type = ? AND sub_type = ? AND object_id = ?',
                    ['user', 'creation', $item['author']],
                    [ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER]
                );
            }

        }
        return self::SUCCESS;
    }
}
