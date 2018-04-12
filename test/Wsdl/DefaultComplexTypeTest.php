<?php
/**
 * @see       https://github.com/zendframework/zend-soap for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-soap/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Soap\Wsdl;

use Prophecy\Argument;
use ReflectionClass;
use ReflectionProperty;
use Zend\Soap\Wsdl\ComplexTypeStrategy\DefaultComplexType;
use Zend\Soap\Wsdl\DocumentationStrategy\DocumentationStrategyInterface;
use ZendTest\Soap\TestAsset\PublicPrivateProtected;
use ZendTest\Soap\TestAsset\WsdlTestClass;
use ZendTest\Soap\WsdlTestHelper;

/**
 * @covers \Zend\Soap\Wsdl\ComplexTypeStrategy\DefaultComplexType
 */
class DefaultComplexTypeTest extends WsdlTestHelper
{
    /**
     * @var DefaultComplexType
     */
    protected $strategy;

    public function setUp()
    {
        $this->strategy = new DefaultComplexType();

        parent::setUp();
    }

    /**
     * @group ZF-5944
     */
    public function testOnlyPublicPropertiesAreDiscoveredByStrategy()
    {
        $this->strategy->addComplexType('ZendTest\Soap\TestAsset\PublicPrivateProtected');

        $nodes = $this->xpath->query('//xsd:element[@name="'.(PublicPrivateProtected::PROTECTED_VAR_NAME).'"]');
        $this->assertEquals(0, $nodes->length, 'Document should not contain protected fields');

        $nodes = $this->xpath->query('//xsd:element[@name="'.(PublicPrivateProtected::PRIVATE_VAR_NAME).'"]');
        $this->assertEquals(0, $nodes->length, 'Document should not contain private fields');

        $this->documentNodesTest();
    }

    public function testDoubleClassesAreDiscoveredByStrategy()
    {
        $this->strategy->addComplexType('ZendTest\Soap\TestAsset\WsdlTestClass');
        $this->strategy->addComplexType('\ZendTest\Soap\TestAsset\WsdlTestClass');

        $nodes = $this->xpath->query('//xsd:complexType[@name="WsdlTestClass"]');
        $this->assertEquals(1, $nodes->length);

        $this->documentNodesTest();
    }

    public function testDocumentationStrategyCalled()
    {
        $documentation = $this->prophesize(DocumentationStrategyInterface::class);
        $documentation->getPropertyDocumentation(Argument::type(ReflectionProperty::class))
            ->shouldBeCalledTimes(2)
            ->willReturn('Property');
        $documentation->getComplexTypeDocumentation(Argument::type(ReflectionClass::class))
            ->shouldBeCalledTimes(1)
            ->willReturn('Complex type');
        $this->strategy->setDocumentationStrategy($documentation->reveal());
        $this->strategy->addComplexType(WsdlTestClass::class);
    }
}
