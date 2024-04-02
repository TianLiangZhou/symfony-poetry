<?php

namespace App\Command;

use FastFFI\LAC\LAC;
use OctopusPress\Bundle\Bridge\Bridger;
use OctopusPress\Bundle\Entity\Post;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AutoSegmentCommand extends Command
{
    private Bridger $bridger;

    public function __construct(Bridger $bridger, ?string $name = null)
    {
        parent::__construct($name);

        $this->bridger = $bridger;
    }

    protected function configure()
    {
        $this->setName('plugin:auto:segment')
            ->setDescription('给文章分词');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityManager = $this->bridger->getEntityManager();
        $showFrontTypes = $this->bridger->getPost()->getShowFrontTypes();

        $countQuery = $entityManager->createQuery('SELECT COUNT(1) FROM ' . Post::class . ' p WHERE p.type IN (?1)');
        $count = $countQuery->setParameter(1, $showFrontTypes)
            ->getSingleScalarResult();


        $pageSize = ceil($count / 5000);
        $lac = LAC::new();
        for($i = 0; $i < $pageSize; $i++) {
            $query = $entityManager->createQuery('SELECT p FROM ' . Post::class . ' p WHERE p.type IN (?1) ORDER BY p.id ASC');
            $query->setParameter(1, $showFrontTypes)
                ->setFirstResult($i * 5000)
                ->setMaxResults(5000);
            foreach ($query->toIterable() as $item) {
                /**
                 * @var $item Post
                 */

                $title = $item->getTitle();
                $lac->parse($title);
                echo $title, "\n";
            }
            die;
        }


        return Command::SUCCESS;
    }
}
