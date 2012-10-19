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
	
	
	public function testEntityManager(){
	
		global $em;
		/* @var $em \Doctrine\ORM\EntityManager */
		
		$this->assertNotNull($em->createQuery());
	}
	
	
	public function testSave(){
		
		
		global $em;
		
		$post = new Post();
		$post->setBody('Testataan tallentamista');
		$post->setTitle('jee');
		$em->persist($post);
		$em->flush();
		
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