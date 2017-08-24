<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Soap\AutoDiscover;

use PHPUnit\Framework\TestCase;
use Zend\Soap\Client;

class OnlineTest extends TestCase
{
    protected $baseuri;

    public function setUp()
    {
        if (! getenv('TESTS_ZEND_SOAP_AUTODISCOVER_ONLINE_SERVER_BASEURI')) {
            $this->markTestSkipped(
                'Enable TESTS_ZEND_SOAP_AUTODISCOVER_ONLINE_SERVER_BASEURI to allow the Online test to work.'
            );
        }
        $this->baseuri = getenv('TESTS_ZEND_SOAP_AUTODISCOVER_ONLINE_SERVER_BASEURI');
    }

    public function testNestedObjectArrayResponse()
    {
        $wsdl = $this->baseuri."/server1.php?wsdl";

        $b = new \ZendTest_Soap_TestAsset_ComplexTypeB();
        $b->bar = "test";
        $b->foo = "test";

        $client = new Client($wsdl);
        $ret = $client->request($b);

        $this->assertInternalType('array', $ret);
        $this->assertEquals(1, count($ret));
        $this->assertInternalType('array', $ret[0]->baz);
        $this->assertEquals(3, count($ret[0]->baz));

        $baz = $ret[0]->baz;
        $this->assertEquals("bar", $baz[0]->bar);
        $this->assertEquals("bar", $baz[0]->foo);
        $this->assertEquals("foo", $baz[1]->bar);
        $this->assertEquals("foo", $baz[1]->foo);
        $this->assertEquals("test", $baz[2]->bar);
        $this->assertEquals("test", $baz[2]->foo);
    }

    public function testObjectResponse()
    {
        $wsdl = $this->baseuri."/server2.php?wsdl";

        $client = new Client($wsdl);
        $ret = $client->request("test", "test");

        $this->assertInstanceOf('stdClass', $ret);
        $this->assertEquals("test", $ret->foo);
        $this->assertEquals("test", $ret->bar);
    }
}
