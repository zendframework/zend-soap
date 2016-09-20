<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Soap;

use Zend\Soap\AutoDiscover;
use Zend\Soap\Wsdl;
use Zend\Uri\Uri;

class AutoDiscoverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AutoDiscover
     */
    protected $server;

    /**
     * @var string
     */
    protected $defaultServiceName = 'MyService';

    /**
     * @var string
     */
    protected $defaultServiceUri = 'http://localhost/MyService.php';

    /**
     * @var \DOMDocument
     */
    protected $dom;

    /**
     * @var \DOMXPath
     */
    protected $xpath;

    public function setUp()
    {
        $this->server = new AutoDiscover();
        $this->server->setUri($this->defaultServiceUri);
        $this->server->setServiceName($this->defaultServiceName);
    }

    /**
     *
     *
     * @param \Zend\Soap\Wsdl $wsdl
     * @param null            $documentNamespace
     */
    public function bindWsdl(Wsdl $wsdl, $documentNamespace = null)
    {
        $this->dom                     = new \DOMDocument();
        $this->dom->formatOutput       = true;
        $this->dom->preserveWhiteSpace = false;

        $this->dom->loadXML($wsdl->toXML());

        if (empty($documentNamespace)) {
            $documentNamespace = $this->defaultServiceUri;
        }

        $this->xpath = new \DOMXPath($this->dom);

        $this->xpath->registerNamespace('unittest', Wsdl::WSDL_NS_URI);

        $this->xpath->registerNamespace('tns', $documentNamespace);
        $this->xpath->registerNamespace('soap', Wsdl::SOAP_11_NS_URI);
        $this->xpath->registerNamespace('soap12', Wsdl::SOAP_12_NS_URI);
        $this->xpath->registerNamespace('xsd', Wsdl::XSD_NS_URI);
        $this->xpath->registerNamespace('soap-enc', Wsdl::SOAP_ENC_URI);
        $this->xpath->registerNamespace('wsdl', Wsdl::WSDL_NS_URI);
    }

    /**
     * Assertion to validate DOMDocument is a valid WSDL file.
     *
     * @param \DOMDocument $dom
     */
    protected function assertValidWSDL(\DOMDocument $dom)
    {
        // this code is necessary to support some libxml stupidities.
        // @todo memory streams ?
        $file = __DIR__ . '/TestAsset/validate.wsdl';
        if (file_exists($file)) {
            unlink($file);
        }

        $dom->save($file);
        $dom = new \DOMDocument();
        $dom->load($file);

        $this->assertTrue(
            $dom->schemaValidate(__DIR__ . '/schemas/wsdl.xsd'),
            "WSDL Did not validate"
        );
        unlink($file);
    }

    /**
     * @param \DOMElement $element
     */
    public function testDocumentNodes($element = null)
    {
        if (!($this->dom instanceof \DOMDocument)) {
            return;
        }

        if (null === $element) {
            $element = $this->dom->documentElement;
        }

        /** @var $node \DOMElement */
        foreach ($element->childNodes as $node) {
            if (in_array($node->nodeType, [XML_ELEMENT_NODE])) {
                $this->assertNotEmpty(
                    $node->namespaceURI,
                    'Document element: '
                    . $node->nodeName . ' has no valid namespace. Line: '
                    . $node->getLineNo()
                );
                $this->testDocumentNodes($node);
            }
        }
    }

    /**
     * @dataProvider dataProviderValidUris
     */
    public function testAutoDiscoverConstructorUri($uri, $expectedUri)
    {
        $server = new AutoDiscover(null, $uri);

        $this->assertEquals($expectedUri, $server->getUri()->toString());
    }

    /**
     * @dataProvider dataProviderForAutoDiscoverConstructorStrategy
     */
    public function testAutoDiscoverConstructorStrategy($strategy)
    {
        $server = new AutoDiscover($strategy);

        $server->addFunction('\ZendTest\Soap\TestAsset\TestFunc');
        $server->setServiceName('TestService');
        $server->setUri('http://example.com');
        $wsdl = $server->generate();

        $this->assertEquals(
            get_class($strategy),
            get_class($wsdl->getComplexTypeStrategy())
        );
    }

    /**
     * @return array
     */
    public function dataProviderForAutoDiscoverConstructorStrategy()
    {
        return [
            [new Wsdl\ComplexTypeStrategy\AnyType()],
            [new Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex()],
            [new Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence()],
            [new Wsdl\ComplexTypeStrategy\Composite()],
            [new Wsdl\ComplexTypeStrategy\DefaultComplexType()],
        ];
    }

    public function testGetDiscoveryStrategy()
    {
        $server = new AutoDiscover();

        $this->assertEquals(
            'Zend\Soap\AutoDiscover\DiscoveryStrategy\ReflectionDiscovery',
            get_class($server->getDiscoveryStrategy())
        );
    }

    public function testAutoDiscoverConstructorWsdlClass()
    {
        $server = new AutoDiscover(null, null, '\Zend\Soap\Wsdl');

        $server->addFunction('\ZendTest\Soap\TestAsset\TestFunc');
        $server->setServiceName('TestService');
        $server->setUri('http://example.com');
        $wsdl = $server->generate();

        $this->assertEquals('Zend\Soap\Wsdl', trim(get_class($wsdl), '\\'));
        $this->assertEquals(
            'Zend\Soap\Wsdl',
            trim($server->getWsdlClass(), '\\')
        );
    }

    /**
     * @expectedException \Zend\Soap\Exception\InvalidArgumentException
     */
    public function testAutoDiscoverConstructorWsdlClassException()
    {
        $server = new AutoDiscover();
        $server->setWsdlClass(new \stdClass());
    }

    /**
     * @dataProvider dataProviderForSetServiceName
     */
    public function testSetServiceName($newName, $shouldBeValid)
    {
        if ($shouldBeValid == false) {
            $this->setExpectedException('InvalidArgumentException');
        }

        $this->server->setServiceName($newName);
        $this->bindWsdl($this->server->generate());
        $this->assertSpecificNodeNumberInXPath(
            1,
            '/wsdl:definitions[@name="' . $newName . '"]'
        );
    }

    /**
     * @return array
     */
    public function dataProviderForSetServiceName()
    {
        return [
            ['MyServiceName123', true],
            ['1MyServiceName123', false],
            ['$MyServiceName123', false],
            ['!MyServiceName123', false],
            ['&MyServiceName123', false],
            ['(MyServiceName123', false],
            ['\MyServiceName123', false],
        ];
    }

    public function testGetServiceName()
    {
        $server = new AutoDiscover();

        $server->setClass('\ZendTest\Soap\TestAsset\Test');

        $this->assertEquals('Test', $server->getServiceName());
    }

    /**
     * @expectedException \Zend\Soap\Exception\RuntimeException
     */
    public function testGetServiceNameException()
    {
        $server = new AutoDiscover();

        $server->addFunction('\ZendTest\Soap\TestAsset\TestFunc');

        $this->assertEquals('Test', $server->getServiceName());
    }

    /**
     * @expectedException \Zend\Soap\Exception\InvalidArgumentException
     */
    public function testSetUriException()
    {
        $server = new AutoDiscover();

        $server->setUri(' ');
    }

    /**
     * @expectedException \Zend\Soap\Exception\RuntimeException
     */
    public function testGetUriException()
    {
        $server = new AutoDiscover();
        $server->getUri();
    }

    public function testClassMap()
    {
        $classMap = [
            'TestClass' => 'test_class'
        ];

        $this->server->setClassMap($classMap);

        $this->assertEquals($classMap, $this->server->getClassMap());
    }

    public function testSetClass()
    {
        $this->server->setClass('\ZendTest\Soap\TestAsset\Test');

        $this->bindWsdl($this->server->generate());

        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema[@targetNamespace="'
            . $this->defaultServiceUri . '"]',
            'Invalid schema definition'
        );

        for ($i = 1; $i <= 4; $i++) {
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="testFunc'
                    . $i . '"]',
                'Invalid func' . $i . ' operation definition'
            );
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="testFunc'
                    . $i . '"]/wsdl:documentation',
                'Invalid func' . $i . ' port definition - documentation node'
            );
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="testFunc'
                    . $i . '"]/wsdl:input[@message="tns:testFunc' . $i . 'In"]',
                'Invalid func' . $i . ' port definition - input node'
            );
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="testFunc'
                    . $i . '"]/wsdl:output[@message="tns:testFunc' . $i . 'Out"]',
                'Invalid func' . $i . ' port definition - output node'
            );
        }

        $nodes = $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding"]',
            'Invalid service binding definition'
        );
        $this->assertEquals(
            'tns:MyServicePort',
            $nodes->item(0)->getAttribute('type'),
            'Invalid type attribute value in service binding definition'
        );

        $nodes = $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding"]/soap:binding',
            'Invalid service binding definition'
        );
        $this->assertEquals(
            'rpc',
            $nodes->item(0)->getAttribute('style'),
            'Invalid style attribute value in service binding definition'
        );
        $this->assertEquals(
            'http://schemas.xmlsoap.org/soap/http',
            $nodes->item(0)->getAttribute('transport'),
            'Invalid transport attribute value in service binding definition'
        );

        for ($i = 1; $i <= 4; $i++) {
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:binding[@name="MyServiceBinding"]/wsdl:operation[@name="testFunc'. $i . '"]',
                'Invalid func' . $i . ' operation binding definition'
            );
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:binding[@name="MyServiceBinding"]/wsdl:operation[@name="testFunc'
                    . $i . '"]/soap:operation[@soapAction="' . $this->defaultServiceUri .
                    '#testFunc' . $i . '"]',
                'Invalid func' . $i . ' operation action binding definition'
            );
        }

        $xpath
            = '//wsdl:binding[@name="MyServiceBinding"]/wsdl:operation[wsdl:input or wsdl:output]/*/soap:body';
        $this->assertSpecificNodeNumberInXPath(8, $xpath);
        $nodes = $this->xpath->query($xpath);
        $this->assertAttributesOfNodes(
            [
                 "use"           => "encoded",
                 "encodingStyle" => "http://schemas.xmlsoap.org/soap/encoding/",
                 "namespace"     => "http://localhost/MyService.php"
            ],
            $nodes
        );

        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:service[@name="MyServiceService"]',
            'Invalid service definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:service[@name="MyServiceService"]/'
                . 'wsdl:port[@name="MyServicePort" and @binding="tns:MyServiceBinding"]',
            'Invalid service port definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:service[@name="MyServiceService"]/'
                . 'wsdl:port[@name="MyServicePort" and @binding="tns:MyServiceBinding"]/soap:address[@location="'
                . $this->defaultServiceUri . '"]',
            'Invalid service address definition'
        );


        $nodes = $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="testFunc1In"]',
            'Invalid message definition'
        );
        $this->assertFalse($nodes->item(0)->hasChildNodes());

        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="testFunc2In"]',
            'Invalid message definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="testFunc2In"]/wsdl:part[@name="who" and @type="xsd:string"]',
            'Invalid message definition'
        );

        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="testFunc2Out"]',
            'Invalid message definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="testFunc2Out"]/wsdl:part[@name="return" and @type="xsd:string"]',
            'Invalid message definition'
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="testFunc3In"]',
            'Invalid message definition'
        );

        // @codingStandardsIgnoreStart
        $this->assertSpecificNodeNumberInXPath(
            2,
            '//wsdl:message[@name="testFunc3In"][(wsdl:part[@name="who" and @type="xsd:string"]) or (wsdl:part[@name="when" and @type="xsd:int"])]/wsdl:part',
            'Invalid message definition'
        );
        // @codingStandardsIgnoreEnd

        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="testFunc3Out"]',
            'Invalid message definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="testFunc3Out"]/wsdl:part[@name="return" and @type="xsd:string"]',
            'Invalid message definition'
        );


        $nodes = $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="testFunc4In"]',
            'Invalid message definition'
        );
        $this->assertFalse($nodes->item(0)->hasChildNodes());
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="testFunc4Out"]/wsdl:part[@name="return" and @type="xsd:string"]',
            'Invalid message definition'
        );


        $this->assertValidWSDL($this->dom);
        $this->testDocumentNodes();
    }

    public function testSetClassWithDifferentStyles()
    {
        $this->server->setBindingStyle(
            ['style'     => 'document',
                  'transport' => $this->defaultServiceUri]
        );
        $this->server->setOperationBodyStyle(
            ['use' => 'literal', 'namespace' => $this->defaultServiceUri]
        );
        $this->server->setClass('\ZendTest\Soap\TestAsset\Test');

        $this->bindWsdl($this->server->generate());

        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc1"]',
            'Missing test func1 definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc1"]/xsd:complexType',
            'Missing test func1 type definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            0,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc1"]/xsd:complexType/*',
            'Test func1 does not have children'
        );

        $nodes = $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc1Response"]/'
                .'xsd:complexType/xsd:sequence/xsd:element',
            'Test func1 return element is invalid'
        );
        $this->assertAttributesOfNodes(
            [
                 'name' => "testFunc1Result",
                 'type' => "xsd:string",
            ],
            $nodes
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc2"]',
            'Missing test func2 definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc2"]/xsd:complexType',
            'Missing test func2 type definition'
        );
        $nodes = $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc2"]/xsd:complexType/'
                .'xsd:sequence/xsd:element',
            'Test func2 does not have children'
        );
        $this->assertAttributesOfNodes(
            [
                 'name' => "who",
                 'type' => "xsd:string",
            ],
            $nodes
        );

        $nodes = $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc2Response"]/'
                .'xsd:complexType/xsd:sequence/xsd:element',
            'Test func2 return element is invalid'
        );
        $this->assertAttributesOfNodes(
            [
                 'name' => "testFunc2Result",
                 'type' => "xsd:string",
            ],
            $nodes
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc3"]',
            'Missing test func3 definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc3"]/xsd:complexType',
            'Missing test func3 type definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            2,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc3"]/xsd:complexType/'
                .'xsd:sequence/xsd:element',
            'Test func3 does not have children'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc3"]/xsd:complexType/'
                .'xsd:sequence/xsd:element[@name="who" and @type="xsd:string"]',
            'Test func3 does not have children'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc3"]/xsd:complexType/'
                .'xsd:sequence/xsd:element[@name="when" and @type="xsd:int"]',
            'Test func3 does not have children'
        );

        $nodes = $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc3Response"]/'
                .'xsd:complexType/xsd:sequence/xsd:element',
            'Test func3 return element is invalid'
        );
        $this->assertAttributesOfNodes(
            [
                 'name' => "testFunc3Result",
                 'type' => "xsd:string",
            ],
            $nodes
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc4"]',
            'Missing test func1 definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc4"]/xsd:complexType',
            'Missing test func1 type definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            0,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc4"]/xsd:complexType/*',
            'Test func1 does not have children'
        );

        $nodes = $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:element[@name="testFunc4Response"]/'
                .'xsd:complexType/xsd:sequence/xsd:element',
            'Test func1 return element is invalid'
        );
        $this->assertAttributesOfNodes(
            [
                 'name' => "testFunc4Result",
                 'type' => "xsd:string",
            ],
            $nodes
        );


        for ($i = 1; $i <= 4; $i++) {
            $this->assertSpecificNodeNumberInXPath(
                3,
                '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="testFunc'
                    . $i . '"]/*',
                'Missing test func' . $i . ' port definition'
            );
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="testFunc'
                    . $i . '"]/wsdl:documentation',
                'Missing test func' . $i . ' port documentation'
            );
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="testFunc'
                    . $i . '"]/wsdl:input[@message="tns:testFunc' . $i . 'In"]',
                'Missing test func' . $i . ' port input message'
            );
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="testFunc'
                    . $i . '"]/wsdl:output[@message="tns:testFunc' . $i
                    . 'Out"]',
                'Missing test func' . $i . ' port output message'
            );
        }


        for ($i = 1; $i <= 4; $i++) {
            $this->assertSpecificNodeNumberInXPath(
                3,
                '//wsdl:binding[@name="MyServiceBinding"]/wsdl:operation[@name="testFunc'
                    . $i . '"]/*',
                'Missing test func' . $i . ' binding definition'
            );
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:binding[@name="MyServiceBinding"]/wsdl:operation[@name="testFunc'
                    . $i . '"]/soap:operation[@soapAction="'
                    . $this->defaultServiceUri . '#testFunc' . $i . '"]',
                'Missing test func' . $i . ' binding operation definition'
            );
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:binding[@name="MyServiceBinding"]/wsdl:operation[@name="testFunc'
                    . $i
                    . '"]/wsdl:input/soap:body[@use="literal" and @namespace="'
                    . $this->defaultServiceUri . '"]',
                'Missing test func' . $i . ' binding input message'
            );
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:binding[@name="MyServiceBinding"]/wsdl:operation[@name="testFunc'
                    . $i
                    . '"]/wsdl:output/soap:body[@use="literal" and @namespace="'
                    . $this->defaultServiceUri . '"]',
                'Missing test func' . $i . ' binding input message'
            );
        }


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:service[@name="MyServiceService"]/wsdl:port[@name="MyServicePort"'
                . ' and @binding="tns:MyServiceBinding"]/soap:address[@location="'
                . $this->defaultServiceUri . '"]'
        );


        for ($i = 1; $i <= 4; $i++) {
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:message[@name="testFunc' . $i
                    . 'In"]/wsdl:part[@name="parameters" and @element="tns:testFunc' . $i . '"]',
                'Missing test testFunc' . $i . ' input message definition'
            );
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:message[@name="testFunc' . $i
                    . 'Out"]/wsdl:part[@name="parameters" and @element="tns:testFunc' . $i . 'Response"]',
                'Missing test testFunc' . $i . ' output message definition'
            );
        }


        $this->assertValidWSDL($this->dom);
        $this->testDocumentNodes();
    }

    /**
     * @group ZF-5072
     */
    public function testSetClassWithResponseReturnPartCompabilityMode()
    {
        $this->server->setClass('\ZendTest\Soap\TestAsset\Test');
        $this->bindWsdl($this->server->generate());


        for ($i = 1; $i <= 4; $i++) {
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:message[@name="testFunc' . $i . 'Out"]/wsdl:part[@name="return"]'
            );
        }


        $this->assertValidWSDL($this->dom);
    }

    /**
     * @expectedException \Zend\Soap\Exception\InvalidArgumentException
     * @dataProvider dataProviderForAddFunctionException
     */
    public function testAddFunctionException($function)
    {
        $this->server->addFunction($function);
    }

    /**
     * @return array
     */
    public function dataProviderForAddFunctionException()
    {
        return [
            ['InvalidFunction'],
            [1],
            [[1, 2]],
        ];
    }

    public function testAddFunctionSimple()
    {
        $this->server->addFunction('\ZendTest\Soap\TestAsset\TestFunc');
        $this->bindWsdl($this->server->generate());


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc"]',
            'Missing service port definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc"]/wsdl:documentation',
            'Missing service port definition documentation'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc"]/wsdl:input',
            'Missing service port definition input message'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc"]/wsdl:output',
            'Missing service port definition input message'
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . 'wsdl:operation[@name="TestFunc"]',
            'Missing service binding definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . ' soap:binding[@style="rpc" and @transport="http://schemas.xmlsoap.org/soap/http"]',
            'Missing service binding transport definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . ' wsdl:operation[@name="TestFunc"]/soap:operation[@soapAction="'
                . $this->defaultServiceUri . '#TestFunc"]',
            'Missing service operation action definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . 'wsdl:operation[@name="TestFunc"]/wsdl:input/soap:body[@use="encoded" '
                . 'and @encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" and @namespace="'
                . $this->defaultServiceUri . '"]',
            'Missing operation input body definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . 'wsdl:operation[@name="TestFunc"]/wsdl:output/soap:body[@use="encoded"'
                . 'and @encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" and @namespace="'
                . $this->defaultServiceUri . '"]',
            'Missing operation input body definition'
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:service[@name="MyServiceService"]/wsdl:port[@name="MyServicePort"'
                . ' and @binding="tns:MyServiceBinding"]/soap:address[@location="'
                . $this->defaultServiceUri . '"]',
            'Missing service port definition'
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="TestFuncIn"]/wsdl:part[@name="who" and @type="xsd:string"]',
            'Missing test testFunc input message definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="TestFuncOut"]/wsdl:part[@name="return" and @type="xsd:string"]',
            'Missing test testFunc input message definition'
        );


        $this->assertValidWSDL($this->dom);
        $this->testDocumentNodes();
    }

    public function testAddFunctionSimpleWithDifferentStyle()
    {
        $this->server->setBindingStyle(
            ['style'     => 'document',
                  'transport' => $this->defaultServiceUri]
        );
        $this->server->setOperationBodyStyle(
            ['use' => 'literal', 'namespace' => $this->defaultServiceUri]
        );
        $this->server->addFunction('\ZendTest\Soap\TestAsset\TestFunc');
        $this->bindWsdl($this->server->generate());


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema[@targetNamespace="'
                . $this->defaultServiceUri . '"]',
            'Missing service port definition'
        );

        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema[@targetNamespace="' . $this->defaultServiceUri
                . '"]/xsd:element[@name="TestFunc"]/xsd:complexType/xsd:sequence/'
                . 'xsd:element[@name="who" and @type="xsd:string"]',
            'Missing complex type definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema[@targetNamespace="' . $this->defaultServiceUri
                . '"]/xsd:element[@name="TestFuncResponse"]/xsd:complexType/xsd:sequence'
                . '/xsd:element[@name="TestFuncResult" and @type="xsd:string"]',
            'Missing complex type definition'
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc"]',
            'Missing service port definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc"]/wsdl:documentation',
            'Missing service port definition documentation'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc"]/wsdl:input',
            'Missing service port definition input message'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc"]/wsdl:output',
            'Missing service port definition input message'
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . 'wsdl:operation[@name="TestFunc"]',
            'Missing service binding definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . 'soap:binding[@style="document" and @transport="' . $this->defaultServiceUri . '"]',
            'Missing service binding transport definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . 'wsdl:operation[@name="TestFunc"]/soap:operation[@soapAction="'
                . $this->defaultServiceUri . '#TestFunc"]',
            'Missing service operation action definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . 'wsdl:operation[@name="TestFunc"]/wsdl:input/soap:body[@use="literal" and @namespace="'
                . $this->defaultServiceUri . '"]',
            'Missing operation input body definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . 'wsdl:operation[@name="TestFunc"]/wsdl:output/soap:body[@use="literal" and @namespace="'
                . $this->defaultServiceUri . '"]',
            'Missing operation input body definition'
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:service[@name="MyServiceService"]/wsdl:port[@name="MyServicePort"'
                . ' and @binding="tns:MyServiceBinding"]/soap:address[@location="'
                . $this->defaultServiceUri . '"]',
            'Missing service port definition'
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="TestFuncIn"]/wsdl:part[@name="parameters" and @element="tns:TestFunc"]',
            'Missing test testFunc input message definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="TestFuncOut"]/wsdl:part[@name="parameters" and @element="tns:TestFuncResponse"]',
            'Missing test testFunc input message definition'
        );


        $this->assertValidWSDL($this->dom);
        $this->testDocumentNodes();
    }

    /**
     * @group ZF-5072
     */
    public function testAddFunctionSimpleInReturnNameCompabilityMode()
    {
        $this->server->addFunction('\ZendTest\Soap\TestAsset\TestFunc');
        $this->bindWsdl($this->server->generate());

        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema[@targetNamespace="'
            . $this->defaultServiceUri . '"]',
            'Missing service port definition'
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc"]',
            'Missing service port definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc"]/'
                . 'wsdl:documentation',
            'Missing service port definition documentation'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc"]/'
                . 'wsdl:input',
            'Missing service port definition input message'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc"]/'
                . 'wsdl:output',
            'Missing service port definition input message'
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . 'wsdl:operation[@name="TestFunc"]',
            'Missing service binding definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . 'soap:binding[@style="rpc" and @transport="http://schemas.xmlsoap.org/soap/http"]',
            'Missing service binding transport definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . 'wsdl:operation[@name="TestFunc"]/soap:operation[@soapAction="'
                . $this->defaultServiceUri . '#TestFunc"]',
            'Missing service operation action definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . 'wsdl:operation[@name="TestFunc"]/wsdl:input/soap:body[@use="encoded"'
                . ' and @encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" '
                . 'and @namespace="http://localhost/MyService.php"]',
            'Missing operation input body definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . 'wsdl:operation[@name="TestFunc"]/wsdl:output/soap:body[@use="encoded"'
                . 'and @encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" and'
                . '@namespace="http://localhost/MyService.php"]',
            'Missing operation input body definition'
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:service[@name="MyServiceService"]/wsdl:port[@name="MyServicePort"'
                . 'and @binding="tns:MyServiceBinding"]/soap:address[@location="'
                . $this->defaultServiceUri . '"]',
            'Missing service port definition'
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="TestFuncIn"]/wsdl:part[@name="who" and @type="xsd:string"]',
            'Missing test testFunc input message definition'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="TestFuncOut"]/wsdl:part[@name="return" and @type="xsd:string"]',
            'Missing test testFunc input message definition'
        );


        $this->assertValidWSDL($this->dom);
        $this->testDocumentNodes();
    }

    public function testAddFunctionMultiple()
    {
        $this->server->addFunction('\ZendTest\Soap\TestAsset\TestFunc');
        $this->server->addFunction('\ZendTest\Soap\TestAsset\TestFunc2');
        $this->server->addFunction('\ZendTest\Soap\TestAsset\TestFunc3');
        $this->server->addFunction('\ZendTest\Soap\TestAsset\TestFunc4');
        $this->server->addFunction('\ZendTest\Soap\TestAsset\TestFunc5');
        $this->server->addFunction('\ZendTest\Soap\TestAsset\TestFunc6');
        $this->server->addFunction('\ZendTest\Soap\TestAsset\TestFunc7');
        $this->server->addFunction('\ZendTest\Soap\TestAsset\TestFunc9');

        $this->bindWsdl($this->server->generate());


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema[@targetNamespace="'
            . $this->defaultServiceUri . '"]',
            'Missing service port definition'
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                . 'soap:binding[@style="rpc" and @transport="http://schemas.xmlsoap.org/soap/http"]',
            'Missing service port definition'
        );


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:service[@name="MyServiceService"]/wsdl:port[@name="MyServicePort"'
                . ' and @binding="tns:MyServiceBinding"]/soap:address[@location="'
                . $this->defaultServiceUri . '"]',
            'Missing service port definition'
        );

        foreach (['', 2, 3, 4, 5, 6, 7, 9] as $i) {
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc'
                    . $i . '"]',
                'Missing service port definition for TestFunc' . $i . ''
            );
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc'
                    . $i . '"]/wsdl:documentation',
                'Missing service port definition documentation for TestFunc'
                    . $i . ''
            );
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc'
                    . $i . '"]/wsdl:input[@message="tns:TestFunc' . $i . 'In"]',
                'Missing service port definition input message for TestFunc'
                    . $i . ''
            );


            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                    . 'wsdl:operation[@name="TestFunc' . $i . '"]/soap:operation[@soapAction="'
                    . $this->defaultServiceUri . '#TestFunc' . $i . '"]',
                'Missing service operation action definition'
            );
            $this->assertSpecificNodeNumberInXPath(
                1,
                '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]/'
                    . 'wsdl:operation[@name="TestFunc' . $i . '"]/wsdl:input/soap:body'
                    . '[@use="encoded" and @encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"'
                    . ' and @namespace="' . $this->defaultServiceUri . '"]',
                'Missing operation input for TestFunc' . $i . ' body definition'
            );


            if ($i != 2) {
                $this->assertSpecificNodeNumberInXPath(
                    1,
                    '//wsdl:portType[@name="MyServicePort"]/wsdl:operation[@name="TestFunc'
                        . $i . '"]/wsdl:output[@message="tns:TestFunc' . $i
                        . 'Out"]',
                    'Missing service port definition input message for TestFunc'
                        . $i . ''
                );


                $this->assertSpecificNodeNumberInXPath(
                    1,
                    '//wsdl:binding[@name="MyServiceBinding" and @type="tns:MyServicePort"]'
                        . '/wsdl:operation[@name="TestFunc'. $i . '"]/wsdl:output/soap:body'
                        . '[@use="encoded" and @encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"'
                        . ' and @namespace="' . $this->defaultServiceUri . '"]',
                    'Missing operation input for TestFunc' . $i
                        . ' body definition'
                );


                $this->assertSpecificNodeNumberInXPath(
                    1,
                    '//wsdl:message[@name="TestFunc' . $i . 'In"]',
                    'Missing test testFunc' . $i . ' input message definition'
                );
                $this->assertSpecificNodeNumberInXPath(
                    1,
                    '//wsdl:message[@name="TestFunc' . $i . 'Out"]',
                    'Missing test testFunc' . $i . ' input message definition'
                );
            }
        }


        $this->assertValidWSDL($this->dom);
        $this->testDocumentNodes();
    }

    /**
     * @group ZF-4117
     *
     * @dataProvider dataProviderValidUris
     */
    public function testChangeWsdlUriInConstructor($uri, $expectedUri)
    {
        $this->server->addFunction('\ZendTest\Soap\TestAsset\TestFunc');
        $this->server->setUri($uri);
        $this->bindWsdl($this->server->generate());


        $this->assertEquals(
            $expectedUri,
            $this->dom->documentElement->getAttribute('targetNamespace')
        );
        $this->assertNotContains(
            $this->defaultServiceUri,
            $this->dom->saveXML()
        );


        $this->assertValidWSDL($this->dom);
        $this->testDocumentNodes();
    }

    public function testSetNonStringNonZendUriUriThrowsException()
    {
        $server = new AutoDiscover();

        $this->setExpectedException(
            '\Zend\Soap\Exception\InvalidArgumentException',
            'Argument to \Zend\Soap\AutoDiscover::setUri should be string or \Zend\Uri\Uri instance.'
        );
        $server->setUri(["bogus"]);
    }

    /**
     * @group ZF-4117
     * @dataProvider dataProviderValidUris
     */
    public function testChangingWsdlUriAfterGenerationIsPossible(
        $uri,
        $expectedUri
    ) {
        $this->server->addFunction('\ZendTest\Soap\TestAsset\TestFunc');
        $wsdl = $this->server->generate();
        $wsdl->setUri($uri);

        $this->assertEquals(
            $expectedUri,
            $wsdl->toDomDocument()->documentElement->getAttribute(
                'targetNamespace'
            )
        );

        $this->assertValidWSDL($wsdl->toDomDocument());
        $this->testDocumentNodes();
    }

    /**
     * @return array
     */
    public function dataProviderValidUris()
    {
        return [
            ['http://example.com/service.php',
                  'http://example.com/service.php'],
            ['http://example.com/?a=b&amp;b=c',
                  'http://example.com/?a=b&amp;b=c'],
            ['http://example.com/?a=b&b=c',
                  'http://example.com/?a=b&amp;b=c'],
            ['urn:uuid:550e8400-e29b-41d4-a716-446655440000',
                  'urn:uuid:550e8400-e29b-41d4-a716-446655440000'],
            ['urn:acme:servicenamespace', 'urn:acme:servicenamespace'],
            [new Uri('http://example.com/service.php'),
                  'http://example.com/service.php'],
            [new Uri('http://example.com/?a=b&amp;b=c'),
                  'http://example.com/?a=b&amp;b=c'],
            [new Uri('http://example.com/?a=b&b=c'),
                  'http://example.com/?a=b&amp;b=c'],
        ];
    }

    /**
     * @group ZF-4688
     * @group ZF-4125
     *
     */
    public function testUsingClassWithMethodsWithMultipleDefaultParameterValues()
    {
        $this->server->setClass(
            '\ZendTest\Soap\TestAsset\TestFixingMultiplePrototypes'
        );
        $this->bindWsdl($this->server->generate());


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="testFuncIn"]'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:message[@name="testFuncOut"]'
        );


        $this->assertValidWSDL($this->dom);
        $this->testDocumentNodes();
    }

    /**
     * @group ZF-4937
     */
    public function testComplexTypesThatAreUsedMultipleTimesAreRecoginzedOnce()
    {
        $this->server->setComplexTypeStrategy(
            new \Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex
        );
        $this->server->setClass(
            '\ZendTest\Soap\TestAsset\AutoDiscoverTestClass2'
        );
        $this->bindWsdl($this->server->generate());


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//xsd:attribute[@wsdl:arrayType="tns:AutoDiscoverTestClass1[]"]',
            'Definition of TestClass1 has to occour once.'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//xsd:complexType[@name="AutoDiscoverTestClass1"]',
            'AutoDiscoverTestClass1 has to be defined once.'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//xsd:complexType[@name="ArrayOfAutoDiscoverTestClass1"]',
            'AutoDiscoverTestClass1 should be defined once.'
        );
        $nodes = $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:part[@name="test" and @type="tns:AutoDiscoverTestClass1"]',
            'AutoDiscoverTestClass1 appears once or more than once in the message parts section.'
        );
        $this->assertGreaterThanOrEqual(
            1,
            $nodes->length,
            'AutoDiscoverTestClass1 appears once or more than once in the message parts section.'
        );


        $this->assertValidWSDL($this->dom);
        $this->testDocumentNodes();
    }

    /**
     * @group ZF-5604
     */
    public function testReturnSameArrayOfObjectsResponseOnDifferentMethodsWhenArrayComplex()
    {
        $this->server->setComplexTypeStrategy(
            new \Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex
        );
        $this->server->setClass('\ZendTest\Soap\TestAsset\MyService');
        $this->bindWsdl($this->server->generate());


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//xsd:complexType[@name="ArrayOfMyResponse"]'
        );
        $this->assertSpecificNodeNumberInXPath(
            0,
            '//wsdl:part[@type="tns:My_Response[]"]'
        );


        $this->assertValidWSDL($this->dom);
        $this->testDocumentNodes();
    }

    /**
     * @group ZF-5430
     */
    public function testReturnSameArrayOfObjectsResponseOnDifferentMethodsWhenArraySequence()
    {
        $this->server->setComplexTypeStrategy(
            new \Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence
        );
        $this->server->setClass('\ZendTest\Soap\TestAsset\MyServiceSequence');
        $this->bindWsdl($this->server->generate());


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//xsd:complexType[@name="ArrayOfString"]'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//xsd:complexType[@name="ArrayOfArrayOfString"]'
        );
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//xsd:complexType[@name="ArrayOfArrayOfArrayOfString"]'
        );


        $this->assertNotContains('tns:string[]', $this->dom->saveXML());


        $this->assertValidWSDL($this->dom);
        $this->testDocumentNodes();
    }

    /**
     * @group ZF-6689
     */
    public function testNoReturnIsOneWayCallInSetClass()
    {
        $this->server->setClass('\ZendTest\Soap\TestAsset\NoReturnType');
        $this->bindWsdl($this->server->generate());


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:portType/wsdl:operation[@name="pushOneWay"]/wsdl:input'
        );
        $this->assertSpecificNodeNumberInXPath(
            0,
            '//wsdl:portType/wsdl:operation[@name="pushOneWay"]/wsdl:output'
        );


        $this->assertValidWSDL($this->dom);
        $this->testDocumentNodes();
    }

    /**
     * @group ZF-6689
     */
    public function testNoReturnIsOneWayCallInAddFunction()
    {
        $this->server->addFunction('\ZendTest\Soap\TestAsset\OneWay');
        $this->bindWsdl($this->server->generate());


        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:portType/wsdl:operation[@name="OneWay"]/wsdl:input'
        );
        $this->assertSpecificNodeNumberInXPath(
            0,
            '//wsdl:portType/wsdl:operation[@name="OneWay"]/wsdl:output'
        );


        $this->assertValidWSDL($this->dom);
        $this->testDocumentNodes();
    }

    /**
     * @group ZF-8948
     * @group ZF-5766
     */
    public function testRecursiveWsdlDependencies()
    {
        $this->server->setComplexTypeStrategy(
            new \Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence
        );
        $this->server->setClass('\ZendTest\Soap\TestAsset\Recursion');

        $this->bindWsdl($this->server->generate());


        //  <types>
        //      <xsd:schema targetNamespace="http://localhost/my_script.php">
        //          <xsd:complexType name="Zend_Soap_AutoDiscover_Recursion">
        //              <xsd:all>
        //                  <xsd:element name="recursion" type="tns:Zend_Soap_AutoDiscover_Recursion"/>
        $this->assertSpecificNodeNumberInXPath(
            1,
            '//wsdl:types/xsd:schema/xsd:complexType[@name="Recursion"]/xsd:all/'
                . 'xsd:element[@name="recursion" and @type="tns:Recursion"]'
        );


        $this->assertValidWSDL($this->dom);
        $this->testDocumentNodes();
    }

    /**
     * @runInSeparateProcess
     */
    public function testHandle()
    {
        $scriptUri = 'http://localhost/MyService.php';

        $this->server->setClass('\ZendTest\Soap\TestAsset\Test');

        ob_start();
        $this->server->handle();
        $actualWsdl = ob_get_clean();
        $this->assertNotEmpty($actualWsdl, "WSDL content was not outputted.");
        $this->assertContains($scriptUri, $actualWsdl, "Script URL was not found in WSDL content.");
    }

    /**
     * @param int    $n
     * @param string $xpath
     * @param string $msg
     *
     * @return \DOMNodeList
     */
    public function assertSpecificNodeNumberInXPath($n, $xpath, $msg = null)
    {
        $nodes = $this->xpath->query($xpath);
        if (!($nodes instanceof \DOMNodeList)) {
            $this->fail('Nodes not found. Invalid XPath expression ?');
        }
        $this->assertEquals($n, $nodes->length, $msg . "\nXPath: " . $xpath);

        return $nodes;
    }

    public function assertAttributesOfNodes($attributes, $nodeList)
    {
        $c = count($attributes);

        $keys = array_keys($attributes);

        foreach ($nodeList as $node) {
            for ($i = 0; $i < $c; $i++) {
                $this->assertEquals(
                    $attributes[$keys[$i]],
                    $node->getAttribute($keys[$i]),
                    'Invalid attribute value.'
                );
            }
        }
    }

    /**
     * @dataProvider dataProviderValidUris
     */
    public function testChangeTargetNamespace($uri, $expectedUri)
    {
        $this->server->setTargetNamespace($uri);
        $this->bindWsdl($this->server->generate());

        $this->assertEquals(
            $expectedUri,
            $this->dom->documentElement->getAttribute('targetNamespace')
        );

        $this->assertNotEquals(
            $this->defaultServiceUri,
            $this->dom->documentElement->getAttribute('targetNamespace')
        );

        $this->assertValidWSDL($this->dom);

        $this->testDocumentNodes();
    }
}
