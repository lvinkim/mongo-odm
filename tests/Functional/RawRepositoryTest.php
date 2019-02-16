<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 01/10/2018
 * Time: 9:58 AM
 */

namespace Lvinkim\MongoODM\Tests\Functional;


use Lvinkim\MongoODM\DocumentManager;
use Lvinkim\MongoODM\Tests\App\Repository\PersonRepository;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Manager;
use PHPUnit\Framework\TestCase;

class RawRepositoryTest extends TestCase
{
    /** @var DocumentManager */
    static private $documentManager;

    public static function setUpBeforeClass()
    {
        $uri = 'mongodb://localhost:27017';
        $manager = new Manager($uri);
        self::$documentManager = new DocumentManager($manager, "test");
    }

    /** @var PersonRepository */
    private $personRepository;

    public function setUp()
    {
        $this->personRepository = self::$documentManager->getRepository(PersonRepository::class);
    }

    public function testClean()
    {
        $this->personRepository->deleteMany();
        $count = $this->personRepository->count();

        $this->assertEquals(0, $count);
    }

    /**
     * @depends testClean
     */
    public function testInsertOne()
    {
        $document = new \stdClass();
        $document->username = "lvinkim-" . mt_rand(100, 999);

        $insertedCount = $this->personRepository->hydrate(false)->insertOne($document);

        $this->assertEquals(1, $insertedCount);
    }

    /**
     * @depends testClean
     */
    public function testInsertMany()
    {
        $documents = [];

        $document = new \stdClass();
        $document->username = "lvinkim-" . mt_rand(100, 999);
        $documents[] = $document;

        $document = new \stdClass();
        $document->username = "lvinkim-" . mt_rand(100, 999);
        $documents[] = $document;

        $insertedCount = $this->personRepository->hydrate(false)->insertMany($documents);

        $this->assertEquals(2, $insertedCount);
    }

    /**
     * @depends testClean
     */
    public function testUpdateOne()
    {
        $document = new \stdClass();
        $document->_id = uniqid("id-");
        $document->username = "lvinkim-" . mt_rand(100, 999);

        $this->personRepository->hydrate(false)->insertOne($document);

        $document->username = "lvinkim-update-" . mt_rand(100, 999);
        $modifiedCount = $this->personRepository->hydrate(false)->updateOne($document);

        $this->assertEquals(1, $modifiedCount);
    }

    /**
     * @depends testClean
     */
    public function testUpdateMany()
    {
        $documents = [];

        $document = new \stdClass();
        $document->_id = uniqid("id-");
        $document->username = "lvinkim-" . mt_rand(100, 999);
        $documents[] = $document;

        $document = new \stdClass();
        $document->_id = uniqid("id-");
        $document->username = "lvinkim-" . mt_rand(100, 999);
        $documents[] = $document;

        $this->personRepository->hydrate(false)->insertMany($documents);

        foreach ($documents as &$document) {
            $document->username = "lvinkim-update-" . mt_rand(100, 999);
        }

        $modifiedCount = $this->personRepository->hydrate(false)->updateMany($documents);

        $this->assertEquals(2, $modifiedCount);
    }

    /**
     * @depends testClean
     */
    public function testUpdateField()
    {
        $documents = [];

        $document = new \stdClass();
        $document->username = "lvinkim-123";
        $document->age = mt_rand(100, 999);
        $documents[] = $document;

        $document = new \stdClass();
        $document->username = "lvinkim-123";
        $document->age = mt_rand(100, 999);
        $documents[] = $document;

        $this->personRepository->hydrate(false)->insertMany($documents);

        $modifiedCount = $this->personRepository->updateField(["username" => "lvinkim-123"], ["username" => "lvinkim-update-123"]);
        $this->assertEquals(2, $modifiedCount);
    }

    /**
     * @depends testClean
     */
    public function testUpsertOneUpdate()
    {
        $document = new \stdClass();
        $document->_id = uniqid("id-");
        $document->username = "lvinkim-" . mt_rand(100, 999);

        $this->personRepository->hydrate(false)->insertOne($document);

        $document->username = "lvinkim-update-" . mt_rand(100, 999);
        $modifiedCount = $this->personRepository->hydrate(false)->upsertOne($document);

        $this->assertEquals(1, $modifiedCount);
    }

    /**
     * @depends testClean
     */
    public function testUpsertOneInsert()
    {
        $document = new \stdClass();
        $document->username = "lvinkim-" . mt_rand(100, 999);

        $modifiedCount = $this->personRepository->hydrate(false)->upsertOne($document);

        $this->assertEquals(1, $modifiedCount);
    }

    /**
     * @depends testClean
     */
    public function testUpsertMany()
    {
        $documents = [];

        $document = new \stdClass();
        $document->username = "lvinkim-" . mt_rand(100, 999);
        $documents[] = $document;

        $this->personRepository->hydrate(false)->insertOne($document);

        $documents[0]->username = "lvinkim-update-" . mt_rand(100, 999);

        $document = new \stdClass();
        $document->username = "lvinkim-" . mt_rand(100, 999);
        $documents[] = $document;

        $effectCount = $this->personRepository->upsertMany($documents);
        $this->assertEquals(2, $effectCount);
    }

    /**
     * @depends testInsertMany
     */
    public function testCount()
    {
        $count = $this->personRepository->count();
        $this->assertEquals(2, $count);
    }

    /**
     * @depends testClean
     */
    public function testDeleteOne()
    {
        $document = new \stdClass();
        $document->_id = uniqid("id-");
        $document->username = "lvinkim-" . mt_rand(100, 999);

        $this->personRepository->hydrate(false)->insertOne($document);

        $deletedCount = $this->personRepository->hydrate(false)->deleteOne($document);

        $this->assertEquals(1, $deletedCount);
    }

    /**
     * @depends testInsertMany
     */
    public function testDeleteMany()
    {
        $deletedCount = $this->personRepository->deleteMany();
        $this->assertEquals(2, $deletedCount);
    }

    /**
     * @depends testClean
     * @throws \ErrorException
     */
    public function testFindOneById()
    {
        $document = new \stdClass();
        $document->username = "lvinkim-" . mt_rand(100, 999);

        $this->personRepository->hydrate(false)->insertOne($document);

        $mongoDoc = $this->personRepository->hydrate(false)->findOneById($document->_id);

        $this->assertEquals($document->username, $mongoDoc->username);
    }

    /**
     * @depends testClean
     * @throws \ErrorException
     */
    public function testFineOne()
    {
        $document = new \stdClass();
        $document->username = "lvinkim-" . mt_rand(100, 999);

        $this->personRepository->hydrate(false)->insertOne($document);

        $mongoDoc = $this->personRepository->hydrate(false)->findOne(["username" => $document->username]);

        $this->assertEquals($document->_id, $mongoDoc->_id);

    }

    /**
     * @depends testClean
     * @throws \ErrorException
     */
    public function testFindMany()
    {
        $documents = [];

        $document = new \stdClass();
        $document->username = "lvinkim-" . mt_rand(100, 999);
        $documents[] = $document;

        $document = new \stdClass();
        $document->username = "lvinkim-" . mt_rand(100, 999);
        $documents[] = $document;

        $this->personRepository->hydrate(false)->insertMany($documents);

        $persons = $this->personRepository->findMany();

        foreach ($persons as $person) {
            $this->assertInstanceOf(ObjectId::class, $person->_id);
        }
    }
}