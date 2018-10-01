<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2018/7/25
 * Time: 3:53 PM
 */

namespace Lvinkim\MongoODM\Tests\Functional;


use Lvinkim\MongoODM\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Manager;
use PHPUnit\Framework\TestCase;
use Lvinkim\MongoODM\Tests\App\Entity\Embed\Address;
use Lvinkim\MongoODM\Tests\App\Entity\Embed\Company;
use Lvinkim\MongoODM\Tests\App\Entity\Embed\Member;
use Lvinkim\MongoODM\Tests\App\Entity\User;
use Lvinkim\MongoODM\Tests\App\Repository\IsEntityWithStringRepository;
use Lvinkim\MongoODM\Tests\App\Repository\UserRepository;

class RepositoryTest extends TestCase
{

    /** @var DocumentManager */
    static private $documentManager;

    public static function setUpBeforeClass()
    {
        $uri = 'mongodb://localhost:27017';
        $manager = new Manager($uri);
        self::$documentManager = new DocumentManager($manager, "test");
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

    /**
     * 清空用户 user 数据表
     */
    public function testUserCleanCollection()
    {
        $this->userRepository->deleteMany();
        $count = $this->userRepository->count();
        $this->assertEquals(0, $count);
    }

    /**
     * 插入一个新用户
     * @depends testUserCleanCollection
     */
    public function testUserInsertOne()
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

        $insertedCount = $this->userRepository->insertOne($user);

        $this->assertEquals(1, $insertedCount);
        $this->assertInstanceOf(ObjectId::class, $user->getId());

        return $user;
    }


    /**
     * 批量插入多个新用户
     * @depends testUserCleanCollection
     */
    public function testUserInsertMany()
    {
        $userCount = 10;
        $users = (function () use ($userCount) {
            foreach (range(1, $userCount) as $value) {
                $user = new User();

                $user->setName('Jack - ' . rand(100, 999) . ' - ' . $value);
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

        $insertedCount = $this->userRepository->insertMany($users);

        $this->assertEquals($userCount, $insertedCount);

        return $insertedCount;
    }

    /**
     * count (单条记录)
     * @param User $user
     * @depends testUserInsertOne
     */
    public function testUserCountWithOne(User $user)
    {
        $filter = [
            'name' => $user->getName(),
            'age' => $user->getAge(),
        ];
        $count = $this->userRepository->count($filter);

        $this->assertEquals(1, $count);
    }

    /**
     * count (多条记录)
     * @param int $insertedCount
     * @depends testUserInsertMany
     */
    public function testCountWithMany(int $insertedCount)
    {
        $count = $this->userRepository->count();

        $this->assertEquals($insertedCount, $count);
    }

    /**
     * 根据 id 查找单条记录
     * @param User $user
     * @depends testUserInsertOne
     */
    public function testUserFindOneById(User $user)
    {
        /** @var User $foundUser */
        $foundUser = $this->userRepository->findOneById($user->getId());

        $this->assertEquals($foundUser->getId(), $user->getId());
    }

    /**
     * 根据 filter 条件查找单条记录
     * @param User $user
     * @depends testUserInsertOne
     */
    public function testUserFindOne(User $user)
    {
        $filter = [
            'name' => $user->getName(),
            'age' => $user->getAge(),
        ];
        /** @var User $foundUser */
        $foundUser = $this->userRepository->findOne($filter);

        $this->assertEquals($foundUser->getId(), $user->getId());
    }

    /**
     * 根据 filter 条件查找多条记录
     * @param User $user
     * @depends testUserInsertOne
     */
    public function testUserFindMany(User $user)
    {
        $filter = [
            'name' => $user->getName(),
            'age' => $user->getAge(),
        ];

        /** @var User[] $foundUsers */
        $foundUsers = $this->userRepository->findMany($filter);

        $cursor = 0;
        foreach ($foundUsers as $foundUser) {
            $cursor++;
            $this->assertEquals($user->getAge(), $foundUser->getAge());
            $this->assertEquals($user->getName(), $foundUser->getName());
        }

        $this->assertEquals(1, $cursor);
    }

    /**
     * 删除单个 entity
     * @param User $user
     * @depends testUserInsertOne
     */
    public function testUserDeleteOne(User $user)
    {
        $deletedCount = $this->userRepository->deleteOne($user);

        $this->assertEquals(1, $deletedCount);

        $foundUser = $this->userRepository->findOneById($user->getId());

        $this->assertNull($foundUser);
    }

    /**
     * 根据 filter 条件批量删除
     * @depends testUserInsertMany
     */
    public function testUserDeleteMany()
    {
        $filter = [
            'age' => ['$gte' => 10],
        ];
        $this->userRepository->deleteMany($filter);

        $count = $this->userRepository->count($filter);
        $this->assertEquals(0, $count);
    }

    /**
     * 更新单个 entity
     * @param User $user
     * @depends testUserInsertOne
     */
    public function testUserUpdateOne(User $user)
    {
        $newAge = $user->getAge() + 1;
        $user->setAge($newAge);

        $modifiedCount = $this->userRepository->updateOne($user);

        $this->assertEquals(1, $modifiedCount);

        /** @var User $foundUser */
        $foundUser = $this->userRepository->findOneById($user->getId());
        $this->assertEquals($newAge, $foundUser->getAge());
    }

    /**
     * 批量更新多个 entities
     * @depends testUserInsertMany
     */
    public function testUserUpdateMany()
    {
        $filter = [
            'age' => ['$gte' => 10],
        ];

        /** @var User[] $foundUsers */
        $foundUsers = $this->userRepository->findMany($filter);
        $foundCount = $this->userRepository->count($filter);

        $newWeight = 88.88;
        $updateUsers = (function () use ($foundUsers, $newWeight) {
            foreach ($foundUsers as $foundUser) {
                $foundUser->setWeight($newWeight);
                yield $foundUser;
            }
        })();

        $modifiedCount = $this->userRepository->updateMany($updateUsers);

        $this->assertEquals($foundCount, $modifiedCount);

        /** @var User[] $newestUsers */
        $newestUsers = $this->userRepository->findMany($filter);
        foreach ($newestUsers as $newestUser) {
            $this->assertEquals($newWeight, $newestUser->getWeight());
        }
    }


    /**
     * 插入或更新单个 entity (插入的情况)
     * @depends testUserCleanCollection
     */
    public function testUserUpsertOneWithInsert()
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

        $upsertCount = $this->userRepository->upsertOne($user);

        $this->assertEquals(1, $upsertCount);
        $this->assertInstanceOf(ObjectId::class, $user->getId());

        return $user;
    }

    /**
     * 插入或更新单个 entity (更新的情况)
     * @param User $user
     * @depends testUserInsertOne
     */
    public function testUserUpsertOneWithUpdate(User $user)
    {
        $newAge = $user->getAge() + 1;
        $user->setAge($newAge);

        $upsertCount = $this->userRepository->upsertOne($user);
        $this->assertEquals(1, $upsertCount);

        /** @var User $foundUser */
        $foundUser = $this->userRepository->findOneById($user->getId());
        $this->assertEquals($newAge, $foundUser->getAge());
    }

    /**
     * 插入或更新多个 entity
     * @depends testUserInsertMany
     */
    public function testUserUpsertMany()
    {
        $filter = [
            'age' => ['$gte' => 10],
        ];

        /** @var User[] $foundUsers */
        $foundUsers = $this->userRepository->findMany($filter);
        $foundCount = $this->userRepository->count($filter);

        $insertCount = 10;
        $upsertUsers = (function () use ($foundUsers, $insertCount) {

            foreach ($foundUsers as $foundUser) {
                $foundUser->setWeight(88.88);
                yield $foundUser;
            }

            foreach (range(1, $insertCount) as $value) {
                $user = new User();

                $user->setName('Jack - ' . rand(100, 999) . ' - ' . $value);
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

        $upsertCount = $this->userRepository->upsertMany($upsertUsers);

        $this->assertEquals($foundCount + $insertCount, $upsertCount);
    }

}