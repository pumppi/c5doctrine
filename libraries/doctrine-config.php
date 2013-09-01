<?php

(@include_once __DIR__ . '/vendor/autoload.php');

use Doctrine\ORM\EntityManager,
	Doctrine\Common\Annotations\AnnotationRegistry,
	Doctrine\ORM\Configuration;

class DoctrineC5Adapter {

	private static $entityManager = false;
	private static $initialized = false;
	private static $applicationMode = "development";
	private static $namespaceArray = array();
	private static $DB_PARAMETER = array();




	private static function initialize() {
		if (self::$initialized)
			return;

		self::$namespaceArray = array(__DIR__ . "/src/Collapick/Models");

		AnnotationRegistry::registerFile(
				__DIR__ . "/vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php");
		AnnotationRegistry::registerAutoloadNamespace(
				'Symfony\\Component\\Validator', __DIR__ . '/vendor/symfony/validator/');
		
		
		
			$DB_PARAMETER['database_driver'] = 'pdo_mysql';
			$DB_PARAMETER['database_name'] = 'doctrine';
			$DB_PARAMETER['database_user'] = 'root';
			$DB_PARAMETER['database_password'] = 'root';
			$DB_PARAMETER['database_host'] = 'localhost';
		
		

		self::$DB_PARAMETER = $DB_PARAMETER;	 


		$em = self::setupDoctrine();
		self::$entityManager = $em;
		self::$initialized = true;
	}

	/**
	 * 
	 * @return EntityManager
	 */
	public static function getEntityManager() {
		self::initialize();
		return self::$entityManager;
	}

	/**
	 * Setting up doctrine and doctrine extensions
	 * See more from here https://github.com/l3pp4rd/DoctrineExtensions/blob/master/example/em.php
	 * 
	 * @return EntityManager
	 */
	private static function setupDoctrine() {
		
		
		if (self::$applicationMode == "development") {
			$cache = new \Doctrine\Common\Cache\ArrayCache;
		} else {
			$cache = new \Doctrine\Common\Cache\ApcCache;
		}


		// standard annotation reader
		$annotationReader = new Doctrine\Common\Annotations\AnnotationReader();
		$cachedAnnotationReader = new Doctrine\Common\Annotations\CachedReader(
						$annotationReader, // use reader
						$cache // and a cache driver
		);
		$annotationDriver = new Doctrine\ORM\Mapping\Driver\AnnotationDriver(
						$cachedAnnotationReader, // our cached annotation reader
						self::$namespaceArray // paths to look in
		);
		
		// With driver cahin you can load different kind of mappings
		$driverChain = new Doctrine\ORM\Mapping\Driver\DriverChain();
		$driverChain->addDriver($annotationDriver, 'Collapick\Models');
		
		//Autoload all annotations, some issue with @package comment
		Gedmo\DoctrineExtensions::registerAnnotations();
		 
		// general ORM configurationI
		$config = new Doctrine\ORM\Configuration();
		$config->setProxyDir(__DIR__ . "/proxy");
		$config->setProxyNamespace('Proxy');
		$config->setAutoGenerateProxyClasses(true); // this can be based on production config.
		$config->setMetadataDriverImpl($driverChain);
		$config->setMetadataCacheImpl($cache);
		$config->setQueryCacheImpl($cache);

		// create event manager and hook prefered extension listeners
		$evm = new Doctrine\Common\EventManager();
		

		// timestampable
		$timestampableListener = new Gedmo\Timestampable\TimestampableListener();
		$timestampableListener->setAnnotationReader($cachedAnnotationReader);
		$evm->addEventSubscriber($timestampableListener);

		$parmas = self::$DB_PARAMETER;
	
		$connectionOptions = array(
			'driver' => $parmas['database_driver'],
			'dbname' => $parmas['database_name'],
			'user' => $parmas['database_user'],
			'password' => $parmas['database_password'],
			'host' => $parmas['database_host']);

		return EntityManager::create($connectionOptions, $config, $evm);
	}

}

