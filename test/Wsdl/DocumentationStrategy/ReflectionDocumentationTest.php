<?php
/**
 * @see       https://github.com/zendframework/zend-soap for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-soap/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Soap\Wsdl\DocumentationStrategy;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zend\Soap\Wsdl\DocumentationStrategy\ReflectionDocumentation;
use ZendTest\Soap\TestAsset\PropertyDocumentationTestClass;
use ZendTest\Soap\TestAsset\WsdlTestClass;

class ReflectionDocumentationTest extends TestCase
{
    /**
     * @var ReflectionDocumentation
     */
    private $documentation;

    protected function setUp()
    {
        $this->documentation = new ReflectionDocumentation();
    }

    public function testGetPropertyDocumentationParsesDocComment()
    {
        $class = new PropertyDocumentationTestClass();
        $reflection = new ReflectionClass($class);
        $actual = $this->documentation->getPropertyDocumentation($reflection->getProperty('withoutType'));
        $this->assertEquals('Property documentation', $actual);
    }

    public function testGetPropertyDocumentationSkipsAnnotations()
    {
        $class = new PropertyDocumentationTestClass();
        $reflection = new ReflectionClass($class);
        $actual = $this->documentation->getPropertyDocumentation($reflection->getProperty('withType'));
        $this->assertEquals('Property documentation', $actual);
    }

    public function testGetPropertyDocumentationReturnsEmptyString()
    {
        $class = new PropertyDocumentationTestClass();
        $reflection = new ReflectionClass($class);
        $actual = $this->documentation->getPropertyDocumentation($reflection->getProperty('noDoc'));
        $this->assertEquals('', $actual);
    }

    public function getGetComplexTypeDocumentationParsesDocComment()
    {
        $reflection = new ReflectionClass(new WsdlTestClass());
        $actual = $this->documentation->getComplexTypeDocumentation($reflection);
        $this->assertEquals('Test class', $actual);
    }
}
