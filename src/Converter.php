<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 15/07/2018
 * Time: 12:35 AM
 */

namespace Lvinkim\MongoODM;


use Doctrine\Common\Annotations\AnnotationReader;
use Lvinkim\MongoODM\Annotations\AbstractField;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class Converter
{
    public function documentToProperty(AbstractField $annotation, $documentValue)
    {
        $fieldType = $annotation->type;
        switch ($fieldType) {
            case 'string':
                $propertyValue = strval($documentValue);
                break;
            case 'int':
                $propertyValue = intval($documentValue);
                break;
            case 'float':
                $propertyValue = floatval($documentValue);
                break;
            case 'array':
                $propertyValue = is_array($documentValue) ? $documentValue : settype($documentValue, 'array');
                break;
            case 'date':
                /** @var UTCDateTime $documentValue */
                $propertyValue = $documentValue->toDateTime();
                break;
            case 'id':
                /** @var ObjectId $documentValue */
                $propertyValue = $documentValue;
                break;
            case 'embedOne':
                $propertyValue = $this->documentToEmbedOne($annotation, $documentValue);
                break;
            case 'embedMany':
                $propertyValue = $this->documentToEmbedMany($annotation, $documentValue);
                break;
            case 'raw':
                $propertyValue = $documentValue;
                break;
            default:
                $propertyValue = $documentValue;
                break;
        }
        return $propertyValue;
    }

    public function documentToEmbedOne(AbstractField $annotation, $document)
    {
        $className = $annotation->target;
        $embed = new $className;

        $reflectClass = new \ReflectionClass($embed);
        $reader = new AnnotationReader();
        foreach ($reflectClass->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            /** @var AbstractField $annotation */
            $annotation = $reader->getPropertyAnnotation($property, AbstractField::class);

            $propertyName = $property->getName();
            $documentValue = $document->{$propertyName} ?? null;
            $propertyValue = $this->documentToProperty($annotation, $documentValue);

            $property->setAccessible(true);
            $property->setValue($embed, $propertyValue);
        }

        return $embed;
    }

    public function documentToEmbedMany(AbstractField $annotation, array $documents)
    {
        $embeds = [];
        foreach ($documents as $document) {
            $embeds[] = $this->documentToEmbedOne($annotation, $document);
        }
        return $embeds;
    }

    public function propertyToDocument(AbstractField $annotation, $propertyValue)
    {
        $fieldType = $annotation->type;

        switch ($fieldType) {
            case 'string':
                $documentValue = strval($propertyValue);
                break;
            case 'int':
                $documentValue = intval($propertyValue);
                break;
            case 'float':
                $documentValue = floatval($propertyValue);
                break;
            case 'array':
                $documentValue = is_array($propertyValue) ? $propertyValue : settype($propertyValue, 'array');
                break;
            case 'date':
                /** @var \DateTime $propertyValue */
                $documentValue = new UTCDateTime($propertyValue);
                break;
            case 'id':
                $documentValue = $propertyValue instanceof ObjectId ? $propertyValue : new ObjectId();
                break;
            case 'embedOne':
                $documentValue = $this->embedOneToDocument($annotation, $propertyValue);
                break;
            case 'embedMany':
                $documentValue = $this->embedManyToDocument($annotation, $propertyValue);
                break;
            case 'raw':
                $documentValue = $propertyValue;
                break;
            default:
                $documentValue = $propertyValue;
                break;
        }

        return $documentValue;
    }

    public function embedOneToDocument(AbstractField $annotation, $embed)
    {
        $className = $annotation->target;
        $targetClass = new $className;

        $properties = new \stdClass();

        $reflectClass = new \ReflectionClass($targetClass);
        $reader = new AnnotationReader();
        foreach ($reflectClass->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            /** @var AbstractField $annotation */
            $annotation = $reader->getPropertyAnnotation($property, AbstractField::class);

            $propertyName = $property->getName();

            $reflectProperty = new \ReflectionProperty($embed, $propertyName);
            $reflectProperty->setAccessible(true);
            $propertyValue = $reflectProperty->getValue($embed);

            $documentValue = $this->propertyToDocument($annotation, $propertyValue);

            $properties->{$propertyName} = $documentValue;
        }

        return $properties;
    }

    public function embedManyToDocument(AbstractField $annotation, array $embeds)
    {
        $documents = [];
        foreach ($embeds as $embed) {
            $documents[] = $this->embedOneToDocument($annotation, $embed);
        }
        return $documents;
    }
}