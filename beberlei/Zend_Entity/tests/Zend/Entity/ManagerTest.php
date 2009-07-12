<?php

require_once dirname(__FILE__)."/../TestHelper.php";
require_once "TestCase.php";

class Zend_Entity_ManagerTest extends Zend_Entity_TestCase
{
    public function testConstructingManagerRequiresDatabaseConnection()
    {
        $manager = $this->createEntityManager();

        $this->assertEquals($this->getDatabaseConnection(), $manager->getAdapter());
    }

    public function testGetDefaultIdentityMap()
    {
        $manager = $this->createEntityManager();
        
        $this->assertTrue( $manager->getIdentityMap() instanceof Zend_Entity_IdentityMap );
    }

    public function testSetGetIdentityMap()
    {
        $manager = $this->createEntityManager();
        $map = new Zend_Entity_IdentityMap();

        $manager->setIdentityMap($map);
        $this->assertEquals($map, $manager->getIdentityMap());
    }

    public function testSettingNoResourceMapThrowsExceptionOnGet()
    {
        $this->setExpectedException("Zend_Entity_Exception");
        $manager = new Zend_Entity_Manager($this->getDatabaseConnection());
        
        $manager->getMetadataFactory();
    }

    public function testSetGetResourceMapEqualsReference()
    {
        $manager = $this->createEntityManager();
        $resourceMap = new Zend_Entity_MetadataFactory_Code("path");
        
        $manager->setMetadataFactory($resourceMap);
        $this->assertEquals($resourceMap, $manager->getMetadataFactory());
    }

    public function testSetResourceThroughConstructorOptionsArray()
    {
        $resourceMap = new Zend_Entity_MetadataFactory_Code("path");
        $manager = $this->createEntityManager(null, $resourceMap, null);
        
        $this->assertEquals($resourceMap, $manager->getMetadataFactory());
    }

    public function testGetMapperByEntityStringNameReturnsMapperOnValidCall()
    {
        $entityName = "Zend_TestEntity1";
        $metadataFactory = $this->createMetadataFactory($entityName);
        $manager = $this->createEntityManager(null, $metadataFactory);
        $mapper  = $manager->getMapperByEntity($entityName);
        
        $this->assertTrue($mapper instanceof Zend_Entity_Mapper);
    }

    private function createMetadataFactory($entityName)
    {
        $definitionMock = $this->getMock('Zend_Entity_Mapper_Definition_Entity');
        $metadataFactory = $this->getMock('Zend_Entity_MetadataFactory_Interface');
        $metadataFactory->expects($this->once())
                    ->method('getDefinitionByEntityName')
                    ->with($this->equalTo($entityName))
                    ->will($this->returnValue($definitionMock));
        return $metadataFactory;
    }

    public function testGetMapperByEntityClassNameReturnsMapperOnValidCall()
    {
        $entity = new Zend_TestEntity1();
        $entityName = "Zend_TestEntity1";
        $metadataFactory = $this->createMetadataFactory($entityName);
        $manager = $this->createEntityManager(null, $metadataFactory);
        $mapper  = $manager->getMapperByEntity($entity);
        
        $this->assertTrue($mapper instanceof Zend_Entity_Mapper);
    }

    public function testManagerShouldDelegateBeginTransactionToAdapter()
    {
        $db = $this->getMock('Zend_Entity_DbAdapterMock');
        $db->expects($this->once())
           ->method('beginTransaction');
        $manager = $this->createEntityManager(null, null, null, $db);

        $manager->beginTransaction();
    }

    public function testManagerShouldDelegateCommitToAdapter()
    {
        $db = $this->getMock('Zend_Entity_DbAdapterMock');
        $db->expects($this->once())
           ->method('commit');
        $manager = $this->createEntityManager(null, null, null, $db);
        
        $manager->commit();
    }
    public function testManagerShouldDelegateRollbackToAdapter()
    {
        $db = $this->getMock('Zend_Entity_DbAdapterMock');
        $db->expects($this->once())
           ->method('rollBack');
        $manager = $this->createEntityManager(null, null, null, $db);
        
        $manager->rollBack();
    }

    public function testManagerContainsProxyToIdentityMap()
    {
        $identityMap = $this->getMock('Zend_Entity_IdentityMap');
        $identityMap->expects($this->once())
                    ->method('contains')
                    ->will($this->returnValue(true));

        $entity = new Zend_TestEntity1;

        $manager = $this->createEntityManager(null, null, $identityMap);

        $this->assertTrue($manager->contains($entity));
    }

    public function testManagerClearCallsIdentityMapClear()
    {
        $identityMap = $this->createIdentityMapMock(self::IDENTITY_MOCK_CLEAR);
        $manager = $this->createEntityManager(null, null, $identityMap);
        
        $manager->clear();
    }

    public function testCloseConnectionDelegatesToAdapter()
    {
        $db = $this->createDatabaseConnectionMock();
        $db->expects($this->once())
           ->method('closeConnection')
           ->will($this->returnValue(true));
        $manager = new Zend_Entity_Manager($db);
        
        $manager->close();
    }

    public function testSaveIsDelegatedToMapper()
    {
        $entity = new Zend_TestEntity1();
        $className = get_class($entity);

        $mapperMock = $this->createMapperMock();
        $mapperMock->expects($this->once())
                   ->method('save')
                   ->with($this->equalTo($entity));

        $manager = $this->createTestingEntityManager();
        $manager->addMapper($className, $mapperMock);

        $manager->save($entity);
    }

    public function testDeleteIsDelegatedToMapper()
    {
        $entity = new Zend_TestEntity1();
        $className = get_class($entity);

        $mapperMock = $this->createMapperMock();
        $mapperMock->expects($this->once())
                   ->method('delete')
                   ->with($this->equalTo($entity));


        $manager = $this->createTestingEntityManager();
        $manager->addMapper($className, $mapperMock);

        $manager->delete($entity);
    }

    const UNKNOWN_ENTITY_CLASS = 'UnknownEntityClass';
    const KNOWN_ENTITY_CLASS = 'KnownEntityClass';

    public function testCreateNativeQuery_FromUnknownEntityMapper_ThrowsException()
    {
        $this->setExpectedException("Exception");
        $e = new Exception();

        $metadataFactory = $this->getMock('Zend_Entity_MetadataFactory_Interface');
        $metadataFactory->expects($this->once())
                        ->method('getDefinitionEntityNames')
                        ->will($this->returnValue(array(self::KNOWN_ENTITY_CLASS)));
        $manager = $this->createEntityManager(null, $metadataFactory);

        $manager->createNativeQuery(self::UNKNOWN_ENTITY_CLASS);
    }

    public function testCreateNativeQuery()
    {
        $metadataFactory = $this->getMock('Zend_Entity_MetadataFactory_Interface');
        $metadataFactory->expects($this->once())
                        ->method('getDefinitionEntityNames')
                        ->will($this->returnValue(array(self::KNOWN_ENTITY_CLASS)));

        $manager = $this->createTestingEntityManager(null, $metadataFactory);
        $select = $this->createDbSelect(self::KNOWN_ENTITY_CLASS);

        $mapper = $this->createMapperMock();
        $mapper->expects($this->any())
               ->method('select')
               ->will($this->returnValue($select));
        $mapper->expects($this->once())
               ->method('getLoader')
               ->will($this->returnValue($this->getMock('Zend_Entity_Mapper_Loader_Interface')));
        $manager->addMapper(self::KNOWN_ENTITY_CLASS, $mapper);

        $query = $manager->createNativeQuery(self::KNOWN_ENTITY_CLASS);

        $this->assertType('Zend_Entity_Query_AbstractQuery', $query);
    }

    public function testCreateQuery()
    {
        $this->markTestIncomplete();
    }

    public function createEntityManagerWithMapper($select)
    {
        $manager = $this->createTestingEntityManager();

        $loader = $this->getMock('Zend_Entity_Mapper_Loader_Interface');
        $loader->expects($this->once())
               ->method('processResultset');

        $mapper = $this->createMapperMock();
        $mapper->expects($this->any())->method('select')->will($this->returnValue($select));
        $mapper->expects($this->once())
               ->method('getLoader')
               ->will($this->returnValue($loader));

        $manager->addMapper(self::KNOWN_ENTITY_CLASS, $mapper);

        return $manager;
    }
}