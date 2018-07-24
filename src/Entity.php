<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 14/07/2018
 * Time: 2:27 PM
 */

namespace Lvinkim\MongoODM;


use Doctrine\Common\Annotations\AnnotationReader;
use Lvinkim\MongoODM\Annotations\AbstractField;
use MongoDB\BSON\ObjectId;

/**
 * Entity 基础类
 * Class Entity
 * @package Lvinkim\MongoODM
 */
abstract class Entity implements EntityInterface
{
    /** @var EntityConverter */
    private $entityConverter;

    public function __construct()
    {
        $this->entityConverter = new EntityConverter();
    }

    /**
     * 返回这个 entity 对应的 Mongodb Document
     * @return \stdClass
     */
    public function getDocument(): \stdClass
    {
        $document = new \stdClass();
        try {
            $class = new \ReflectionClass($this);
            $reader = new AnnotationReader();
        } catch (\Exception $exception) {
            return $document;
        }

        foreach ($class->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            /** @var AbstractField $annotation */
            $annotation = $reader->getPropertyAnnotation($property, AbstractField::class);

            $property->setAccessible(true);

            $propertyValue = $property->getValue($this);
            $documentValue = $this->entityConverter->propertyToDocument($annotation, $propertyValue);

            $propertyName = $property->getName();
            $document->{$propertyName} = $documentValue;
        }
        return $document;
    }

    /**
     * 通过 Mongodb Document 来设置 Entity 记录
     * @param \stdClass $document
     * @return $this
     */
    public function setByDocument(\stdClass $document)
    {
        try {
            $class = new \ReflectionClass($this);
            $reader = new AnnotationReader();
        } catch (\Exception $exception) {
            return $this;
        }

        foreach ($class->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            /** @var AbstractField $annotation */
            $annotation = $reader->getPropertyAnnotation($property, AbstractField::class);

            $propertyName = $property->getName();
            $documentValue = $document->{$propertyName} ?? null;
            $propertyValue = $this->entityConverter->documentToProperty($annotation, $documentValue);

            $property->setAccessible(true);
            $property->setValue($this, $propertyValue);
        }

        return $this;
    }

    abstract public function getId();

    abstract public function setId($id);
}