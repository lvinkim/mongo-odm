<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2018/7/25
 * Time: 9:01 PM
 */

namespace Lvinkim\MongoODM\Tests\Functional;


use Lvinkim\MongoODM\AnnotationParser;
use Lvinkim\MongoODM\Annotations\AbstractField;
use Lvinkim\MongoODM\Annotations\Field;
use PHPUnit\Framework\TestCase;
use Lvinkim\MongoODM\Tests\App\Entity\IsEntity;
use Lvinkim\MongoODM\Tests\App\Entity\IsNotEntity;
use Lvinkim\MongoODM\Tests\App\Entity\User;

class AnnotationParserTest extends TestCase
{

    /** @var AnnotationParser */
    private $annotationParser;

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function setUp()
    {
        $this->annotationParser = new AnnotationParser();
    }

    public function testIsEntityClass()
    {

        $isEntity = $this->annotationParser->isEntityClass(IsEntity::class);
        $this->assertEquals(true, $isEntity);

        $isEntity = $this->annotationParser->isEntityClass((new IsEntity()));
        $this->assertEquals(true, $isEntity);

        $isEntity = $this->annotationParser->isEntityClass(IsNotEntity::class);
        $this->assertEquals(false, $isEntity);

        $isEntity = $this->annotationParser->isEntityClass((new IsNotEntity()));
        $this->assertEquals(false, $isEntity);

    }

    /**
     * @throws \ReflectionException
     */
    public function testGetPropertyAnnotation()
    {

        $reflectClass = new \ReflectionClass(User::class);

        $types = array_merge([
            'id',
            'embedOne',
            'embedMany',
        ], Field::TYPES);

        $parserTypes = [];
        foreach ($reflectClass->getProperties() as $entityField) {
            if ($entityField->isStatic()) {
                continue;
            }
            $annotation = $this->annotationParser->getPropertyAnnotation($entityField);

            if ($annotation instanceof AbstractField) {
                $parserTypes[$annotation->type] = 1;
            }
        }

        $this->assertEquals(count($types), count($parserTypes));

    }
}