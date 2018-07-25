<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2018/7/25
 * Time: 9:01 PM
 */

namespace Tests\Functional;


use Lvinkim\MongoODM\AnnotationParser;
use PHPUnit\Framework\TestCase;
use Tests\App\Entity\IsEntity;
use Tests\App\Entity\IsNotEntity;

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
}