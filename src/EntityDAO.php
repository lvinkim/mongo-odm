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
 * Class EntityDAO
 * @package Lvinkim\MongoODM
 */
abstract class EntityDAO
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
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
    abstract protected function getEntity(): string;


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
     * @param array $filter
     * @return int|null
     */
    public function delete(array $filter = [])
    {
        $bulk = new BulkWrite(['ordered' => false]);
        $bulk->delete($filter);

        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
        $result = $this->documentManager->getManager()->executeBulkWrite($this->getNamespace(), $bulk, $writeConcern);

        $deletedCount = $result->getDeletedCount();

        return $deletedCount;
    }

    public function insert(EntityInterface $entity)
    {
        $bulk = new BulkWrite(['ordered' => false]); // 允许更新报错

        $document = $entity->getDocument();
        $bulk->insert($document);

        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
        $result = $this->documentManager->getManager()->executeBulkWrite($this->getNamespace(), $bulk, $writeConcern);

        $insertedCount = $result->getInsertedCount();

        return $insertedCount;
    }

    public function findOne(array $filter = [])
    {
        $entities = $this->find($filter);
        foreach ($entities as $entity) {
            return $entity;
        }
        return null;
    }

    public function find(array $filter = [], array $sort = null, int $skip = null, int $limit = null)
    {
        list($dbName, $collectionName) = explode('.', $this->getNamespace(), 2);
        $commandOpt = ['find' => $collectionName];
        $filter ? $commandOpt['filter'] = $filter : null;
        $sort ? $commandOpt['sort'] = $sort : null;
        $skip ? $commandOpt['skip'] = $skip : null;
        $limit ? $commandOpt['limit'] = $limit : null;

        $queryCommand = new Command($commandOpt);

        try {
            $documents = $this->documentManager->getManager()->executeCommand($dbName, $queryCommand);
            $entityClass = $this->getEntity();
            /** @var \stdClass $document */
            foreach ($documents as $document) {
                /** @var EntityInterface $entity */
                $entity = new $entityClass();
                $entity->setByDocument($document);
                yield $entity;
            }
        } catch (\MongoDB\Driver\Exception\Exception $exception) {
            null;
        }
    }

    public function update(EntityInterface $entity)
    {
        $bulk = new BulkWrite(['ordered' => false]);

        $document = $entity->getDocument();

        $bulk->update(['_id' => $document->_id], $document);

        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
        $result = $this->documentManager->getManager()->executeBulkWrite($this->getNamespace(), $bulk, $writeConcern);

        $modifiedCount = $result->getModifiedCount();

        return $modifiedCount;
    }

}