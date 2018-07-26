<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 15/07/2018
 * Time: 12:35 AM
 */

namespace Lvinkim\MongoODM;


use Lvinkim\MongoODM\Annotations\AbstractField;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

/**
 * 负责 Entity 对象与 Mongodb Document 对象之间转换
 * Class EntityConverter
 * @package Lvinkim\MongoODM
 */
class EntityConverter
{
    /** @var AnnotationParser */
    private $annotationParser;

    /**
     * EntityConverter constructor.
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function __construct()
    {
        $this->annotationParser = new AnnotationParser();
    }

    /**
     * @param $entity
     * @return mixed|null
     */
    public function getId($entity)
    {
        if (!$this->annotationParser->isEntityClass($entity)) {
            return $entity->_id ?? null;
        }
        try {
            $reflectClass = new \ReflectionClass($entity);
        } catch (\Exception $exception) {
            return null;
        }

        foreach ($reflectClass->getProperties() as $entityField) {
            if ($entityField->isStatic()) {
                continue;
            }
            $annotation = $this->annotationParser->getPropertyAnnotation($entityField);
            if (null === $annotation || !$annotation->id) {
                continue;
            }
            $entityField->setAccessible(true);
            return $entityField->getValue($entity);
        }
        return null;
    }

    /**
     * @param $entity
     * @param $documentId
     * @return bool
     */
    public function setId($entity, $documentId)
    {
        if (!$this->annotationParser->isEntityClass($entity)) {
            return false;
        }
        try {
            $reflectClass = new \ReflectionClass($entity);
        } catch (\Exception $exception) {
            return false;
        }
        foreach ($reflectClass->getProperties() as $entityField) {
            if ($entityField->isStatic()) {
                continue;
            }
            $annotation = $this->annotationParser->getPropertyAnnotation($entityField);
            if (null === $annotation || !$annotation->id) {
                continue;
            }
            $entityField->setAccessible(true);
            $entityField->setValue($entity, $documentId);
            return true;
        }
        return false;
    }

    /**
     * 将 mongodb document 转换成 entity 类
     * @param mixed $document
     * @param string $entityClassName
     * @return mixed
     */
    public function documentToEntity($document, string $entityClassName)
    {
        if (!$this->annotationParser->isEntityClass($entityClassName)) {
            return $document;
        }

        try {
            $reflectClass = new \ReflectionClass($entityClassName);
        } catch (\Exception $exception) {
            return $document;
        }

        $entity = new $entityClassName();

        foreach ($reflectClass->getProperties() as $documentProperty) {
            if ($documentProperty->isStatic()) {
                continue;
            }

            $annotation = $this->annotationParser->getPropertyAnnotation($documentProperty);
            if (null === $annotation) {
                continue;
            }

            $propertyName = $annotation->name ?? $documentProperty->getName();
            if ($annotation->id) {
                $propertyName = '_id';
            }
            $propertyValue = $document->{$propertyName} ?? null;
            $fieldValue = $this->propertyToField($propertyValue, $annotation);

            $documentProperty->setAccessible(true);
            $documentProperty->setValue($entity, $fieldValue);
        }

        return $entity;
    }

    /**
     * @param $entity
     * @param string $entityClassName
     * @return mixed
     */
    public function entityToDocument($entity, string $entityClassName)
    {
        if (!$this->annotationParser->isEntityClass($entityClassName)) {
            return $entity;
        }

        try {
            $reflectClass = new \ReflectionClass($entityClassName);
        } catch (\Exception $exception) {
            return $entity;
        }

        $document = new \stdClass();
        foreach ($reflectClass->getProperties() as $entityField) {
            if ($entityField->isStatic()) {
                continue;
            }
            $annotation = $this->annotationParser->getPropertyAnnotation($entityField);
            if (null === $annotation) {
                continue;
            }

            $entityField->setAccessible(true);

            $fieldValue = $entityField->getValue($entity);
            $propertyValue = $this->fieldToProperty($fieldValue, $annotation);

            $fieldName = $annotation->name ?? $entityField->getName();
            if ($annotation->id) {
                $fieldName = '_id';
            }
            $document->{$fieldName} = $propertyValue;
        }
        return $document;
    }

    /**
     * 由 Mongodb Document 属性 property 设置成对应的 Entity 字段 Field 属性
     * @param AbstractField $annotation
     * @param $propertyValue
     * @return array|bool|\DateTime|float|int|mixed|ObjectId|string
     */
    private function propertyToField($propertyValue, AbstractField $annotation)
    {
        $fieldType = $annotation->type;
        switch ($fieldType) {
            case 'string':
                $fieldValue = strval($propertyValue);
                break;
            case 'bool':
                $fieldValue = boolval($propertyValue);
                break;
            case 'int':
                $fieldValue = intval($propertyValue);
                break;
            case 'float':
                $fieldValue = floatval($propertyValue);
                break;
            case 'array':
                $fieldValue = is_array($propertyValue) ? $propertyValue : settype($propertyValue, 'array');
                break;
            case 'date':
                /** @var UTCDateTime $propertyValue */
                $fieldValue = $propertyValue->toDateTime();
                break;
            case 'id':
                /** @var ObjectId $propertyValue */
                $fieldValue = $propertyValue;
                break;
            case 'embedOne':
                $fieldValue = $this->propertyToEmbedOne($propertyValue, $annotation);
                break;
            case 'embedMany':
                $fieldValue = $this->propertyToEmbedMany($propertyValue, $annotation);
                break;
            case 'raw':
                $fieldValue = $propertyValue;
                break;
            default:
                $fieldValue = $propertyValue;
                break;
        }
        return $fieldValue;
    }

    /**
     * 由 Mongodb Document 字段设置成对应的 Entity EmbedOne 对象
     * @param AbstractField $annotation
     * @param $propertyValue
     * @return mixed
     */
    private function propertyToEmbedOne($propertyValue, AbstractField $annotation)
    {
        $className = $annotation->target;

        try {
            $reflectClass = new \ReflectionClass($className);
        } catch (\Exception $exception) {
            return $propertyValue;
        }

        $embed = new $className;

        foreach ($reflectClass->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }
            /** @var AbstractField $annotation */
            $annotation = $this->annotationParser->getPropertyAnnotation($property);

            $propertyName = $property->getName();
            $propertyValue = $document->{$propertyName} ?? null;
            $propertyValue = $this->propertyToField($propertyValue, $annotation);

            $property->setAccessible(true);
            $property->setValue($embed, $propertyValue);
        }

        return $embed;
    }

    /**
     * 由 Mongodb Document 字段设置成对应的 Entity EmbedMany 对象
     * @param AbstractField $annotation
     * @param array $properties
     * @return array
     */
    private function propertyToEmbedMany(array $properties, AbstractField $annotation)
    {
        $embeds = [];
        foreach ($properties as $property) {
            $embeds[] = $this->propertyToEmbedOne($property, $annotation);
        }

        return $embeds;
    }

    /**
     * 由 Entity 的字段属性转换回 Mongodb Document 字段
     * @param AbstractField $annotation
     * @param $fieldValue
     * @return array|bool|float|int|ObjectId|UTCDateTime|\stdClass|string
     */
    private function fieldToProperty($fieldValue, AbstractField $annotation)
    {
        $fieldType = $annotation->type;

        switch ($fieldType) {
            case 'string':
                $propertyValue = strval($fieldValue);
                break;
            case 'bool':
                $propertyValue = boolval($fieldValue);
                break;
            case 'int':
                $propertyValue = intval($fieldValue);
                break;
            case 'float':
                $propertyValue = floatval($fieldValue);
                break;
            case 'array':
                $propertyValue = is_array($fieldValue) ? $fieldValue : settype($fieldValue, 'array');
                break;
            case 'date':
                /** @var \DateTime $fieldValue */
                $propertyValue = new UTCDateTime($fieldValue);
                break;
            case 'id':
                $propertyValue = $fieldValue instanceof ObjectId ? $fieldValue : new ObjectId();
                break;
            case 'embedOne':
                $propertyValue = $this->embedOneToProperty($fieldValue, $annotation);
                break;
            case 'embedMany':
                $propertyValue = $this->embedManyToProperty($fieldValue, $annotation);
                break;
            case 'raw':
            default:
                $propertyValue = $fieldValue;
                break;
        }

        return $propertyValue;
    }

    /**
     * 由 Entity 的 EmbedOne 属性的对象转换回 Mongodb Document 字段
     * @param AbstractField $annotation
     * @param $embed
     * @return \stdClass
     */
    private function embedOneToProperty($embed, AbstractField $annotation)
    {
        $className = $annotation->target;

        try {
            $reflectClass = new \ReflectionClass($className);
        } catch (\Exception $exception) {
            return $embed;
        }

        $property = new \stdClass();

        foreach ($reflectClass->getProperties() as $field) {
            if ($field->isStatic()) {
                continue;
            }

            $annotation = $this->annotationParser->getPropertyAnnotation($field);

            $propertyName = $field->getName();

            try {
                $reflectProperty = new \ReflectionProperty($embed, $propertyName);

                $reflectProperty->setAccessible(true);
                $fieldValue = $reflectProperty->getValue($embed);

                $propertyValue = $this->fieldToProperty($fieldValue, $annotation);
            } catch (\Exception $exception) {
                $propertyValue = null;
            }

            $property->{$propertyName} = $propertyValue;
        }

        return $property;
    }

    /**
     * 由 Entity 的 EmbedMany 属性的对象转换回 Mongodb Document 字段
     * @param AbstractField $annotation
     * @param array $embeds
     * @return array
     */
    private function embedManyToProperty(array $embeds, AbstractField $annotation)
    {
        $documents = [];
        foreach ($embeds as $embed) {
            $documents[] = $this->embedOneToProperty($embed, $annotation);
        }
        return $documents;
    }
}