<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App\Test\Unit\Route;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\App\Route\Config
     */
    protected $_config;

    /**
     * @var \Magento\Framework\App\Route\Config\Reader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_readerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_cacheMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_configScopeMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_areaList;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serializerMock;

    protected function setUp()
    {
        $this->_readerMock = $this->getMock(\Magento\Framework\App\Route\Config\Reader::class, [], [], '', false);
        $this->_cacheMock = $this->getMock(\Magento\Framework\Config\CacheInterface::class);
        $this->_configScopeMock = $this->getMock(\Magento\Framework\Config\ScopeInterface::class);
        $this->_areaList = $this->getMock(\Magento\Framework\App\AreaList::class, [], [], '', false);
        $this->_configScopeMock->expects($this->any())
            ->method('getCurrentScope')
            ->willReturn('areaCode');
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_config = $objectManager->getObject(
            \Magento\Framework\App\Route\Config::class,
            [
                'reader' => $this->_readerMock,
                'cache' => $this->_cacheMock,
                'configScope' => $this->_configScopeMock,
                'areaList' => $this->_areaList
            ]
        );
        $this->serializerMock = $this->getMock(\Magento\Framework\Serialize\SerializerInterface::class);
        $objectManager->setBackwardCompatibleProperty($this->_config, 'serializer', $this->serializerMock);
    }

    public function testGetRouteFrontNameIfCacheIfRouterIdNotExist()
    {
        $this->_cacheMock->expects($this->once())
            ->method('load')
            ->with('areaCode::RoutesConfig')
            ->willReturn('["expected"]');
        $this->assertEquals('routerCode', $this->_config->getRouteFrontName('routerCode'));
    }

    public function testGetRouteByFrontName()
    {
        $data = ['routerCode' => ['frontName' => 'routerName']];
        $serializedData = json_encode($data);
        $this->_cacheMock->expects($this->once())
            ->method('load')
            ->with('areaCode::RoutesConfig')
            ->willReturn($serializedData);
        $this->serializerMock->method('unserialize')
            ->willReturn($data);
        $this->assertEquals('routerCode', $this->_config->getRouteByFrontName('routerName'));
    }

    public function testGetRouteByFrontNameNoRoutes()
    {
        $this->_cacheMock->expects($this->once())
            ->method('load')
            ->with('areaCode::RoutesConfig')
            ->willReturn('[]');
        $this->serializerMock->method('unserialize')
            ->willReturn([]);
        $this->assertFalse($this->_config->getRouteByFrontName('routerName'));
    }

    public function testGetRouteByFrontNameNoCache()
    {
        $this->_cacheMock->expects($this->once())
            ->method('load')
            ->with('scope::RoutesConfig')
            ->willReturn('false');

        $routes = [
            'routerCode' => [
                'frontName' => 'routerName',
            ],
        ];

        $routers = [
            'default_router' => [
                'routes' => $routes,
            ],
        ];

        $serializedData = json_encode($routes);

        $this->_readerMock->expects(
            $this->once()
        )->method(
            'read'
        )->with(
            'scope'
        )->will(
            $this->returnValue($routers)
        );

        $this->_areaList->expects(
            $this->once()
        )->method(
            'getDefaultRouter'
        )->with(
            'scope'
        )->will(
            $this->returnValue('default_router')
        );

        $this->serializerMock->method('serialize')
            ->willReturn($serializedData);

        $this->_cacheMock->expects($this->once())
            ->method('save')
            ->with($serializedData, 'scope::RoutesConfig');

        $this->assertEquals('routerCode', $this->_config->getRouteByFrontName('routerName', 'scope'));
    }

    public function testGetModulesByFrontName()
    {
        $data = ['routerCode' => ['frontName' => 'routerName', 'modules' => ['Module1']]];
        $serializedData = json_encode($data);
        $this->_cacheMock->expects($this->once())
            ->method('load')
            ->with('areaCode::RoutesConfig')
            ->willReturn($serializedData);
        $this->serializerMock->method('unserialize')
            ->willReturn($data);
        $this->assertEquals(['Module1'], $this->_config->getModulesByFrontName('routerName'));
    }
}
