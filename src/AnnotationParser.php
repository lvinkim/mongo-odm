<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2018/7/25
 * Time: 9:00 PM
 */

namespace Lvinkim\MongoODM;


use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Lvinkim\MongoODM\Annotations\AbstractField;
use Lvinkim\MongoODM\Annotations\Entity;

class AnnotationParser
{
    /** @var AnnotationReader */
    private $annotationReader;

    private static $registered = false;

    /**
     * EntityConverter constructor.
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function __construct()
    {
        if (!self::$registered) {
            AnnotationRegistry::registerFile(__DIR__ . '/Annotations/AbstractField.php');
            AnnotationRegistry::registerFile(__DIR__ . '/Annotations/EmbedMany.php');
            AnnotationRegistry::registerFile(__DIR__ . '/Annotations/EmbedOne.php');
            AnnotationRegistry::registerFile(__DIR__ . '/Annotations/Entity.php');
            AnnotationRegistry::registerFile(__DIR__ . '/Annotations/Field.php');
            AnnotationRegistry::registerFile(__DIR__ . '/Annotations/Id.php');
            self::$registered = true;
        }

        $this->annotationReader = new AnnotationReader();
    }


    /**
     * 判断某个类名或对象是否声明为 Entity 类
     * @param $class mixed  类名|对象
     * @return bool
     */
    public function isEntityClass($class)
    {
        try {
            $refClass = new \ReflectionClass($class);
            $classAnnotation = $this->annotationReader->getClassAnnotation($refClass, Entity::class);
            if ($classAnnotation instanceof Entity) {
                return true;
            }
        } catch (\Exception $exception) {
            return false;
        }

        return false;
    }

    /**
     * @param $property
     * @return mixed
     */
    public function getPropertyAnnotation($property)
    {
        return $this->annotationReader->getPropertyAnnotation($property, AbstractField::class);
    }
}