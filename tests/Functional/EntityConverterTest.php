<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2018/7/25
 * Time: 3:52 PM
 */

namespace Tests\Functional;

use Lvinkim\MongoODM\EntityConverter;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;
use Tests\App\Entity\IsEntity;
use Tests\App\Entity\IsNotEntity;

class EntityConverterTest extends TestCase
{
    /** @var EntityConverter */
    private $entityConverter;

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function setUp()
    {
        $this->entityConverter = new EntityConverter();
    }

    /**
     * 使用 Entity 声明的类
     * 字段属性可以自由选择 public/private/protect ，但不能用 static/const
     */
    public function testDocumentToEntity()
    {
        $document = new \stdClass();
        $document->_id = new ObjectId();
        $document->name = 'name - ' . rand(100, 999);
        $document->age = (rand(10, 99));
        $document->weight = (66.6);
        $document->_parent = '_parent - ' . rand(100, 999);

        /** @var IsEntity $isEntity */
        $isEntity = $this->entityConverter->documentToEntity($document, IsEntity::class);

        $this->assertInstanceOf(IsEntity::class, $isEntity);

        $this->assertEquals($document->_id, $isEntity->id);     // 主键
        $this->assertEquals($document->name, $isEntity->name);  // public 属性
        $this->assertEquals($document->age, $isEntity->getAge());  // private 属性
        $this->assertEquals($document->weight, $isEntity->getWeight()); // protected 属性
        $this->assertNull($isEntity->getParent());                  // 没有使用 Field 声明的字段

        $this->assertInstanceOf(ObjectId::class, $isEntity->id);
        $this->assertTrue(is_string($isEntity->name));
        $this->assertTrue(is_int($isEntity->getAge()));
        $this->assertTrue(is_float($isEntity->getWeight()));

    }

    /**
     * 没有使用 Entity 声明的类，不会对字段做任何处理
     */
    public function testDocumentToNotEntity()
    {
        $document = new \stdClass();
        $document->_id = new ObjectId();
        $document->name = 'name - ' . rand(100, 999);

        $isNotEntity = $this->entityConverter->documentToEntity($document, IsNotEntity::class);

        $this->assertFalse(($isNotEntity instanceof IsNotEntity));
        $this->assertEquals($document, $isNotEntity);
    }

    /**
     * 使用 Entity 声明的类
     * 会持久化使用了 Field 声明的字段
     */
    public function testEntityToDocument()
    {
        $isEntity = new IsEntity();
        $isEntity->name = 'name - ' . rand(100, 999);   // public 属性
        $isEntity->setAge(rand(10, 99));                 // private 属性
        $isEntity->setWeight(66.6);             // protected 属性
        $isEntity->setParent('_parent - ' . rand(100, 999));    // 没有使用 Field 声明的字段

        $document = $this->entityConverter->entityToDocument($isEntity, IsEntity::class);

        $this->assertInstanceOf(ObjectId::class, $document->_id);   // 自动生成主键
        $this->assertEquals($isEntity->name, $document->name);
        $this->assertEquals($isEntity->getAge(), $document->age);
        $this->assertEquals($isEntity->getWeight(), $document->weight);
        $this->assertObjectNotHasAttribute('_parent', $document);// 没有使用 Field 声明的字段，不会被持久化

        $this->assertTrue(is_string($document->name));
        $this->assertTrue(is_int($document->age));
        $this->assertTrue(is_float($document->weight));
    }

    /**
     * 没有使用 Entity 声明的类，不对字段做任何处理
     */
    public function testNotEntityToDocument()
    {
        $isNotEntity = new IsNotEntity();
        $isNotEntity->name = 'name - ' . rand(100, 999);

        $document = $this->entityConverter->entityToDocument($isNotEntity, IsNotEntity::class);

        $this->assertInstanceOf(IsNotEntity::class, $document);
        $this->assertEquals($document, $isNotEntity);
    }

    /**
     * 新增记录持久化成功后，将新的 _id 设置到对应的 Entity 对象中
     */
    public function testSetId()
    {
        $document = new \stdClass();
        $document->_id = new ObjectId();

        $isEntity = new IsEntity();
        $succeed = $this->entityConverter->setId($isEntity, $document->_id);

        $this->assertTrue($succeed);
        $this->assertEquals($document->_id, $isEntity->id);

        return $isEntity;
    }

    /**
     * 从任意 Entity 中获取 id 值
     * @param IsEntity $isEntity
     * @depends testSetId
     */
    public function testGetId(IsEntity $isEntity)
    {
        $id = $this->entityConverter->getId($isEntity);

        $this->assertEquals($id, $isEntity->id);
    }

}