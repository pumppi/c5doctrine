<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\Article;
use Doctrine\ORM\Proxy\Proxy;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue84Test extends BaseTestCaseORM
{
    const ARTICLE = 'Translatable\\Fixture\\Article';
    const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    private $translatableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testIssue84()
    {
        $repo = $this->em->getRepository(self::TRANSLATION);

        $article = new Article;
        $article->setTitle('en art');
        $article->setContent('content');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $article = $this->em->getReference(self::ARTICLE, 1);
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $article);

        $trans = $repo->findTranslations($article);
        $this->assertEquals(1, count($trans));
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::TRANSLATION
        );
    }
}