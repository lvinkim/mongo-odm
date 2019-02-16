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
use MongoDB\Driver\Cursor;
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
     * 获取 collection 表名
     * @return string
     */
    public function getCollectionName(): string
    {
        return $this->collection();
    }

    /**
     * 关联查询结构到 Entity 实体类
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
    public function count(array $filter = []): int
    {
        $command = new Command(['count' => $this->collection(), 'query' => $filter]);
        try {
            $result = $this->documentManager->getManager()->executeCommand($this->database, $command)->toArray();
            $row = reset($result);
            if (is_object($row)) {
                $count = $row->n ?? 0;
            } else {
                $count = 0;
            }
        } catch (\MongoDB\Driver\Exception\Exception $exception) {
            $count = 0;
        }
        return intval($count);
    }

    /**
     * @param $id
     * @return mixed|null
     * @throws \ErrorException
     */
    public function findOneById($id)
    {
        return $this->findOne(['_id' => $id]);
    }

    /**
     * @param array $filter
     * @return mixed|null
     * @throws \ErrorException
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
     * @throws \ErrorException
     */
    public function findMany(
        array $filter = [], array $sort = null, int $skip = null, int $limit = null, array $options = []
    ): \Generator
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
        } catch (\Throwable $throwable) {
            throw new \ErrorException($throwable->getMessage(), $throwable->getCode());
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
     * @param string $key
     * @param array $query
     * @return array
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function distinct(string $key, array $query = []): array
    {
        $commandOpt = [
            'distinct' => $this->collection(),
            'key' => $key,
            'query' => json_decode(json_encode($query)),
        ];
        $response = $this->runCommand($commandOpt);

        $result = $response->toArray();
        $row = reset($result);

        $ok = boolval($row->ok ?? false);
        if ($ok) {
            $values = (array)($row->values ?? []);
        } else {
            $values = [];
        }

        return $values;
    }

    /**
     * @param string $sumKey
     * @param array $groupKey
     * @param array $cond
     * @return array
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function sumGroup(string $sumKey, array $groupKey = [], array $cond = []): array
    {
        $reduce = '
            function ( curr, result ) {
                result.total += curr.' . $sumKey . ';
            }
        ';
        $initial = [
            'total' => 0,
        ];

        $response = $this->group($reduce, $initial, $groupKey, $cond);

        $result = $response->toArray();
        $row = reset($result);

        $ok = boolval($row->ok ?? false);

        if ($ok) {
            $retval = (array)($row->retval ?? []);
        } else {
            $retval = [];
        }
        return $retval;

    }

    /**
     * @param string $reduce
     * @param array $initial
     * @param array $key
     * @param array $cond
     * @return Cursor
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function group(string $reduce = "", array $initial = [], array $key = [], array $cond = []): Cursor
    {
        $commandOpt = [
            "group" => [
                'ns' => $this->collection(),
                'key' => json_decode(json_encode($key)),
                'cond' => json_decode(json_encode($cond)),
                '$reduce' => $reduce,
                'initial' => $initial,
            ]
        ];

        return $this->runCommand($commandOpt);
    }

    /**
     * @param array $commandOpt
     * @return \MongoDB\Driver\Cursor
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function runCommand(array $commandOpt): Cursor
    {
        $command = new Command($commandOpt);
        return $this->documentManager->getManager()->executeCommand($this->database, $command);
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