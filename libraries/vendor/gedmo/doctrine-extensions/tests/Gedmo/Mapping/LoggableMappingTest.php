<?php

namespace Gedmo\Loggable;

use Doctrine\Common\Util\Debug,
    Doctrine\ORM\Mapping\Driver\YamlDriver,
    Doctrine\ORM\Mapping\Driver\DriverChain,
    Mapping\Fixture\Yaml\Category,
    Gedmo\Mapping\ExtensionMetadataFactory;

/**
 * These are mapping tests for tree extension
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeMappingTest extends \PHPUnit_Framework_TestCase
{
    const YAML_CATEGORY = 'Mapping\Fixture\Yaml\Category';
    private $em;

    public function setUp()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $chainDriverImpl = new DriverChain;
        $chainDriverImpl->addDriver(
            new YamlDriver(array(__DIR__ . '/Driver/Yaml')), 
            'Mapping\Fixture\Yaml'
        );
        $config->setMetadataDriverImpl($chainDriverImpl);

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

        $evm = new \Doctrine\Common\EventManager();
        $evm->addEventSubscriber(new LoggableListener());
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
    }

    public function testLoggableMapping()
    {
        $meta = $this->em->getClassMetadata(self::YAML_CATEGORY);
        $cacheId = ExtensionMetadataFactory::getCacheId(self::YAML_CATEGORY, 'Gedmo\Loggable');
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);

        $this->assertArrayHasKey('loggable', $config);
        $this->assertTrue($config['loggable']);
        $this->assertArrayHasKey('logEntryClass', $config);
        $this->assertEquals('Gedmo\\Loggable\\Entity\\LogEntry', $config['logEntryClass']);
    }
}
