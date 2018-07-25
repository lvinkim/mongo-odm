# mongo-odm 

### 安装
```
$ composer require lvinkim/mongo-odm
```

### 使用说明

#### 步骤1. 定义 Entity

```php
use Lvinkim\MongoODM\Annotations as ODM;
use Lvinkim\MongoODM\Entity;
use MongoDB\BSON\ObjectId;

class User extends Entity
{
    /**
     * @var ObjectId
     * @ODM\Id
     */
    private $_id;
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
        return $this->_id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->_id = $id;
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

#### 步骤2. 定义 DAO 类

```php
use Lvinkim\MongoODM\EntityDAO;

class UserDAO extends EntityDAO
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
    protected function getEntity(): string
    {
        return User::class;
    }
}
```

#### 步骤3. 使用示例

```php

use Lvinkim\MongoODM\DocumentManager;
use MongoDB\Driver\Manager;

// 必须添加这行代码
\Doctrine\Common\Annotations\AnnotationRegistry::registerUniqueLoader(function () {
    return true;
});

$uri = 'mongodb://docker.for.mac.localhost';
$driver = new Manager($uri);

$documentManager = new DocumentManager($driver);
$userDAO = $documentManager->getDAO(UserDAO::class);

// 插入
$user = new User();
$user->setName("your name");
$userDAO->insertOne($user);

// 更多方法.... 
// 计数 - count,
// 删除 - delete,
// 插入单个 - insertOne,
// 查找单个 - findOne,
// 查找多个 - find,
// 更新单个 - updateOne,
// 插入或更新单个 - upsertOne,
// 批量插入 - insertMany,
// 批量更新  - updateMany,
// 批量插入或更新 - upsertMany

```

