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
use Tests\Entity\Embed\Address;
use Tests\Entity\Embed\Company;
use Tests\Entity\Embed\Member;
use Tests\Entity\User;
use Tests\Repository\UserRepository;

class MongoODMTest extends TestCase
{
    /** @var DocumentManager */
    private $documentManager;

    /** @var UserRepository */
    private $userRepository;

    public static function setUpBeforeClass()
    {
        \Doctrine\Common\Annotations\AnnotationRegistry::registerUniqueLoader(function () {
            return true;
        });
    }

    public function setUp()
    {
        $uri = 'mongodb://docker.for.mac.localhost';
        $driver = new Manager($uri);

        $this->documentManager = new DocumentManager($driver);
        $this->userRepository = $this->documentManager->getRepository(UserRepository::class);

    }

    /**
     * 清空数据表
     */
    public function testCleanCollection()
    {
        $this->userRepository->delete();
        $count = $this->userRepository->count();
        $this->assertEquals(0, $count);
    }

    /**
     * 插入一个新用户
     * @depends testCleanCollection
     */
    public function testInsertOne()
    {
        $user = new User();

        $user->setName('Jack - ' . rand(100, 999));
        $user->setAge(rand(10, 99));
        $user->setBirth((new \DateTime())->setTimestamp(strtotime('2000-01-01 01:01:01')));
        $user->setTags(['young', 'fashion']);
        $user->setWeight(66.66);

        $company = new Company();
        $company->setName('company name');
        $company->setContact('13812345678');

        $address = new Address();
        $address->setCountry('中国');
        $address->setCity('广州');
        $company->setAddress($address);

        $user->setCompany($company);

        $member = new Member();
        $member->setName('Tom');
        $member->setRelation('brother');
        $families = [$member];

        $user->setFamilies($families);

        $user->setRemark(['skill' => '运动，唱歌']);

        $insertedCount = $this->userDAO->insertOne($user);

        $this->assertEquals(1, $insertedCount);

        return $user;
    }


    /**
     * 查找单条记录
     * @param User $user
     * @depends testInsertOne
     * @return User
     */
    public function testFindOne(User $user)
    {
        /** @var User $entity */
        $entity = $this->userDAO->findOne(['name' => $user->getName()]);

        $this->assertInstanceOf(EntityInterface::class, $entity);
        $this->assertEquals($user->getName(), $entity->getName());

        return $entity;
    }


    /**
     * 查找多条记录
     * @param User $user
     * @depends testInsertOne
     */
    public function testFind(User $user)
    {
        $documents = $this->userDAO->find(['age' => $user->getAge()]);

        $cursor = 0;
        /** @var User $document */
        foreach ($documents as $document) {
            $cursor++;
            $this->assertEquals($user->getAge(), $document->getAge());
        }

        $this->assertEquals(1, $cursor);
    }

    /**
     * @param User $user
     * @depends testFindOne
     */
    public function testUpdate(User $user)
    {
        $newAge = $user->getAge() + 1;
        $user->setAge($newAge);

        $modifiedCount = $this->userDAO->updateOne($user);
        $this->assertEquals(1, $modifiedCount);

        /** @var User $newUser */
        $newUser = $this->userDAO->findOne(['_id' => $user->getId()]);

        $this->assertEquals($newAge, $newUser->getAge());

    }

    /**
     * @depends testCleanCollection
     */
    public function testUpsertForInsert()
    {
        $user = new User();

        $user->setName('Jack - ' . rand(100, 999));
        $user->setAge(rand(10, 99));
        $user->setBirth((new \DateTime())->setTimestamp(strtotime('2000-01-01 01:01:01')));
        $user->setTags(['young', 'fashion']);
        $user->setWeight(66.66);

        $company = new Company();
        $company->setName('company name');
        $company->setContact('13812345678');

        $address = new Address();
        $address->setCountry('中国');
        $address->setCity('广州');
        $company->setAddress($address);

        $user->setCompany($company);

        $member = new Member();
        $member->setName('Tom');
        $member->setRelation('brother');
        $families = [$member];

        $user->setFamilies($families);

        $user->setRemark(['skill' => '运动，唱歌']);

        $count = $this->userDAO->upsertOne($user);

        $this->assertEquals(1, $count);
    }

    /**
     * @param $user User
     * @depends testFindOne
     */
    public function testUpsertForUpdate(User $user)
    {
        $newAge = $user->getAge() + 1;
        $user->setAge($newAge);

        $modifiedCount = $this->userDAO->upsertOne($user);
        $this->assertEquals(1, $modifiedCount);

        /** @var User $newUser */
        $newUser = $this->userDAO->findOne(['_id' => $user->getId()]);

        $this->assertEquals($newAge, $newUser->getAge());
    }

    /**
     * @depends testCleanCollection
     */
    public function testInsertMany()
    {
        $userCount = 10;
        $users = (function () use ($userCount) {
            foreach (range(1, $userCount) as $value) {
                $user = new User();

                $user->setName('Jack - ' . $value);
                $user->setAge(rand(10, 99));
                $user->setBirth((new \DateTime())->setTimestamp(strtotime('2000-01-01 01:01:01')));
                $user->setTags(['young', 'fashion']);
                $user->setWeight(66.66);

                $company = new Company();
                $company->setName('company name');
                $company->setContact('13812345678');

                $address = new Address();
                $address->setCountry('中国');
                $address->setCity('广州');
                $company->setAddress($address);

                $user->setCompany($company);

                $member = new Member();
                $member->setName('Tom');
                $member->setRelation('brother');
                $families = [$member];

                $user->setFamilies($families);

                $user->setRemark(['skill' => '运动，唱歌']);

                yield $user;
            }
        })();

        $insertedCount = $this->userDAO->insertMany($users);

        $this->assertEquals($userCount, $insertedCount);
    }


    /**
     * @depends testInsertMany
     */
    public function testUpdateMany()
    {
        $usersCount = $this->userDAO->count();

        /** @var User[] $users */
        $users = $this->userDAO->find();
        $updateUsers = (function () use ($users) {

            foreach ($users as $user) {
                $user->setAge(rand(10, 99));
                yield $user;
            }

        })();

        $modifiedCount = $this->userDAO->updateMany($updateUsers);

        $this->assertEquals($usersCount, $modifiedCount);
    }

    /**
     * @depends testInsertMany
     */
    public function testUpsertMany()
    {
        $updateCount = $this->userDAO->count();
        $insertCount = 10;

        /** @var User[] $users */
        $users = $this->userDAO->find();

        $upsertUsers = (function () use ($insertCount, $users) {
            foreach (range(1, $insertCount) as $value) {
                $user = new User();

                $user->setName('Jack - ' . $value);
                $user->setAge(rand(10, 99));
                $user->setBirth((new \DateTime())->setTimestamp(strtotime('2000-01-01 01:01:01')));
                $user->setTags(['young', 'fashion']);
                $user->setWeight(66.66);

                $company = new Company();
                $company->setName('company name');
                $company->setContact('13812345678');

                $address = new Address();
                $address->setCountry('中国');
                $address->setCity('广州');
                $company->setAddress($address);

                $user->setCompany($company);

                $member = new Member();
                $member->setName('Tom');
                $member->setRelation('brother');
                $families = [$member];

                $user->setFamilies($families);

                $user->setRemark(['skill' => '运动，唱歌']);

                yield $user;
            }

            foreach ($users as $user) {
                $user->setAge(rand(10, 99));
                yield $user;
            }
        })();

        $upsertCount = $this->userDAO->upsertMany($upsertUsers);

        $this->assertEquals(($updateCount + $insertCount), $upsertCount);
    }

}