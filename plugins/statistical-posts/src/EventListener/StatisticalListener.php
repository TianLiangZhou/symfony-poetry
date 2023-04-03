<?php

namespace OctopusPress\Plugin\StatisticalPosts\EventListener;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use OctopusPress\Bundle\Bridge\Bridger;
use OctopusPress\Bundle\Entity\Post;
use OctopusPress\Bundle\Entity\TermTaxonomy;
use OctopusPress\Bundle\Entity\User;
use OctopusPress\Bundle\Event\OctopusEvent;
use OctopusPress\Bundle\Event\PostEvent;
use OctopusPress\Bundle\Event\PostStatusUpdateEvent;
use OctopusPress\Bundle\Support\ArchiveDataSet;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 *
 */
class StatisticalListener implements EventSubscriberInterface
{
    private Bridger $bridger;

    public function __construct(Bridger $bridger)
    {
        $this->bridger = $bridger;
    }


    /**
     * @param FinishRequestEvent $event
     * @return void
     * @throws Exception
     */
    public function onFinish(FinishRequestEvent $event): void
    {
        $activatedRoute = $this->bridger->getActivatedRoute();
        if ($activatedRoute->isSingular() || $activatedRoute->isArchives()) {
            $this->statistical($event->getRequest());
        }
    }

    /**
     * @param PostEvent $event
     * @return void
     * @throws Exception
     */
    public function onPostDelete(PostEvent $event): void
    {
        $authorId = $event->getPost()->getAuthor()->getId();
        $connection = $this->bridger->getEntityManager()->getConnection();
        $connection->executeQuery(
            'UPDATE statistical_posts SET `count` = `count` - 1 WHERE type = ? AND sub_type = ? AND object_id = ? AND count > 0',
            ['user', 'creation', $authorId],
            [ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER]
        );
    }


    /**
     * @param PostEvent $event
     * @return void
     * @throws Exception
     */
    public function onPostSave(PostEvent $event): void
    {
        $post = $event->getPost();
        $oldStatus = $event->getOldStatus();
        $status = $post->getStatus();
        if ($oldStatus === $status) {
            return ;
        }
        if (empty($oldStatus) && $status !== Post::STATUS_PUBLISHED) {
            return ;
        }
        if ($oldStatus !== Post::STATUS_PUBLISHED && $status !== Post::STATUS_PUBLISHED) {
            return ;
        }
        $authorId = $post->getAuthor()->getId();
        $connection = $this->bridger->getEntityManager()->getConnection();
        $record = $connection->executeQuery(
            'SELECT id FROM statistical_posts WHERE type = ? AND sub_type = ? AND object_id = ? LIMIT 1',
            ['user', 'creation', $authorId],
            [ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER]
        )->fetchAssociative();
        $date = date('Y-m-d H:i:s');
        if (empty($record) && $status === Post::STATUS_PUBLISHED) {
            $connection->executeStatement(
                'INSERT INTO statistical_posts (type, sub_type, object_id, count, updated_at) VALUE (?, ?, ?, ?, ?)',
                ['user', 'creation', $authorId, 1, $date],
                [ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::STRING]
            );
        } else {
            $connection->executeQuery(
                'UPDATE statistical_posts SET `count` = `count` + ? WHERE type = ? AND sub_type = ? AND object_id = ? AND count > 0',
                [$status === Post::STATUS_PUBLISHED ? 1 : -1, 'user', 'creation', $authorId],
                [ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER]
            );
        }
    }


    /**
     * @param PostStatusUpdateEvent $event
     * @return void
     * @throws Exception
     */
    public function onPostStatus(PostStatusUpdateEvent $event): void
    {
        $posts = $event->getPosts();
        $authors = [];
        foreach ($posts as $post) {
            $status = $post->getStatus();
            if ($status !== Post::STATUS_PUBLISHED) {
                continue;
            }
            $authorId = $post->getAuthor()->getId();
            $authors[$authorId] = ($authors[$authorId] ?? 0) + 1;
        }
        if (count($authors) < 1) {
            return ;
        }
        $connection = $this->bridger->getEntityManager()->getConnection();
        foreach ($authors as $authorId => $count) {
            $connection->executeQuery(
                'UPDATE statistical_posts SET `count` = `count` + ? WHERE type = ? AND sub_type = ? AND object_id = ? AND count > 0',
                ['user', 'creation', $authorId, -$count],
                [ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER, ParameterType::INTEGER]
            );
        }
    }



    public static function getSubscribedEvents(): array
    {
        // TODO: Implement getSubscribedEvents() method.
        return [
            KernelEvents::FINISH_REQUEST => ['onFinish', 32],
            OctopusEvent::POST_DELETE => ['onPostDelete', 32],
            OctopusEvent::POST_SAVE_AFTER => ['onPostSave', 32],
            OctopusEvent::POST_STATUS_UPDATE => ['onPostStatus', 32],
        ];
    }

    /**
     * @throws Exception
     */
    private function statistical(Request $request)
    {
        $result = $request->attributes->get('_controller_result');
        if ($result == null) {
            return ;
        }
        $type = $subType = '';
        $objectId = 0;
        if ($result instanceof Post) {
            $type = 'post';
            $subType = $result->getType();
            $objectId = $result->getId();
        }
        if ($result instanceof ArchiveDataSet) {
            $taxonomy = $result->getArchiveTaxonomy();
            if ($taxonomy instanceof TermTaxonomy) {
                $type = 'taxonomy';
                $subType = $taxonomy->getTaxonomy();
                $objectId = $taxonomy->getId();
            } elseif ($taxonomy instanceof User) {
                $type = 'user';
                $subType = 'author';
                $objectId = $taxonomy->getId();
            }
        }
        if (empty($type) || empty($subType) || $objectId < 1) {
            return ;
        }
        /**
         * @var
         */
        $entityManager = $this->bridger->getEntityManager();
        $connection = $entityManager->getConnection();
        $record = $connection->executeQuery(
            'SELECT id, count FROM statistical_posts WHERE type = ? and sub_type = ? AND object_id = ? LIMIT 1',
            [$type, $subType, $objectId]
        )->fetchAssociative();
        if ($record) {
            $connection->executeStatement('UPDATE statistical_posts SET count = `count` + 1, updated_at = ?  WHERE id = ?', [date('Y-m-d H:i:s'), $record['id']]);
        } else {
            $connection->executeStatement(
                'INSERT INTO statistical_posts (`type`, `sub_type`, `object_id`, `count`, `updated_at`) VALUE (?, ?, ?, ?, ?)',
                [$type, $subType, $objectId, 1, date('Y-m-d H:i:s')]
            );
        }
    }
}
