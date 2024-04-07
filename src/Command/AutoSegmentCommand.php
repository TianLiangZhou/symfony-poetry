<?php

namespace App\Command;

use FastFFI\LAC\LAC;
use FastFFI\Pinyin\Pinyin;
use OctopusPress\Bundle\Bridge\Bridger;
use OctopusPress\Bundle\Entity\Post;
use OctopusPress\Bundle\Entity\Term;
use OctopusPress\Bundle\Entity\TermRelationship;
use OctopusPress\Bundle\Entity\TermTaxonomy;
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


        $max = 1000;
        $pageSize = ceil($count / $max);
        $lac = LAC::new();
        $pinyin = Pinyin::new();
        for($i = 0; $i < $pageSize; $i++) {
            echo "pageSize =", $i + 1, "\n";
            $query = $entityManager->createQuery('SELECT p FROM ' . Post::class . ' p WHERE p.type IN (?1) ORDER BY p.id ASC');
            $query->setParameter(1, $showFrontTypes)
                ->setFirstResult($i * $max)
                ->setMaxResults($max);
            $toIterable = $query->toIterable();
            foreach ($toIterable as $item) {
                /**
                 * @var $item Post
                 */

                $title = explode('·', $item->getTitle())[0];
                $segments = $lac->parse($title);
                $words = explode(' ', $segments['words']);
                $tags  = explode(' ', $segments['tags']);
                $filterWords = [];
                foreach ($words as $j => $word) {
                    if (mb_strlen($word) < 2) {
                        continue;
                    }
                    if (!isset($tags[$j])) {
                        continue;
                    }
                    if (in_array($tags[$j], ['r', 'nw', 'm', 'q', 'r', 'd', 'p', 'w', 'xc', 'u','c'])) {
                        continue;
                    }
                    if (in_array($word, $filterWords)) {
                        continue;
                    }
                    if ($word == $title) {
                        continue;
                    }
                    echo $word ,"=", $tags[$j], " ";
                    $filterWords[] = $word;
                }
                if (empty($filterWords)) {
                    continue;
                }
                echo $item->getId(), "=", $title, "\n";

                $taxonomies = [];
                foreach ($filterWords as $word) {
                    $slug = $pinyin->slug($word);
                    $term = $entityManager->createQuery('SELECT t FROM ' . Term::class . ' t WHERE t.name = ?1 AND t.slug = ?2')
                        ->setParameter(1, $word)
                        ->setParameter(2, $slug)
                        ->setMaxResults(1)
                        ->getOneOrNullResult();
                    if ($term == null) {
                        $term = new Term();
                        $term->setName($word)->setSlug($slug);
                        $taxonomy = new TermTaxonomy();
                        $taxonomy->setTaxonomy(TermTaxonomy::TAG)
                            ->setTerm($term)
                            ->setCount(1);
                    } else {
                        $taxonomy = $entityManager->createQuery('SELECT tt FROM ' . TermTaxonomy::class . ' tt WHERE tt.term = ?1 AND tt.taxonomy = ?2')
                            ->setParameter(1, $term->getId())
                            ->setParameter(2, TermTaxonomy::TAG)
                            ->setMaxResults(1)
                            ->getOneOrNullResult();
                        if ($taxonomy == null) {
                            $taxonomy = new TermTaxonomy();
                            $taxonomy->setTaxonomy(TermTaxonomy::TAG);
                            $taxonomy->setTerm($term);
                        }
                        $taxonomy->setCount($taxonomy->getCount() + 1);
                    }
                    $taxonomies[] = $taxonomy;
                    $entityManager->persist($taxonomy);
                }
                $entityManager->flush();
                $taxonomySets = [];
                foreach ($item->getTermRelationships() as $relationship) {
                    $taxonomySets[] = $relationship->getTaxonomy()->getId();
                }
                foreach ($taxonomies as $taxonomy) {
                    if (in_array($taxonomy->getId(), $taxonomySets)) {
                        $taxonomy->setCount($taxonomy->getCount() - 1);
                        $entityManager->persist($taxonomy);
                        continue;
                    }
                    $termRelationship = new TermRelationship();
                    $termRelationship->setTaxonomy($taxonomy);
                    $item->addTermRelationship($termRelationship);
                }
                $entityManager->persist($item);
                $entityManager->flush();
            }
            $entityManager->clear();
        }


        return Command::SUCCESS;
    }
}
