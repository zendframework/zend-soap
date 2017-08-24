<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Soap\Wsdl;

use Zend\Soap\Exception\InvalidArgumentException;
use Zend\Soap\Wsdl\ComplexTypeStrategy;
use Zend\Soap\Wsdl\ComplexTypeStrategy\AnyType;
use Zend\Soap\Wsdl\ComplexTypeStrategy\Composite;
use Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex;
use Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence;
use Zend\Soap\Wsdl\ComplexTypeStrategy\DefaultComplexType;
use ZendTest\Soap\WsdlTestHelper;

/**
 * @group      Zend_Soap
 * @group      Zend_Soap_Wsdl
 */
class CompositeStrategyTest extends WsdlTestHelper
{
    public function setUp()
    {
        // override parent setup because it is needed only in one method
    }

    public function testCompositeApiAddingStragiesToTypes()
    {
        $strategy = new Composite([], new ArrayOfTypeSequence);
        $strategy->connectTypeToStrategy('Book', new ArrayOfTypeComplex);

        $bookStrategy = $strategy->getStrategyOfType('Book');
        $cookieStrategy = $strategy->getStrategyOfType('Cookie');

        $this->assertInstanceOf('Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex', $bookStrategy);
        $this->assertInstanceOf('Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence', $cookieStrategy);
    }

    public function testConstructorTypeMapSyntax()
    {
        $typeMap = ['Book' => '\Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex'];

        $strategy = new ComplexTypeStrategy\Composite(
            $typeMap,
            new ArrayOfTypeSequence
        );

        $bookStrategy = $strategy->getStrategyOfType('Book');
        $cookieStrategy = $strategy->getStrategyOfType('Cookie');

        $this->assertInstanceOf('Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex', $bookStrategy);
        $this->assertInstanceOf('Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence', $cookieStrategy);
    }

    public function testCompositeThrowsExceptionOnInvalidType()
    {
        $strategy = new ComplexTypeStrategy\Composite();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid type given to Composite Type Map');
        $strategy->connectTypeToStrategy([], 'strategy');
    }

    public function testCompositeThrowsExceptionOnInvalidStrategy()
    {
        $strategy = new ComplexTypeStrategy\Composite([], 'invalid');
        $strategy->connectTypeToStrategy('Book', 'strategy');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Strategy for Complex Type "Book" is not a valid strategy');
        $strategy->getStrategyOfType('Book');
    }

    public function testCompositeThrowsExceptionOnInvalidStrategyPart2()
    {
        $strategy = new ComplexTypeStrategy\Composite([], 'invalid');
        $strategy->connectTypeToStrategy('Book', 'strategy');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Default Strategy for Complex Types is not a valid strategy object');
        $strategy->getStrategyOfType('Anything');
    }

    public function testCompositeDelegatesAddingComplexTypesToSubStrategies()
    {
        $this->strategy = new ComplexTypeStrategy\Composite([], new AnyType);
        $this->strategy->connectTypeToStrategy(
            '\ZendTest\Soap\TestAsset\Book',
            new ArrayOfTypeComplex
        );
        $this->strategy->connectTypeToStrategy(
            '\ZendTest\Soap\TestAsset\Cookie',
            new DefaultComplexType
        );

        parent::setUp();

        $this->assertEquals('tns:Book', $this->strategy->addComplexType('\ZendTest\Soap\TestAsset\Book'));
        $this->assertEquals('tns:Cookie', $this->strategy->addComplexType('\ZendTest\Soap\TestAsset\Cookie'));
        $this->assertEquals('xsd:anyType', $this->strategy->addComplexType('\ZendTest\Soap\TestAsset\Anything'));

        $this->documentNodesTest();
    }

    public function testCompositeRequiresContextForAddingComplexTypesOtherwiseThrowsException()
    {
        $strategy = new ComplexTypeStrategy\Composite();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add complex type "Test"');
        $strategy->addComplexType('Test');
    }

    public function testGetDefaultStrategy()
    {
        $strategyClass =  'Zend\Soap\Wsdl\ComplexTypeStrategy\AnyType';

        $strategy = new Composite([], $strategyClass);

        $this->assertEquals($strategyClass, get_class($strategy->getDefaultStrategy()));
    }
}
