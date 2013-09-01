<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\ORM\Query;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Translatable\Fixture\Article;
use Translatable\Fixture\Comment;

/**
 * These are tests for translation query walker
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue109Test extends BaseTestCaseORM
{
    const ARTICLE = 'Translatable\\Fixture\\Article';
    const COMMENT = 'Translatable\\Fixture\\Comment';
    const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    const TREE_WALKER_TRANSLATION = 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker';

    private $translatableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
         $this->populate();
    }

    public function testIssue109()
    {
        $this->em
            ->getConfiguration()
            ->expects($this->any())
            ->method('getCustomHydrationMode')
            ->with(TranslationWalker::HYDRATE_OBJECT_TRANSLATION)
            ->will($this->returnValue('Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'))
        ;
        $query = $this->em->createQueryBuilder();
        $query->select('a')
            ->from(self::ARTICLE, 'a')
            ->add('where', $query->expr()->not($query->expr()->eq('a.title', ':title')))
            ->setParameter('title', 'NA')
        ;

        $this->translatableListener->setTranslatableLocale('es');
        $this->translatableListener->setDefaultLocale('en');
        $this->translatableListener->setTranslationFallback(true);
        $query = $query->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $result = $query->getResult();
        $this->assertEquals(3, count($result));
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::TRANSLATION,
            self::COMMENT
        );
    }

    public function populate()
    {
        $text0 = new Article;
        $text0->setTitle('text0');

        $this->em->persist($text0);

        $text1 = new Article;
        $text1->setTitle('text1');

        $this->em->persist($text1);

        $na = new Article;
        $na->setTitle('NA');

        $this->em->persist($na);

        $out = new Article;
        $out->setTitle('Out');

        $this->em->persist($out);
        $this->em->flush();
        $this->translatableListener->setTranslatableLocale('es');

        $text1->setTitle('texto1');
        $text0->setTitle('texto0');
        $this->em->persist($text1);
        $this->em->persist($text0);
        $this->em->flush();
    }
}
