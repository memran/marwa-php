<?php

namespace Unit;
use Marwa\Application\Cache\Cache;
use Marwa\Application\Cache\CacheInterface;
use Marwa\Application\Cache\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CacheFileTest extends TestCase
{

    /**
     * @throws InvalidArgumentException
     */
    public function testExceptionOnConstructor()
    {
        $this->expectException(InvalidArgumentException::class);
        $cache = new Cache([]);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testCacheOnConstructor()
    {
        $config =[
            'file' =>[
                'path' => '/cache',
                'expire' => 0,
            ]];
        $cache = new Cache($config);
        $this->assertInstanceOf(CacheInterface::class,$cache);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testCacheDiskConfig()
    {
        $config =[
            'file' =>[
                'path' => '/cache',
                'expire' => 0,
            ]];
        $cache = new Cache($config);
        $this->assertEquals('file',$cache->getDisk());
    }

}