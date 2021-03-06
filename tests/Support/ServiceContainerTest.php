<?php
/**
 * Created by PhpStorm.
 * User: f-oris
 * Date: 2019/6/17
 * Time: 9:49 AM
 */

namespace EasySmartProgram\Tests\Support;

use EasySmartProgram\Support\ServiceContainer;
use EasySmartProgram\Tests\TestCase;
use Pimple\Exception\FrozenServiceException;

/**
 * Class ServiceContainerTest
 * @package EasySmartProgram\Tests\Support
 * @author  f-oris <us@f-oris.me>
 * @version 1.0.0
 */
class ServiceContainerTest extends TestCase
{
    /**
     * test get config
     */
    public function testGetConfig()
    {
        $config = ['key' => 'value'];
        $this->assertSame($config, (new ServiceContainer($config))->getConfig());
    }

    /**
     * test bind service
     */
    public function testBindService()
    {
        $container = new ServiceContainer();

        $container['service'] = function () {
            return 'test service';
        };

        $this->assertSame('test service', $container['service']);

        return $container;
    }

    /**
     * @param ServiceContainer $container
     * @depends testBindService
     */
    public function testBindDuplicateService(ServiceContainer $container)
    {
        $this->expectException(FrozenServiceException::class);
        $this->expectExceptionMessage('Cannot override frozen service "service".');

        $container['service'] = function () {
            return 'test service 2';
        };
    }

    /**
     * @param ServiceContainer $container
     * @depends testBindService
     */
    public function testRebindService(ServiceContainer $container)
    {
        $container->rebind('service', function () {
            return 'test service 2';
        });

        $this->assertSame('test service 2', $container['service']);
    }

    /**
     * test magic set and get service
     */
    public function testMagicSetAndGet()
    {
        $container = new ServiceContainer();

        $container->service = function () {
            return 'test service';
        };

        $this->assertSame('test service', $container->service);
    }
}