<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Soap;

use PHPUnit\Framework\TestCase;
use Zend\Soap\AutoDiscover;
use Zend\Soap\Client;
use Zend\Soap\Server;
use Zend\Soap\Wsdl;

class ClientTest extends TestCase
{
    public function setUp()
    {
        if (! extension_loaded('soap')) {
            $this->markTestSkipped('SOAP Extension is not loaded');
        }
    }

    public function testSetOptions()
    {
        /*************************************************************
         * ------ Test WSDL mode options -----------------------------
         *************************************************************/
        $client = new Client();

        $this->assertEquals(['encoding' => 'UTF-8', 'soap_version' => SOAP_1_2], $client->getOptions());

        $ctx = stream_context_create();

        $typeMap = [
            [
                'type_name'     => 'dateTime',
                'type_ns'       => 'http://www.w3.org/2001/XMLSchema',
                'from_xml'      => 'strtotime',
                'to_xml'        => 'strtotime',
            ],
            [
                'type_name'     => 'date',
                'type_ns'       => 'http://www.w3.org/2001/XMLSchema',
                'from_xml'      => 'strtotime',
                'to_xml'        => 'strtotime',
            ]
        ];

        $nonWSDLOptions = ['soap_version'   => SOAP_1_1,
                                'classmap'       => ['TestData1' => '\ZendTest\Soap\TestAsset\TestData1',
                                                    'TestData2' => '\ZendTest\Soap\TestAsset\TestData2',],
                                'encoding'       => 'ISO-8859-1',
                                'uri'            => 'http://framework.zend.com/Zend_Soap_ServerTest.php',
                                'location'       => 'http://framework.zend.com/Zend_Soap_ServerTest.php',
                                'use'            => SOAP_ENCODED,
                                'style'          => SOAP_RPC,

                                'login'          => 'http_login',
                                'password'       => 'http_password',

                                'proxy_host'     => 'proxy.somehost.com',
                                'proxy_port'     => 8080,
                                'proxy_login'    => 'proxy_login',
                                'proxy_password' => 'proxy_password',

                                'local_cert'     => __DIR__.'/TestAsset/cert_file',
                                'passphrase'     => 'some pass phrase',

                                'stream_context' => $ctx,
                                'cache_wsdl'     => WSDL_CACHE_NONE,
                                'features'       => 4,

                                'compression'    => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 5,
                                'typemap'        => $typeMap,
                                'keep_alive'     => true,
                                'ssl_method'     => 3,
        ];

        $client->setOptions($nonWSDLOptions);
        $this->assertEquals($nonWSDLOptions, $client->getOptions());

        /*************************************************************
         * ------ Test non-WSDL mode options -----------------------------
         *************************************************************/
        $client1 = new Client();

        $this->assertEquals(['encoding' => 'UTF-8', 'soap_version' => SOAP_1_2], $client1->getOptions());

        $wsdlOptions = ['soap_version'   => SOAP_1_1,
                             'wsdl'           => __DIR__.'/TestAsset/wsdl_example.wsdl',
                             'classmap'       => ['TestData1' => '\ZendTest\Soap\TestAsset\TestData1',
                                                 'TestData2' => '\ZendTest\Soap\TestAsset\TestData2',],
                             'encoding'       => 'ISO-8859-1',

                             'login'          => 'http_login',
                             'password'       => 'http_password',

                             'proxy_host'     => 'proxy.somehost.com',
                             'proxy_port'     => 8080,
                             'proxy_login'    => 'proxy_login',
                             'proxy_password' => 'proxy_password',

                             'local_cert'     => __DIR__.'/TestAsset/cert_file',
                             'passphrase'     => 'some pass phrase',

                             'stream_context' => $ctx,

                             'compression'    => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 5,
                             'typemap'        => $typeMap,
                             'keep_alive'     => true,
                             'ssl_method'     => 3,
        ];

        $client1->setOptions($wsdlOptions);
        $this->assertEquals($wsdlOptions, $client1->getOptions());
    }

    public function testGetOptions()
    {
        $client = new Client();

        $this->assertEquals(['encoding' => 'UTF-8', 'soap_version' => SOAP_1_2], $client->getOptions());

        $typeMap = [
            [
                'type_name'     => 'dateTime',
                'type_ns'       => 'http://www.w3.org/2001/XMLSchema',
                'from_xml'      => 'strtotime',
                'to_xml'        => 'strtotime',
            ],
            [
                'type_name'     => 'date',
                'type_ns'       => 'http://www.w3.org/2001/XMLSchema',
                'from_xml'      => 'strtotime',
                'to_xml'        => 'strtotime',
            ]
        ];

        $options = ['soap_version'   => SOAP_1_1,
                         'wsdl'           => __DIR__.'/TestAsset/wsdl_example.wsdl',

                         'classmap'       => ['TestData1' => '\ZendTest\Soap\TestAsset\TestData1',
                                             'TestData2' => '\ZendTest\Soap\TestAsset\TestData2',],
                         'encoding'       => 'ISO-8859-1',
                         'uri'            => 'http://framework.zend.com/Zend_Soap_ServerTest.php',
                         'location'       => 'http://framework.zend.com/Zend_Soap_ServerTest.php',
                         'use'            => SOAP_ENCODED,
                         'style'          => SOAP_RPC,

                         'login'          => 'http_login',
                         'password'       => 'http_password',

                         'proxy_host'     => 'proxy.somehost.com',
                         'proxy_port'     => 8080,
                         'proxy_login'    => 'proxy_login',
                         'proxy_password' => 'proxy_password',

                         'local_cert'     => __DIR__.'/TestAsset/cert_file',
                         'passphrase'     => 'some pass phrase',

                         'compression'    => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 5,
                         'typemap'        => $typeMap,
                         'keep_alive'     => true,
                         'ssl_method'     => 3,
        ];

        $client->setOptions($options);
        $this->assertEquals($options, $client->getOptions());
    }

    /**
     * @group ZF-8053
     */
    public function testGetAndSetUserAgentOption()
    {
        $client = new Client();
        $this->assertNull($client->getUserAgent());

        $client->setUserAgent('agent1');
        $this->assertEquals('agent1', $client->getUserAgent());

        $client->setOptions([
            'user_agent' => 'agent2'
        ]);
        $this->assertEquals('agent2', $client->getUserAgent());

        $client->setOptions([
            'useragent' => 'agent3'
        ]);
        $this->assertEquals('agent3', $client->getUserAgent());

        $client->setOptions([
            'userAgent' => 'agent4'
        ]);
        $this->assertEquals('agent4', $client->getUserAgent());

        $options = $client->getOptions();
        $this->assertEquals('agent4', $options['user_agent']);
    }

    /**
     * @group ZF-6954
     */
    public function testUserAgentAllowsEmptyString()
    {
        $client = new Client();
        $this->assertNull($client->getUserAgent());
        $options = $client->getOptions();
        $this->assertArrayNotHasKey('user_agent', $options);

        $client->setUserAgent('');
        $this->assertEquals('', $client->getUserAgent());
        $options = $client->getOptions();
        $this->assertEquals('', $options['user_agent']);

        $client->setUserAgent(null);
        $this->assertNull($client->getUserAgent());
        $options = $client->getOptions();
        $this->assertArrayNotHasKey('user_agent', $options);
    }

    /**
     * @group ZF-10542
     */
    public function testAllowNumericZeroAsValueForCacheWsdlOption()
    {
        $client = new Client();
        $this->assertNull($client->getWsdlCache());
        $options = $client->getOptions();
        $this->assertArrayNotHasKey('cache_wsdl', $options);

        $client->setWsdlCache(WSDL_CACHE_NONE);
        $this->assertSame(WSDL_CACHE_NONE, $client->getWsdlCache());
        $options = $client->getOptions();
        $this->assertSame(WSDL_CACHE_NONE, $options['cache_wsdl']);

        $client->setWsdlCache(null);
        $this->assertNull($client->getWsdlCache());
        $options = $client->getOptions();
        $this->assertArrayNotHasKey('cache_wsdl', $options);
    }

    /**
     * @expectedException \Zend\Soap\Exception\InvalidArgumentException
     */
    public function testInvalidCacheWsdlOptionException()
    {
        $client = new Client();
        $client->setWsdlCache(100);
    }

    /**
     * @group ZF-10542
     */
    public function testAllowNumericZeroAsValueForCompressionOptions()
    {
        $client = new Client();
        $this->assertNull($client->getCompressionOptions());
        $options = $client->getOptions();
        $this->assertArrayNotHasKey('compression', $options);

        $client->setCompressionOptions(SOAP_COMPRESSION_GZIP);
        $this->assertSame(SOAP_COMPRESSION_GZIP, $client->getCompressionOptions());
        $options = $client->getOptions();
        $this->assertSame(SOAP_COMPRESSION_GZIP, $options['compression']);

        $client->setCompressionOptions(null);
        $this->assertNull($client->getCompressionOptions());
        $options = $client->getOptions();
        $this->assertArrayNotHasKey('compression', $options);
    }

    public function testGetFunctions()
    {
        $server = new Server(__DIR__ . '/TestAsset/wsdl_example.wsdl');
        $server->setClass('\ZendTest\Soap\TestAsset\TestClass');

        $client = new Client\Local($server, __DIR__ . '/TestAsset/wsdl_example.wsdl');

        $expected = ['string testFunc()',
            'string testFunc2(string $who)',
            'string testFunc3(string $who, int $when)',
            'string testFunc4()'];
        $this->assertEquals($expected, $client->getFunctions());
    }

    public function testGetTypes()
    {
        $wsdlFilename = __DIR__ . '/TestAsset/GetTypesWsdlTest.wsdl';

        $autodiscover = new AutoDiscover();
        $autodiscover->setServiceName('ExampleService');
        $autodiscover->setComplexTypeStrategy(new \Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex);
        $autodiscover->setClass('\ZendTest\Soap\TestAsset\AutoDiscoverTestClass2');
        $autodiscover->setUri('http://example.com');
        $wsdl = $autodiscover->generate();
        $wsdl->dump($wsdlFilename);

        $server = new Server($wsdlFilename);
        $server->setClass('\ZendTest\Soap\TestAsset\AutoDiscoverTestClass2');

        $client = new Client\Local($server, $wsdlFilename);
        $soapClient = $client->getSoapClient();

        $typesArray = $soapClient->__getTypes();

        $this->assertCount(2, $typesArray);

        $count = 0;
        foreach ($typesArray as $element) {
            if (strpos($element, 'struct AutoDiscoverTestClass1') === 0
                || strpos($element, 'AutoDiscoverTestClass1 ArrayOfAutoDiscoverTestClass1') === 0
            ) {
                $count++;
            }
        }
        $this->assertEquals(2, $count, 'Invalid types');

        unlink($wsdlFilename);
    }

    /**
     * @outputBuffering enabled
     */
    public function testGetLastRequest()
    {
        if (headers_sent($file, $line)) {
            $this->markTestSkipped(sprintf(
                'Cannot run testGetLastRequest() when headers have already been sent. '
                . 'Output started in %s@%d; enable output buffering to run this test',
                $file,
                $line
            ));
        }

        $server = new Server(__DIR__ . '/TestAsset/wsdl_example.wsdl');
        $server->setClass('\ZendTest\Soap\TestAsset\TestClass');

        $client = new Client\Local($server, __DIR__ . '/TestAsset/wsdl_example.wsdl');

        // Perform request
        $client->testFunc2('World');

        $expectedRequest = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                         . '<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" '
                         .               'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
                         .               'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
                         .               'xmlns:enc="http://www.w3.org/2003/05/soap-encoding">'
                         .     '<env:Body>'
                         .         '<env:testFunc2 env:encodingStyle="http://www.w3.org/2003/05/soap-encoding">'
                         .             '<who xsi:type="xsd:string">World</who>'
                         .         '</env:testFunc2>'
                         .     '</env:Body>'
                         . '</env:Envelope>' . "\n";

        $this->assertEquals($client->getLastRequest(), $expectedRequest);
    }

    public function testGetLastResponse()
    {
        if (headers_sent()) {
            $this->markTestSkipped(
                'Cannot run testGetLastResponse() when headers have already been sent; '
                . 'enable output buffering to run this test'
            );
        }

        $server = new Server(__DIR__ . '/TestAsset/wsdl_example.wsdl');
        $server->setClass('\ZendTest\Soap\TestAsset\TestClass');

        $client = new Client\Local($server, __DIR__ . '/TestAsset/wsdl_example.wsdl');

        // Perform request
        $client->testFunc2('World');

        $expectedResponse = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" '
            .               'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
            .               'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            .               'xmlns:enc="http://www.w3.org/2003/05/soap-encoding">'
            .     '<env:Body xmlns:rpc="http://www.w3.org/2003/05/soap-rpc">'
            .         '<env:testFunc2Response env:encodingStyle="http://www.w3.org/2003/05/soap-encoding">'
            .             '<rpc:result>testFunc2Return</rpc:result>'
            .             '<testFunc2Return xsi:type="xsd:string">Hello World!</testFunc2Return>'
            .         '</env:testFunc2Response>'
            .     '</env:Body>'
            . '</env:Envelope>' . "\n";

        $this->assertEquals($client->getLastResponse(), $expectedResponse);
    }

    public function testCallInvoke()
    {
        if (headers_sent()) {
            $this->markTestSkipped(
                'Cannot run testCallInvoke() when headers have already been sent; '
                . 'enable output buffering to run this test'
            );
        }

        $server = new Server(__DIR__ . '/TestAsset/wsdl_example.wsdl');
        $server->setClass('\ZendTest\Soap\TestAsset\TestClass');

        $client = new Client\Local($server, __DIR__ . '/TestAsset/wsdl_example.wsdl');

        $this->assertEquals($client->testFunc2('World'), 'Hello World!');
    }

    public function testCallDirect()
    {
        if (headers_sent()) {
            $this->markTestSkipped(
                'Cannot run testCallInvoke() when headers have already been sent; '
                . 'enable output buffering to run this test'
            );
        }

        $server = new Server(__DIR__ . '/TestAsset/wsdl_example.wsdl');
        $server->setClass('\ZendTest\Soap\TestAsset\TestClass');

        $client = new Client\Local($server, __DIR__ . '/TestAsset/wsdl_example.wsdl');

        $this->assertEquals($client->call('testFunc2', ['World']), 'Hello World!');
    }

    public function testCallDirectWithArgString()
    {
        if (headers_sent()) {
            $this->markTestSkipped(
                'Cannot run testCallInvoke() when headers have already been sent; '
                . 'enable output buffering to run this test'
            );
        }

        $server = new Server(__DIR__ . '/TestAsset/wsdl_example.wsdl');
        $server->setClass('\ZendTest\Soap\TestAsset\TestClass');

        $client = new Client\Local($server, __DIR__ . '/TestAsset/wsdl_example.wsdl');

        $this->assertEquals($client->call('testFunc2', 'World'), 'Hello World!');
    }

    public function testSetOptionsWithZendConfig()
    {
        $ctx = stream_context_create();

        $nonWSDLOptions = ['soap_version'   => SOAP_1_1,
                                'classmap'       => ['TestData1' => '\ZendTest\Soap\TestAsset\TestData1',
                                                    'TestData2' => '\ZendTest\Soap\TestAsset\TestData2',],
                                'encoding'       => 'ISO-8859-1',
                                'uri'            => 'http://framework.zend.com/Zend_Soap_ServerTest.php',
                                'location'       => 'http://framework.zend.com/Zend_Soap_ServerTest.php',
                                'use'            => SOAP_ENCODED,
                                'style'          => SOAP_RPC,

                                'login'          => 'http_login',
                                'password'       => 'http_password',

                                'proxy_host'     => 'proxy.somehost.com',
                                'proxy_port'     => 8080,
                                'proxy_login'    => 'proxy_login',
                                'proxy_password' => 'proxy_password',

                                'local_cert'     => __DIR__.'/TestAsset/cert_file',
                                'passphrase'     => 'some pass phrase',

                                'stream_context' => $ctx,

                                'compression'    => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 5
        ];

        $config = new \Zend\Config\Config($nonWSDLOptions);

        $client = new Client(null, $config);

        $this->assertEquals($nonWSDLOptions, $client->getOptions());
    }

    public function testSetInputHeaders()
    {
        if (headers_sent()) {
            $this->markTestSkipped(
                'Cannot run testSetInputHeaders() when headers have already been sent; '
                . 'enable output buffering to run this test'
            );
        }

        $server = new Server(__DIR__ . '/TestAsset/wsdl_example.wsdl');
        $server->setClass('\ZendTest\Soap\TestAsset\TestClass');

        $client = new Client\Local($server, __DIR__ . '/TestAsset/wsdl_example.wsdl');

        // Add request header
        $client->addSoapInputHeader(new \SoapHeader(
            'http://www.example.com/namespace',
            'MyHeader1',
            'SOAP header content 1'
        ));

        // Add permanent request header
        $client->addSoapInputHeader(new \SoapHeader(
            'http://www.example.com/namespace',
            'MyHeader2',
            'SOAP header content 2'
        ), true);

        // Perform request
        $client->testFunc2('World');

        $expectedRequest = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                         . '<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" '
                         .               'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
                         .               'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
                         .               'xmlns:ns1="http://www.example.com/namespace" '
                         .               'xmlns:enc="http://www.w3.org/2003/05/soap-encoding">'
                         .     '<env:Header>'
                         .         '<ns1:MyHeader2>SOAP header content 2</ns1:MyHeader2>'
                         .         '<ns1:MyHeader1>SOAP header content 1</ns1:MyHeader1>'
                         .     '</env:Header>'
                         .     '<env:Body>'
                         .         '<env:testFunc2 env:encodingStyle="http://www.w3.org/2003/05/soap-encoding">'
                         .             '<who xsi:type="xsd:string">World</who>'
                         .         '</env:testFunc2>'
                         .     '</env:Body>'
                         . '</env:Envelope>' . "\n";

        $this->assertEquals($client->getLastRequest(), $expectedRequest);

        // Add request header
        $client->addSoapInputHeader(
            new \SoapHeader('http://www.example.com/namespace', 'MyHeader3', 'SOAP header content 3')
        );

        // Perform request
        $client->testFunc2('World');

        $expectedRequest = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                         . '<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" '
                         .               'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
                         .               'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
                         .               'xmlns:ns1="http://www.example.com/namespace" '
                         .               'xmlns:enc="http://www.w3.org/2003/05/soap-encoding">'
                         .     '<env:Header>'
                         .         '<ns1:MyHeader2>SOAP header content 2</ns1:MyHeader2>'
                         .         '<ns1:MyHeader3>SOAP header content 3</ns1:MyHeader3>'
                         .     '</env:Header>'
                         .     '<env:Body>'
                         .         '<env:testFunc2 env:encodingStyle="http://www.w3.org/2003/05/soap-encoding">'
                         .             '<who xsi:type="xsd:string">World</who>'
                         .         '</env:testFunc2>'
                         .     '</env:Body>'
                         . '</env:Envelope>' . "\n";

        $this->assertEquals($client->getLastRequest(), $expectedRequest);

        $client->resetSoapInputHeaders();

        // Add request header
        $client->addSoapInputHeader(
            new \SoapHeader('http://www.example.com/namespace', 'MyHeader4', 'SOAP header content 4')
        );

        // Perform request
        $client->testFunc2('World');

        $expectedRequest = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                         . '<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" '
                         .               'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
                         .               'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
                         .               'xmlns:ns1="http://www.example.com/namespace" '
                         .               'xmlns:enc="http://www.w3.org/2003/05/soap-encoding">'
                         .     '<env:Header>'
                         .         '<ns1:MyHeader4>SOAP header content 4</ns1:MyHeader4>'
                         .     '</env:Header>'
                         .     '<env:Body>'
                         .         '<env:testFunc2 env:encodingStyle="http://www.w3.org/2003/05/soap-encoding">'
                         .             '<who xsi:type="xsd:string">World</who>'
                         .         '</env:testFunc2>'
                         .     '</env:Body>'
                         . '</env:Envelope>' . "\n";

        $this->assertEquals($client->getLastRequest(), $expectedRequest);
    }

    /**
     * @group ZF-6955
     */
    public function testSetCookieIsDelegatedToSoapClient()
    {
        $fixtureCookieKey = "foo";
        $fixtureCookieValue = "bar";

        $clientMock = $this->getMockBuilder('SoapClient')
            ->setMethods(['__setCookie'])
            ->setConstructorArgs(
                [
                    null,
                    ['uri' => 'http://www.zend.com', 'location' => 'http://www.zend.com']
                ]
            )
            ->getMock();

        $clientMock->expects($this->once())
                   ->method('__setCookie')
                   ->with($fixtureCookieKey, $fixtureCookieValue);

        $soap = new Client();
        $soap->setSoapClient($clientMock);

        $soap->setCookie($fixtureCookieKey, $fixtureCookieValue);
    }

    public function testSetSoapClient()
    {
        $clientMock = $this->getMockBuilder('SoapClient')
            ->setMethods(['__setCookie'])
            ->setConstructorArgs(
                [
                    null,
                    ['uri' => 'http://www.zend.com', 'location' => 'http://www.zend.com']
                ]
            )
            ->getMock();

        $soap = new Client();
        $soap->setSoapClient($clientMock);

        $this->assertSame($clientMock, $soap->getSoapClient());
    }

    /**
     * @expectedException \Zend\Soap\Exception\UnexpectedValueException
     * @dataProvider dataProviderForInitSoapClientObjectException
     */
    public function testInitSoapClientObjectException($wsdl, $options)
    {
        $client = new Client($wsdl, $options);
        $client->getSoapClient();
    }

    /**
     * @return array
     */
    public function dataProviderForInitSoapClientObjectException()
    {
        return [
            [null,                             []],
            [null,                             ['location' => 'http://example.com']],
            [__DIR__ . './TestAsset/wsdl_example.wsdl',    ['use' => SOAP_ENCODED]],
            [__DIR__ . './TestAsset/wsdl_example.wsdl',    ['style' => SOAP_DOCUMENT]]
        ];
    }
}
