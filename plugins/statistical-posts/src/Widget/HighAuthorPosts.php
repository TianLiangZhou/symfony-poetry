<?php

namespace OctopusPress\Plugin\StatisticalPosts\Widget;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use OctopusPress\Bundle\Entity\Post;
use OctopusPress\Bundle\Widget\AbstractWidget;
use Traversable;
use Twig\TemplateWrapper;

class HighAuthorPosts extends AbstractWidget implements \IteratorAggregate
{

    protected function template(): string|TemplateWrapper
    {
        // TODO: Implement template() method.
    }

    /**
     * @throws Exception
     */
    protected function context(array $attributes = []): array
    {
        // TODO: Implement context() method.
        if (empty($attributes['author']) || $attributes['author'] < 1) {
            return [
                'entities' => [],
            ];
        }
        $connection = $this->getBridger()->getEntityManager()->getConnection();
        $result = $connection->executeQuery(
                'SELECT id FROM posts WHERE author = ? AND type = ? AND status = ?',
                [(int) $attributes['author'], $attributes['type'] ?? 'post', Post::STATUS_DRAFT],
                [ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING]
            )->fetchFirstColumn();
        if (empty($result)) {
            return [
                'entities' => [],
            ];
        }
        $result = $connection->executeQuery(
            'SELECT object_id FROM statistical_posts WHERE type = ? AND sub_type = ? AND object_id IN (?) ORDER BY count DESC LIMIT ?',
            ['post', $attributes['type'] ?? 'post', $result, (int) ($attributes['limit'] ?? 10)],
            [ParameterType::STRING, ParameterType::STRING, ArrayParameterType::INTEGER, ParameterType::INTEGER]
        )->fetchFirstColumn();
        if (empty($result)) {
            return [
                'entities' => [],
            ];
        }
        $posts = $this->getBridger()->getPostRepository()
            ->createQuery([
                'id' => $result,
            ])->getResult();
        return [
            'entities' => $posts,
        ];
    }

    public function delayRegister(): void
    {
        // TODO: Implement delayRegister() method.
    }

    public function getIterator(): Traversable
    {
        // TODO: Implement getIterator() method.
        $context = $this->getContext();
        return new \ArrayIterator($context['entities']);
    }
}
