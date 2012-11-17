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

	private static function initialize() {
		if (self::$initialized)
			return;

		self::$namespaceArray = array(__DIR__ . "/src/Collapick/Models");

		AnnotationRegistry::registerFile(
				__DIR__ . "/vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php");
		AnnotationRegistry::registerAutoloadNamespace(
				'Symfony\\Component\\Validator', __DIR__ . '/vendor/symfony/validator/');
//		AnnotationRegistry::registerAutoloadNamespace(
//				'Gedmo', __DIR__ . '/vendor/gedmo/doctrine-extensions/lib/');
		
		

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
		$config->setProxyDir(sys_get_temp_dir());
		$config->setProxyNamespace('Proxy');
		$config->setAutoGenerateProxyClasses(false); // this can be based on production config.
		$config->setMetadataDriverImpl($driverChain);
		$config->setMetadataCacheImpl($cache);
		$config->setQueryCacheImpl($cache);

		// create event manager and hook prefered extension listeners
		$evm = new Doctrine\Common\EventManager();
		

		// timestampable
		$timestampableListener = new Gedmo\Timestampable\TimestampableListener();
		$timestampableListener->setAnnotationReader($cachedAnnotationReader);
		$evm->addEventSubscriber($timestampableListener);

	
		
		$ini_array = parse_ini_file("doctrine.ini", true);
		$parameters = $ini_array['parameters'];
		$connectionOptions = array(
			'driver' => $parameters['database_driver'],
			'dbname' => $parameters['database_name'],
			'user' => $parameters['database_user'],
			'password' => $parameters['database_password'],
			'host' => $parameters['database_host']);

		return EntityManager::create($connectionOptions, $config, $evm);
	}

}

