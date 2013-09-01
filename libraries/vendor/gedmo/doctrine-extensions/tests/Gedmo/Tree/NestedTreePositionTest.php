<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Tree\Fixture\Category;
use Tree\Fixture\RootCategory;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NestedTreePositionTest extends BaseTestCaseORM
{
    const CATEGORY = "Tree\\Fixture\\Category";
    const ROOT_CATEGORY = "Tree\\Fixture\\RootCategory";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);

        $this->getMockSqliteEntityManager($evm);
    }

    /**
    * @test
    * @expectedException UnexpectedValueException
    */
    function shouldFailToPersistRootSibling()
    {
        $food = new Category;
        $food->setTitle('Food');

        $sport = new Category;
        $sport->setTitle('Sport');

        $repo = $this->em->getRepository(self::CATEGORY);

        $repo->persistAsFirstChild($food);
        $repo->persistAsNextSiblingOf($sport, $food);

        $this->em->flush();
    }

    /**
     * @test
     * @expectedException UnexpectedValueException
     */
    function shouldFailToPersistRootAsSiblingForRootBasedTree()
    {
        $food = new RootCategory;
        $food->setTitle('Food');

        $sport = new RootCategory;
        $sport->setTitle('Sport');

        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $repo->persistAsFirstChild($food);
        $repo->persistAsNextSiblingOf($sport, $food);

        $this->em->flush();
    }

    public function testTreeChildPositionMove2()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $oranges = $repo->findOneByTitle('Oranges');
        $meat = $repo->findOneByTitle('Meat');

        $this->assertEquals(2, $oranges->getLevel());
        $this->assertEquals(7, $oranges->getLeft());
        $this->assertEquals(8, $oranges->getRight());

        $repo->persistAsNextSiblingOf($meat, $oranges);
        $this->em->flush();

        $oranges = $repo->findOneByTitle('Oranges');
        $meat = $repo->findOneByTitle('Meat');

        $this->assertEquals(7, $oranges->getLeft());
        $this->assertEquals(8, $oranges->getRight());

        //Normal test that pass
        $this->assertEquals(9, $meat->getLeft());
        $this->assertEquals(10, $meat->getRight());

        // Raw query to show the issue #108 with wrong left value by Doctrine
        $dql = 'SELECT c FROM ' . self::ROOT_CATEGORY . ' c';
        $dql .= ' WHERE c.id = 5'; //5 == meat
        $meat_array = $this->em->createQuery($dql)->getScalarResult();

        $this->assertEquals(9, $meat_array[0]['c_lft']);
        $this->assertEquals(10, $meat_array[0]['c_rgt']);
        $this->assertEquals(2, $meat_array[0]['c_level']);
    }

    public function testTreeChildPositionMove3()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $oranges = $repo->findOneByTitle('Oranges');
        $milk = $repo->findOneByTitle('Milk');

        $this->assertEquals(2, $oranges->getLevel());
        $this->assertEquals(7, $oranges->getLeft());
        $this->assertEquals(8, $oranges->getRight());

        $repo->persistAsNextSiblingOf($milk, $oranges);
        $this->em->flush();

        $this->assertEquals(7, $oranges->getLeft());
        $this->assertEquals(8, $oranges->getRight());

        //Normal test that pass
        $this->assertEquals(9, $milk->getLeft());
        $this->assertEquals(10, $milk->getRight());

        // Raw query to show the issue #108 with wrong left value by Doctrine
        $dql = 'SELECT c FROM ' . self::ROOT_CATEGORY . ' c';
        $dql .= ' WHERE c.id = 4 '; //4 == Milk
        $milk_array = $this->em->createQuery($dql)->getScalarResult();
        $this->assertEquals(9, $milk_array[0]['c_lft']);
        $this->assertEquals(10, $milk_array[0]['c_rgt']);
        $this->assertEquals(2, $milk_array[0]['c_level']);
    }

    public function testPositionedUpdates()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $citrons = $repo->findOneByTitle('Citrons');
        $vegitables = $repo->findOneByTitle('Vegitables');

        $repo->persistAsNextSiblingOf($vegitables, $citrons);
        $this->em->flush();

        $this->assertEquals(5, $vegitables->getLeft());
        $this->assertEquals(6, $vegitables->getRight());
        $this->assertEquals(2, $vegitables->getParent()->getId());

        $fruits = $repo->findOneByTitle('Fruits');
        $this->assertEquals(2, $fruits->getLeft());
        $this->assertEquals(9, $fruits->getRight());

        $milk = $repo->findOneByTitle('Milk');
        $repo->persistAsFirstChildOf($milk, $fruits);
        $this->em->flush();

        $this->assertEquals(3, $milk->getLeft());
        $this->assertEquals(4, $milk->getRight());

        $this->assertEquals(2, $fruits->getLeft());
        $this->assertEquals(11, $fruits->getRight());
    }

    public function testTreeChildPositionMove()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $oranges = $repo->findOneByTitle('Oranges');
        $fruits = $repo->findOneByTitle('Fruits');

        $this->assertEquals(2, $oranges->getLevel());

        $repo->persistAsNextSiblingOf($oranges, $fruits);
        $this->em->flush();

        $this->assertEquals(1, $oranges->getLevel());
        $this->assertCount(1, $repo->children($fruits, true));

        $vegies = $repo->findOneByTitle('Vegitables');
        $this->assertEquals(2, $vegies->getLeft());
        $repo->persistAsNextSiblingOf($vegies, $fruits);
        $this->em->flush();

        $this->assertEquals(6, $vegies->getLeft());
        $this->em->flush();
        $this->assertEquals(6, $vegies->getLeft());
    }

    public function testOnRootCategory()
    {
        // need to check if this does not produce errors
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $fruits = new RootCategory;
        $fruits->setTitle('Fruits');

        $vegitables = new RootCategory;
        $vegitables->setTitle('Vegitables');

        $milk = new RootCategory;
        $milk->setTitle('Milk');

        $meat = new RootCategory;
        $meat->setTitle('Meat');

        $repo
            ->persistAsFirstChild($fruits)
            ->persistAsFirstChild($vegitables)
            ->persistAsLastChild($milk)
            ->persistAsLastChild($meat);

        $cookies = new RootCategory;
        $cookies->setTitle('Cookies');

        $drinks = new RootCategory;
        $drinks->setTitle('Drinks');

        $repo
            ->persistAsNextSibling($cookies)
            ->persistAsPrevSibling($drinks);

        $this->em->flush();
        $dql = 'SELECT COUNT(c) FROM ' . self::ROOT_CATEGORY . ' c';
        $dql .= ' WHERE c.lft = 1 AND c.rgt = 2 AND c.parent IS NULL AND c.level = 0';
        $count = $this->em->createQuery($dql)->getSingleScalarResult();
        $this->assertEquals(6, $count);

        $repo = $this->em->getRepository(self::CATEGORY);

        $fruits = new Category;
        $fruits->setTitle('Fruits');

        $vegitables = new Category;
        $vegitables->setTitle('Vegitables');

        $milk = new Category;
        $milk->setTitle('Milk');

        $meat = new Category;
        $meat->setTitle('Meat');

        $repo
            ->persistAsFirstChild($fruits)
            ->persistAsFirstChild($vegitables)
            ->persistAsLastChild($milk)
            ->persistAsLastChild($meat);

        $cookies = new Category;
        $cookies->setTitle('Cookies');

        $drinks = new Category;
        $drinks->setTitle('Drinks');

        $repo
            ->persistAsNextSibling($cookies)
            ->persistAsPrevSibling($drinks);

        $this->em->flush();
        $dql = 'SELECT COUNT(c) FROM ' . self::CATEGORY . ' c';
        $dql .= ' WHERE c.parentId IS NULL AND c.level = 0';
        $dql .= ' AND c.lft BETWEEN 1 AND 11';
        $count = $this->em->createQuery($dql)->getSingleScalarResult();
        $this->assertEquals(6, $count);
    }

    public function testRootTreePositionedInserts()
    {
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        // test child positioned inserts
        $food = new RootCategory;
        $food->setTitle('Food');

        $fruits = new RootCategory;
        $fruits->setTitle('Fruits');

        $vegitables = new RootCategory;
        $vegitables->setTitle('Vegitables');

        $milk = new RootCategory;
        $milk->setTitle('Milk');

        $meat = new RootCategory;
        $meat->setTitle('Meat');

        $repo
            ->persistAsFirstChild($food)
            ->persistAsFirstChildOf($fruits, $food)
            ->persistAsFirstChildOf($vegitables, $food)
            ->persistAsLastChildOf($milk, $food)
            ->persistAsLastChildOf($meat, $food);

        $this->em->flush();

        $this->assertEquals(4, $fruits->getLeft());
        $this->assertEquals(5, $fruits->getRight());

        $this->assertEquals(2, $vegitables->getLeft());
        $this->assertEquals(3, $vegitables->getRight());

        $this->assertEquals(6, $milk->getLeft());
        $this->assertEquals(7, $milk->getRight());

        $this->assertEquals(8, $meat->getLeft());
        $this->assertEquals(9, $meat->getRight());

        // test sibling positioned inserts
        $cookies = new RootCategory;
        $cookies->setTitle('Cookies');

        $drinks = new RootCategory;
        $drinks->setTitle('Drinks');

        $repo
            ->persistAsNextSiblingOf($cookies, $milk)
            ->persistAsPrevSiblingOf($drinks, $milk);

        $this->em->flush();

        $this->assertEquals(6, $drinks->getLeft());
        $this->assertEquals(7, $drinks->getRight());

        $this->assertEquals(10, $cookies->getLeft());
        $this->assertEquals(11, $cookies->getRight());

        $this->assertTrue($repo->verify());
    }

    public function testSimpleTreePositionedInserts()
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        // test child positioned inserts
        $food = new Category;
        $food->setTitle('Food');
        $repo->persistAsFirstChild($food);

        $fruits = new Category;
        $fruits->setTitle('Fruits');
        $fruits->setParent($food);
        $repo->persistAsFirstChild($fruits);

        $vegitables = new Category;
        $vegitables->setTitle('Vegitables');
        $vegitables->setParent($food);
        $repo->persistAsFirstChild($vegitables);

        $milk = new Category;
        $milk->setTitle('Milk');
        $milk->setParent($food);
        $repo->persistAsLastChild($milk);

        $meat = new Category;
        $meat->setTitle('Meat');
        $meat->setParent($food);
        $repo->persistAsLastChild($meat);

        $this->em->flush();

        $this->assertEquals(4, $fruits->getLeft());
        $this->assertEquals(5, $fruits->getRight());

        $this->assertEquals(2, $vegitables->getLeft());
        $this->assertEquals(3, $vegitables->getRight());

        $this->assertEquals(6, $milk->getLeft());
        $this->assertEquals(7, $milk->getRight());

        $this->assertEquals(8, $meat->getLeft());
        $this->assertEquals(9, $meat->getRight());

        // test sibling positioned inserts
        $cookies = new Category;
        $cookies->setTitle('Cookies');
        $cookies->setParent($milk);
        $repo->persistAsNextSibling($cookies);

        $drinks = new Category;
        $drinks->setTitle('Drinks');
        $drinks->setParent($milk);
        $repo->persistAsPrevSibling($drinks);

        $this->em->flush();

        $this->assertEquals(6, $drinks->getLeft());
        $this->assertEquals(7, $drinks->getRight());

        $this->assertEquals(10, $cookies->getLeft());
        $this->assertEquals(11, $cookies->getRight());

        $this->assertTrue($repo->verify());
    }

    private function populate()
    {
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $food = new RootCategory;
        $food->setTitle('Food');

        $fruits = new RootCategory;
        $fruits->setTitle('Fruits');

        $vegitables = new RootCategory;
        $vegitables->setTitle('Vegitables');

        $milk = new RootCategory;
        $milk->setTitle('Milk');

        $meat = new RootCategory;
        $meat->setTitle('Meat');

        $oranges = new RootCategory;
        $oranges->setTitle('Oranges');

        $citrons = new RootCategory;
        $citrons->setTitle('Citrons');

        $repo
            ->persistAsFirstChild($food)
            ->persistAsFirstChildOf($fruits, $food)
            ->persistAsFirstChildOf($vegitables, $food)
            ->persistAsLastChildOf($milk, $food)
            ->persistAsLastChildOf($meat, $food)
            ->persistAsFirstChildOf($oranges, $fruits)
            ->persistAsFirstChildOf($citrons, $fruits);

        $this->em->flush();
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY,
            self::ROOT_CATEGORY
        );
    }
}
