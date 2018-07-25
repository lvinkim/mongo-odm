<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2018/7/25
 * Time: 3:53 PM
 */

namespace Tests\Functional;


use Lvinkim\MongoODM\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Manager;
use PHPUnit\Framework\TestCase;
use Tests\App\Entity\IsEntityWithString;
use Tests\App\Repository\IsEntityWithStringRepository;
use Tests\App\Repository\UserRepository;

class RepositoryTest extends TestCase
{

    /** @var DocumentManager */
    static private $documentManager;

    public static function setUpBeforeClass()
    {
        $uri = 'mongodb://docker.for.mac.localhost:27017';
        $manager = new Manager($uri);
        self::$documentManager = new DocumentManager($manager);
    }

    /** @var UserRepository */
    private $userRepository;

    /** @var IsEntityWithStringRepository */
    private $isEntityWithStringRepository;

    public function setUp()
    {
        $this->userRepository = self::$documentManager->getRepository(UserRepository::class);
        $this->isEntityWithStringRepository = self::$documentManager->getRepository(IsEntityWithStringRepository::class);
    }

    public function testInsertOne()
    {
        $isEntityWithString = new IsEntityWithString();
        $isEntityWithString->setName('name - ' . rand(100, 999));
        $insertedId = $this->isEntityWithStringRepository->insertOne($isEntityWithString);

        $this->assertInstanceOf(ObjectId::class, $insertedId);
    }

}