<?php

abstract class Zend_Entity_Mapper_Loader_OneToManyFixture extends Zend_Entity_Mapper_Loader_TestCase
{
    const TEST_A_CLASS = 'Zend_TestEntity1';
    const TEST_A_TABLE = 'table_a';
    const TEST_A_ID = 'id';
    const TEST_A_ID_COLUMN = 'a_id';
    const TEST_A_ONETOMANY = 'onetomany';

    const TEST_B_CLASS = 'Zend_TestEntity2';
    const TEST_B_TABLE = 'table_b';
    const TEST_B_ID = 'id';
    const TEST_B_ID_COLUMN = 'b_id';
    const TEST_B_FOREIGN_KEY = 'b_fkey';

    public function setUp()
    {
        $this->resourceMap = new Zend_Entity_Resource_Testing();
        $this->resourceMap->addDefinition( $this->createClassBDefinition() );
        $this->resourceMap->addDefinition( $this->createClassADefinition() );
    }

    abstract public function createLoader($definition);

    /**
     * @return Zend_Entity_Mapper_Loader_Interface
     */
    public function getClassALoader()
    {
        return $this->createLoader($this->resourceMap->getDefinitionByEntityName(self::TEST_A_CLASS));
    }

    /**
     * @return Zend_Entity_Mapper_Loader_Interface
     */
    public function getClassBLoader()
    {
        return $this->createLoader($this->resourceMap->getDefinitionByEntityName(self::TEST_B_CLASS));
    }
    
    public function createClassADefinition()
    {
        $def = new Zend_Entity_Mapper_Definition_Entity(self::TEST_A_CLASS);
        $def->setTable(self::TEST_A_TABLE);

        $def->addPrimaryKey(self::TEST_A_ID, array('columnName' => self::TEST_A_ID_COLUMN));
        $def->addCollection(self::TEST_A_ONETOMANY, array(
            'relation' => new Zend_Entity_Mapper_Definition_OneToManyRelation(self::TEST_A_ONETOMANY, array(
                'class' => self::TEST_B_CLASS,
                'foreignKey' => self::TEST_B_FOREIGN_KEY,
            )),
            'key' => self::TEST_B_FOREIGN_KEY,
        ));

        return $def;
    }

    public function createClassBDefinition()
    {
        $def = new Zend_Entity_Mapper_Definition_Entity(self::TEST_B_CLASS);
        $def->setTable(self::TEST_B_TABLE);
        $def->addPrimaryKey(self::TEST_B_ID, array('columnName' => self::TEST_B_ID_COLUMN));

        return $def;
    }
}