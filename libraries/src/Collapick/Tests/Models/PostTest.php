<?php

namespace Collapick\Tests\Models;

use Collapick\Models\Post;
use Symfony\Component\Validator\Validation;

class PostTest extends \PHPUnit_Framework_TestCase {

	protected $post;
	protected $em;

	protected function setUp() {
		$this->em = $this->getMock('em');
	}

	protected function tearDown() {
		
	}
	/**
	 * 
	 * @global type $em
	 * @return \Doctrine\ORM\EntityManager
	 */
	protected function getEntityManager(){
		global $em;
		/* @var $em \Doctrine\ORM\EntityManager */
		return $em;
	}




	public function testEntityManager(){
		$this->assertNotNull($this->getEntityManager()->createQuery());
	}
	
	
	public function testSave(){
		$post = new Post();
		$post->setBody('Description for post');
		$post->setTitle('Title for post');
		$this->getEntityManager()->persist($post);
		$this->getEntityManager()->flush();
		
		$this->assertNotNull($post->getId());
	}
	
	
	
	 
	public function testDoesValidatorWork(){
		$validator = Validation::createValidatorBuilder()
		->enableAnnotationMapping()
		->getValidator();

		$post = new Post();
		
		
		/* @var $violations Symfony\Component\Validator\ConstraintViolationList */
		$violations = $validator->validate($post);
		
		
		$this->assertTrue($violations->count() > 0);
		
	}
	function testCanCreateAPost() {
		$post = new Post();
		$this->assertNotNull($post);
	}

}