<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2018/7/25
 * Time: 3:52 PM
 */

namespace Tests\Functional;

use Lvinkim\MongoODM\EntityConverter;
use PHPUnit\Framework\TestCase;
use Tests\App\Entity\IsEntity;
use Tests\App\Entity\IsEntityWithString;
use Tests\App\Entity\IsNotEntity;
use Tests\App\Entity\IsNotEntityWithString;

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

    public function testDocumentToNotEntityWithString()
    {
        $isNotEntityWithStringDocument = new \stdClass();
        $isNotEntityWithStringDocument->name = 'name';

        $isNotEntityWithString = $this->entityConverter->documentToEntity($isNotEntityWithStringDocument, IsNotEntityWithString::class);
        $this->assertInstanceOf(\stdClass::class, $isNotEntityWithString);
        $this->assertEquals($isNotEntityWithStringDocument->name, $isNotEntityWithString->name);
    }

    public function testDocumentToEntityWithString()
    {
        $isEntityWithStringDocument = new \stdClass();
        $isEntityWithStringDocument->name = 'name';

        /** @var IsEntityWithString $isEntityWithString */
        $isEntityWithString = $this->entityConverter->documentToEntity($isEntityWithStringDocument, IsEntityWithString::class);
        $this->assertInstanceOf(IsEntityWithString::class, $isEntityWithString);
        $this->assertEquals($isEntityWithStringDocument->name, $isEntityWithString->getName());
    }


}