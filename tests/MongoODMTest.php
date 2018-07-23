<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 23/07/2018
 * Time: 10:20 PM
 */

namespace Tests;

use Lvinkim\MongoODM\DocumentManager;
use MongoDB\Driver\Manager;
use PHPUnit\Framework\TestCase;
use Tests\DAO\UserDAO;

class MongoODMTest extends TestCase
{
    /** @var DocumentManager */
    private $documentManager;

    /** @var UserDAO */
    private $userDAO;

    public function setUp()
    {
        $uri = 'mongodb://docker.for.mac.localhost';
        $driver = new Manager($uri);

        $this->documentManager = new DocumentManager($driver);
        $this->userDAO = $this->documentManager->getDAO(UserDAO::class);

    }

    public function testCleanCollection()
    {
        $this->userDAO->delete();
        $count = $this->userDAO->count();
        $this->assertEquals(0, $count);
    }

    public function testInsertOne()
    {
        $this->assertTrue(true);
    }

}