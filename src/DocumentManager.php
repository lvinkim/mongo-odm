<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 23/07/2018
 * Time: 10:57 PM
 */

namespace Lvinkim\MongoODM;


use MongoDB\Driver\Manager;

/**
 * Class DocumentManager
 * @package Lvinkim\MongoODM
 */
class DocumentManager
{
    /** @var Manager */
    private $manager;

    /** @var string */
    private $database;

    public function __construct(Manager $manager, string $database)
    {
        $this->manager = $manager;
        $this->database = $database;
    }

    public function getManager(): Manager
    {
        return $this->manager;
    }

    public function getRepository($className): Repository
    {
        return new $className($this, $this->database);
    }
}