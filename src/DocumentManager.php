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

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function getManager(): Manager
    {
        return $this->manager;
    }

    public function getDAO($className): EntityDAO
    {
        return new $className($this);
    }
}