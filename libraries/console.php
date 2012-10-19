<?php

(@include_once __DIR__ . '/doctrine-config.php');
$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
			'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper(DoctrineC5Adapter::getEntityManager()->getConnection()),
			'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper(DoctrineC5Adapter::getEntityManager())
		));

\Doctrine\ORM\Tools\Console\ConsoleRunner::run($helperSet);