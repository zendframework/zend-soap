<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Soap\Server;

use PHPUnit\Framework\TestCase;
use Zend\Soap\Client\Local as SoapClient;
use Zend\Soap\Server;
use Zend\Soap\Server\DocumentLiteralWrapper;
use ZendTest\Soap\TestAsset\MyCalculatorService;

class DocumentLiteralWrapperTest extends TestCase
{
    const WSDL = '/_files/calculator.wsdl';

    public function setUp()
    {
        ini_set("soap.wsdl_cache_enabled", 0);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDelegate()
    {
        $server = new Server(__DIR__ . self::WSDL);
        $server->setObject(new DocumentLiteralWrapper(new MyCalculatorService));

        // The local client needs an abstraction for this pattern as well.
        // This is just a test so we use the messy way.
        $client = new SoapClient($server, __DIR__ . self::WSDL);
        $ret = $client->add(['x' => 10, 'y' => 20]);

        $this->assertInstanceOf('stdClass', $ret);
        $this->assertEquals(30, $ret->addResult);
    }
}
