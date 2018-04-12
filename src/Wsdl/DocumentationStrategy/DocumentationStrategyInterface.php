<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Soap\Wsdl\DocumentationStrategy;

use ReflectionClass;
use ReflectionProperty;

/**
 * Implement this interface to provide contents for <xsd:documentation> elements on complex types
 */
interface DocumentationStrategyInterface
{
    /**
     * Returns documentation for complex type property
     *
     * @param ReflectionProperty $property
     *
     * @return string
     */
    public function getPropertyDocumentation(ReflectionProperty $property);

    /**
     * Returns documentation for complex type
     *
     * @param ReflectionClass $class
     *
     * @return string
     */
    public function getComplexTypeDocumentation(ReflectionClass $class);
}
