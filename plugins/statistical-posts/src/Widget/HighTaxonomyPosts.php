<?php

namespace OctopusPress\Plugin\StatisticalPosts\Widget;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use OctopusPress\Bundle\Entity\TermTaxonomy;
use OctopusPress\Bundle\Widget\AbstractWidget;
use Traversable;
use Twig\TemplateWrapper;

class HighTaxonomyPosts extends AbstractWidget implements \IteratorAggregate
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
        if (empty($attributes['taxonomy']) || !($attributes['taxonomy'] instanceof TermTaxonomy)) {
            return [
                'entities' => [],
            ];
        }
        $taxonomy = $attributes['taxonomy'];
        $connection = $this->getBridger()->getEntityManager()->getConnection();
        if ($taxonomy->getTaxonomy() === TermTaxonomy::CATEGORY) {
            $top500 = $connection
                ->executeQuery(
                    'SELECT object_id FROM statistical_posts WHERE type = ? AND sub_type = ? ORDER BY count DESC LIMIT ?',
                    ['post', $attributes['subType'] ?? 'post', 500],
                    [ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER]
                )->fetchFirstColumn();
            if (empty($top500)) {
                return ['entities' => []];
            }
            $topObjects = $connection->executeQuery(
                'SELECT object_id FROM term_relationships WHERE object_id IN (?) AND term_taxonomy_id = ? LIMIT ?',
                [$top500, $taxonomy->getId(), (int) ($attributes['limit'] ?? 10)],
                [ArrayParameterType::INTEGER, ParameterType::INTEGER, ParameterType::INTEGER]
            )->fetchFirstColumn();
        } else {
            $allObjects = $connection->executeQuery(
                'SELECT object_id FROM term_relationships WHERE term_taxonomy_id = ?',
                [$taxonomy->getId()],
                [ParameterType::INTEGER]
            )->fetchFirstColumn();
            if (empty($allObjects)) {
                return ['entities' => []];
            }
            $topObjects = $connection
                ->executeQuery(
                    'SELECT object_id FROM statistical_posts WHERE type = ? AND sub_type = ? AND object_id IN (?) ORDER BY count DESC LIMIT ?',
                    ['post', $attributes['subType'] ?? 'post', $allObjects, (int) ($attributes['limit'] ?? 10)],
                    [ParameterType::STRING, ParameterType::STRING, ArrayParameterType::INTEGER, ParameterType::INTEGER]
                )->fetchFirstColumn();
        }
        if (empty($topObjects)) {
            return [
                'entities' => [],
            ];
        }
        $posts = $this->getBridger()->getPostRepository()
            ->createQuery([
                'id' => $topObjects,
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
        return new \ArrayIterator($context['entities'] ?? []);
    }
}
