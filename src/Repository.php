<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 14/07/2018
 * Time: 12:50 PM
 */

namespace Lvinkim\MongoODM;


use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\WriteConcern;

/**
 * 对数据库的 curd 操作的封装
 * Class Repository
 * @package Lvinkim\MongoODM
 */
abstract class Repository
{
    /** @var DocumentManager */
    private $documentManager;

    /** @var EntityConverter */
    private $entityConverter;

    /**
     * Repository constructor.
     * @param DocumentManager $documentManager
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
        $this->entityConverter = new EntityConverter();
    }

    /**
     * 返回数据库中的表名, 例如: db.user
     * @return string
     */
    abstract protected function getNamespace(): string;

    /**
     * 返回数据表的对应实体类名
     * @return string
     */
    abstract protected function getEntityClassName(): string;

    /**
     * @param array $filter
     * @return bool|int
     */
    public function count(array $filter = [])
    {
        list($dbName, $collectionName) = explode('.', $this->getNamespace(), 2);
        $command = new Command(['count' => $collectionName, 'query' => $filter]);
        try {
            $result = $this->documentManager->getManager()->executeCommand($dbName, $command)->toArray()[0];
            return $result->n ?? 0;
        } catch (\MongoDB\Driver\Exception\Exception $exception) {
            return false;
        }
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public function findOneById($id)
    {
        return $this->findOne(['_id' => $id]);
    }

    /**
     * @param array $filter
     * @return mixed|null
     */
    public function findOne(array $filter = [])
    {
        $entities = $this->findMany($filter);
        foreach ($entities as $entity) {
            return $entity;
        }
        return null;
    }


    /**
     * @param array $filter
     * @param array|null $sort
     * @param int|null $skip
     * @param int|null $limit
     * @param array $options
     * @return \Generator
     */
    public function findMany(array $filter = [], array $sort = null, int $skip = null, int $limit = null, array $options = [])
    {
        list($dbName, $collectionName) = explode('.', $this->getNamespace(), 2);
        $commandOpt = ['find' => $collectionName];
        $filter ? $commandOpt['filter'] = $filter : null;
        $sort ? $commandOpt['sort'] = $sort : null;
        $skip ? $commandOpt['skip'] = $skip : null;
        $limit ? $commandOpt['limit'] = $limit : null;

        if ($options) {
            $commandOpt = array_merge($commandOpt, $options);
        }

        $queryCommand = new Command($commandOpt);

        try {
            $documents = $this->documentManager->getManager()->executeCommand($dbName, $queryCommand);

            $entityClassName = $this->getEntityClassName();
            /** @var \stdClass $document */
            foreach ($documents as $document) {
                yield $this->entityConverter->documentToEntity($document, $entityClassName);
            }
        } catch (\MongoDB\Driver\Exception\Exception $exception) {
            null;
        }
    }

    /**
     * @param $entity
     * @return int|null
     */
    public function deleteOne($entity)
    {
        return $this->deleteMany(['_id' => $this->entityConverter->getId($entity)]);
    }

    /**
     * @param array $filter
     * @return int|null
     */
    public function deleteMany(array $filter = [])
    {
        $bulk = new BulkWrite(['ordered' => false]);
        $bulk->delete($filter);

        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
        $result = $this->documentManager->getManager()->executeBulkWrite($this->getNamespace(), $bulk, $writeConcern);

        $deletedCount = $result->getDeletedCount();

        return $deletedCount;
    }

    /**
     * @param $entity
     * @return int|null
     */
    public function insertOne($entity)
    {
        $bulk = new BulkWrite(['ordered' => false]); // 允许更新报错

        $document = $this->entityConverter->entityToDocument($entity, $this->getEntityClassName());

        $bulk->insert($document);

        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
        $result = $this->documentManager->getManager()->executeBulkWrite($this->getNamespace(), $bulk, $writeConcern);

        $insertedCount = $result->getInsertedCount();

        if ($insertedCount) {
            $this->entityConverter->setId($entity, $document->_id);
        }

        return $insertedCount;
    }

    /**
     * @param $entities
     * @return int|null
     */
    public function insertMany($entities)
    {
        $bulk = new BulkWrite(['ordered' => false]); // 允许更新报错

        foreach ($entities as $entity) {
            $document = $this->entityConverter->entityToDocument($entity, $this->getEntityClassName());
            $bulk->insert($document);
        }

        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
        $result = $this->documentManager->getManager()->executeBulkWrite($this->getNamespace(), $bulk, $writeConcern);

        $insertedCount = $result->getInsertedCount();

        return $insertedCount;
    }


    /**
     * @param $entity
     * @return int|null
     */
    public function updateOne($entity)
    {
        $bulk = new BulkWrite(['ordered' => false]);

        $document = $this->entityConverter->entityToDocument($entity, $this->getEntityClassName());
        $bulk->update(['_id' => $document->_id], $document);

        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
        $result = $this->documentManager->getManager()->executeBulkWrite($this->getNamespace(), $bulk, $writeConcern);

        $modifiedCount = $result->getModifiedCount();

        return $modifiedCount;
    }

    /**
     * @param $entities
     * @return int|null
     */
    public function updateMany($entities)
    {
        $bulk = new BulkWrite(['ordered' => false]);

        foreach ($entities as $entity) {
            $document = $this->entityConverter->entityToDocument($entity, $this->getEntityClassName());
            $bulk->update(['_id' => $document->_id], $document);
        }

        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
        $result = $this->documentManager->getManager()->executeBulkWrite($this->getNamespace(), $bulk, $writeConcern);

        $modifiedCount = $result->getModifiedCount();

        return $modifiedCount;
    }


    /**
     * @param $entity
     * @return int|null
     */
    public function upsertOne($entity)
    {
        $entityId = $this->entityConverter->getId($entity);
        if ($entityId && $this->count(['_id' => $entityId])) {
            return $this->updateOne($entity);
        } else {
            return $this->insertOne($entity);
        }
    }

    /**
     * @param $entities
     * @return int|null
     */
    public function upsertMany($entities)
    {
        $bulk = new BulkWrite(['ordered' => false]);

        foreach ($entities as $entity) {

            $document = $this->entityConverter->entityToDocument($entity, $this->getEntityClassName());

            $entityId = $this->entityConverter->getId($entity);
            if ($entityId && $this->count(['_id' => $entityId])) {
                $bulk->update(['_id' => $document->_id], $document);
            } else {
                $bulk->insert($document);
            }
        }
        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
        $result = $this->documentManager->getManager()->executeBulkWrite($this->getNamespace(), $bulk, $writeConcern);

        $modifiedCount = $result->getModifiedCount();
        $insertedCount = $result->getInsertedCount();

        return ($modifiedCount + $insertedCount);
    }
}