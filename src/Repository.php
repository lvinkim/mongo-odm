<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 14/07/2018
 * Time: 12:50 PM
 */

namespace Lvinkim\MongoODM;


use MongoDB\BSON\ObjectId;
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

    protected $database = "";

    /** @var bool */
    protected $hydrated = true;

    /**
     * Repository constructor.
     * @param DocumentManager $documentManager
     * @param string $database
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function __construct(DocumentManager $documentManager, string $database)
    {
        $this->documentManager = $documentManager;
        $this->database = $database;
        $this->entityConverter = new EntityConverter();
    }

    /**
     * 返回数据库中的表名, 例如: user
     * @return string
     */
    abstract protected function collection(): string;

    /**
     * 重写此方法，返回数据表的对应实体类名，即可自动关联实体类
     * @return string
     */
    protected function getEntityClassName()
    {
        return "";
    }

    /**
     * @param bool $hydrated
     * @return $this
     */
    public function hydrate($hydrated = true)
    {
        if ($hydrated && $this->getEntityClassName()) {
            $this->hydrated = true;
        } else {
            $this->hydrated = false;
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isHydrated(): bool
    {
        return boolval($this->hydrated);
    }

    /**
     * @param array $filter
     * @return bool|int
     */
    public function count(array $filter = [])
    {
        $command = new Command(['count' => $this->collection(), 'query' => $filter]);
        try {
            $result = $this->documentManager->getManager()->executeCommand($this->database, $command)->toArray()[0];
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
        $commandOpt = ['find' => $this->collection(), "noCursorTimeout" => true];
        $filter ? $commandOpt['filter'] = $filter : null;
        $sort ? $commandOpt['sort'] = $sort : null;
        $skip ? $commandOpt['skip'] = $skip : null;
        $limit ? $commandOpt['limit'] = $limit : null;

        if ($options) {
            $commandOpt = array_merge($commandOpt, $options);
        }

        $queryCommand = new Command($commandOpt);

        try {
            $documents = $this->documentManager->getManager()->executeCommand($this->database, $queryCommand);

            $entityClassName = $this->getEntityClassName();
            /** @var \stdClass $document */
            foreach ($documents as $document) {
                if ($this->isHydrated()) {
                    yield $this->entityConverter->documentToEntity($document, $entityClassName);
                } else {
                    yield $document;
                }
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
        if ($this->isHydrated()) {
            $id = $this->entityConverter->getId($entity);
        } else {
            $id = $entity->_id ?? false;
        }
        return $this->deleteMany(['_id' => $id]);
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

        if ($this->isHydrated()) {
            $document = $this->entityConverter->entityToDocument($entity, $this->getEntityClassName());
        } else {
            $document = $this->padDocumentId($entity);
        }

        $bulk->insert($document);

        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);

        $result = $this->documentManager->getManager()->executeBulkWrite($this->getNamespace(), $bulk, $writeConcern);

        $insertedCount = $result->getInsertedCount();

        if ($insertedCount) {
            if ($this->isHydrated()) {
                $this->entityConverter->setId($entity, $document->_id);
            }
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
            if ($this->isHydrated()) {
                $document = $this->entityConverter->entityToDocument($entity, $this->getEntityClassName());
            } else {
                $document = $this->padDocumentId($entity);
            }
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

        if ($this->isHydrated()) {
            $document = $this->entityConverter->entityToDocument($entity, $this->getEntityClassName());
        } else {
            $document = $this->padDocumentId($entity);
        }

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
            if ($this->isHydrated()) {
                $document = $this->entityConverter->entityToDocument($entity, $this->getEntityClassName());
            } else {
                $document = $this->padDocumentId($entity);
            }
            $bulk->update(['_id' => $document->_id], $document);
        }

        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
        $result = $this->documentManager->getManager()->executeBulkWrite($this->getNamespace(), $bulk, $writeConcern);

        $modifiedCount = $result->getModifiedCount();

        return $modifiedCount;
    }

    /**
     * @param array $filter
     * @param $doc
     * @return int|null
     */
    public function updateField(array $filter = [], $doc)
    {
        $bulk = new BulkWrite(['ordered' => false]);
        $bulk->update($filter, ['$set' => $doc], ['multi' => true, 'upsert' => false]);

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
        if ($this->isHydrated()) {
            $entityId = $this->entityConverter->getId($entity);
        } else {
            $entity = $this->padDocumentId($entity);
            $entityId = $entity->_id;
        }

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

            if ($this->isHydrated()) {
                $document = $this->entityConverter->entityToDocument($entity, $this->getEntityClassName());
                $entityId = $this->entityConverter->getId($entity);
            } else {
                $document = $this->padDocumentId($entity);
                $entityId = $document->_id;
            }

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

    /**
     * @return string
     */
    protected function getNamespace()
    {
        return "{$this->database}.{$this->collection()}";
    }

    /**
     * @param $document
     * @return mixed
     */
    protected function padDocumentId($document)
    {
        !isset($document->_id) ? $document->_id = new ObjectId() : null;
        return $document;
    }
}