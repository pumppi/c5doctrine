<?php

namespace Collapick\Repositories;

use Doctrine\ORM\EntityRepository;
use Models\Post;

class PostRepository extends EntityRepository {

	/**
	 * Initializes a new <tt>EntityRepository</tt>.
	 *
	 * @param EntityManager $em The EntityManager to use.
	 * @param ClassMetadata $classMetadata The class descriptor.
	 */
	public function __construct($em, Mapping\ClassMetadata $class) {
		parent::__construct($em, $class);
	}

	public function findByTitle($title = '') {
		$result = array();
		$qb = $this->createQueryBuilder('p')
				->where('p.title LIKE :title')
				->setParameter('title', $title);

		try {
			$result = $qb->getQuery()->getResult();
		} catch (Exception $exc) {
			echo $exc->getTraceAsString();
		}


		return $result;
	}

}