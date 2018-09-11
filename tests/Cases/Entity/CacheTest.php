<?php
/**
 * Swoft Entity Cache
 *
 * @author   limx <715557344@qq.com>
 * @link     https://github.com/limingxinleo/swoft-entity-cache
 */
// | EventTest.php [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 limingxinleo All rights reserved.
// +----------------------------------------------------------------------
// | Author: limx <715557344@qq.com> <https://github.com/limingxinleo>
// +----------------------------------------------------------------------
namespace SwoftTest\Db\Cases\Entity;

use Swoft\Helper\StringHelper;
use Swoft\Redis\Redis;
use SwoftTest\Db\Cases\AbstractMysqlCase;
use SwoftTest\Db\Testing\Entity\User;
use Swoftx\Db\Entity\Config\ModelCacheConfig;
use Swoftx\Db\Entity\Manager\ModelCacheManager;
use Swoftx\Db\Entity\Operator\Hashs\HashsGetMultiple;

class CacheTest extends AbstractMysqlCase
{
    public function testExample()
    {
        $this->assertTrue(true);

        $redis = bean(Redis::class);
        $res = $redis->type('sss');
        $this->assertEquals(\Redis::REDIS_NOT_FOUND, $res);

        go(function () use ($redis) {
            $res = $redis->type('sss');
            $this->assertEquals(\Redis::REDIS_NOT_FOUND, $res);

            $command = new HashsGetMultiple();

            $res = $redis->eval($command->getScript(), ['ss', 'bb'], 2);
            $res = $command->parseResponse($res);
            $this->assertEquals([], $res);

            $redis->hSet('xx', 'k', 'v');
            $res = $redis->eval($command->getScript(), ['ss', 'xx', 'bb'], 3);
            $res = $command->parseResponse($res);
            $this->assertEquals([['k' => 'v']], $res);
        });
    }

    public function testFind()
    {
        // 生成对应缓存
        $user = User::findOneByCache(1);

        $user2 = User::findById(1)->getResult();
        $user = User::findOneByCache(1);

        $this->assertEquals($user, $user2);
    }

    public function testFindByCo()
    {
        go(function () {
            $this->testFind();
        });
    }

    public function testFindAll()
    {
        $idMethod = 'get' . StringHelper::studly('user_id');
        $this->assertEquals('getUserId', $idMethod);

        $users = User::findAllByCache([1, 11111, 22222]);

        $this->assertEquals([1, 11111, 22222], array_keys($users));
        $this->assertInstanceOf(User::class, $users[1]);
        $this->assertNull($users[11111]);
        $this->assertNull($users[22222]);
    }

    public function testFindAllByCo()
    {
        go(function () {
            $this->testFindAll();
        });
    }

    public function testFindNotExist()
    {
        $user = User::findOneByCache(11111);
        $this->assertNull($user);

        $user = User::findOneByCache(11111);
        $this->assertNull($user);
    }

    public function testFindNotExistByCo()
    {
        go(function () {
            $this->testFindNotExist();
        });
    }

    public function testModelCacheConfig()
    {
        $config = bean(ModelCacheConfig::class);
        $this->assertEquals(env('ENTITY_CACHE_TTL'), $config->getTtl());
        $this->assertEquals(env('ENTITY_CACHE_PREFIX'), $config->getPrefix());
    }

    public function testModelCacheConfigByCo()
    {
        go(function () {
            $this->testModelCacheConfig();
        });
    }

    public function testUpdateAndDelete()
    {
        $name = 'oldName' . uniqid();
        $user = new User();
        $user->setName($name);
        $user->setRoleId(1);
        $user->setUpdatedAt(date('Y-m-d H:i:s'));
        $user->setCreatedAt(date('Y-m-d H:i:s'));
        $id = $user->save()->getResult();
        $this->assertTrue($id > 0);
        $this->assertEquals($name, $user->getName());

        $newName = 'newName' . uniqid();
        $user = User::findOneByCache($id);
        $user->setName($newName);
        $row = $user->update()->getResult();
        $this->assertEquals(1, $row);
        $this->assertEquals($newName, $user->getName());

        $user = User::findOneByCache($id);
        $this->assertEquals($newName, $user->getName());

        $user->delete()->getResult();
        $user = User::findOneByCache($id);
        $this->assertNull($user);

        $user = User::findById($id)->getResult();
        $this->assertNull($user);
    }

    public function testUpdateAndDeleteByCo()
    {
        go(function () {
            $this->testUpdateAndDelete();
        });
    }
}
