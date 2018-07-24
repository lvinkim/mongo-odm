<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 23/07/2018
 * Time: 10:20 PM
 */

namespace Tests;

use Lvinkim\MongoODM\DocumentManager;
use Lvinkim\MongoODM\EntityInterface;
use MongoDB\Driver\Manager;
use PHPUnit\Framework\TestCase;
use Tests\DAO\UserDAO;
use Tests\Entity\Embed\Address;
use Tests\Entity\Embed\Company;
use Tests\Entity\Embed\Member;
use Tests\Entity\User;

class MongoODMTest extends TestCase
{
    /** @var DocumentManager */
    private $documentManager;

    /** @var UserDAO */
    private $userDAO;

    public function setUp()
    {
        \Doctrine\Common\Annotations\AnnotationRegistry::registerUniqueLoader(function () {
            return true;
        });

        $uri = 'mongodb://docker.for.mac.localhost';
        $driver = new Manager($uri);

        $this->documentManager = new DocumentManager($driver);
        $this->userDAO = $this->documentManager->getDAO(UserDAO::class);

    }

    /**
     * 清空数据表
     */
    public function testCleanCollection()
    {
        $this->userDAO->delete();
        $count = $this->userDAO->count();
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

        $insertedCount = $this->userDAO->insert($user);

        $this->assertEquals(1, $insertedCount);

        return $user;
    }


    /**
     * 查找单条记录
     * @param User $user
     * @depends testInsertOne
     */
    public function testFindOne(User $user)
    {
        /** @var User $entity */
        $entity = $this->userDAO->findOne(['name' => $user->getName()]);

        $this->assertInstanceOf(EntityInterface::class, $entity);
        $this->assertEquals($user->getName(), $entity->getName());
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

}