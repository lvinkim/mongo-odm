# mongo-odm 

### 安装
```
$ composer require lvinkim/mongo-odm
```

### 方法概述

* 通过 ID 获取单个 Entity 的方法：findOneById($id)
* 通过 filter 获取单个 Entity 的方法：findOne($filter)
* 通过 $filter 获取多个 Entity 的方法：findMany($filter, ...)
* 删除单个 Entity 的方法：deleteOne($entity)
* 通过 $filter 批量删除的方法：deleteMany($filter)
* 插入单个 Entity 的方法：insertOne($entity)
* 插入多个 Entity 的方法：insertMany($entities)
* 更新单个 Entity 的方法：updateOne($entity)
* 更新多个 Entity 的方法：updateMany($entities)
* 插入或更新单个 Entity 的方法：upsertOne($entity)
* 插入或更新多个 Entity 的方法：upsertMany($entities)

### 使用说明

#### 步骤1. 定义 Entity

```php
use Lvinkim\MongoODM\Annotations as ODM;
use Lvinkim\MongoODM\Entity;
use MongoDB\BSON\ObjectId;

/**
 * Class User
 * @ODM\Entity()
 */
class User
{
    /**
     * @var ObjectId
     * @ODM\Id
     */
    private $id;
    /**
     * 名称
     * @var string
     * @ODM\Field(type="string")
     */
    private $name;
    
    /**
     * @return ObjectId
     */
    public function getId(): ?ObjectId
    {
        return $this->id;
    }

    /**
     * @param ObjectId $id
     */
    public function setId(ObjectId $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
```

#### 步骤2. 定义 Repository 类

```php
use Lvinkim\MongoODM\Repository;

class UserRepository extends Repository
{

    /**
     * 返回数据库中的表名, 例如: db.user
     * @return string
     */
    protected function getNamespace(): string
    {
        return 'test.user';
    }

    /**
     * 返回数据表的对应实体类名
     * @return string
     */
    protected function getEntityClassName(): string
    {
        return User::class;
    }
}
```

#### 步骤3. 使用示例

```php

use Lvinkim\MongoODM\DocumentManager;
use MongoDB\Driver\Manager;

$uri = 'mongodb://docker.for.mac.localhost';
$driver = new Manager($uri);

$documentManager = new DocumentManager($driver);
$userRepository = $documentManager->getRepository(UserRepository::class);

// 插入
$user = new User();
$user->setName("your name");
$userRepository->insertOne($user);

// 更多方法.... 参见 Functional/RepositoryTest.php 的各用例

```