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

final class ReflectionDocumentation implements DocumentationStrategyInterface
{
    public function getPropertyDocumentation(ReflectionProperty $property)
    {
        return $this->parseDocComment($property->getDocComment());
    }

    public function getComplexTypeDocumentation(ReflectionClass $class)
    {
        return $this->parseDocComment($class->getDocComment());
    }

    private function parseDocComment(string $docComment)
    {
        $documentation = [];
        foreach (explode("\n", $docComment) as $i => $line) {
            if ($i == 0) {
                continue;
            }

            $line = trim(preg_replace('/\s*\*+/', '', $line));
            if (preg_match('/^(@[a-z]|\/)/i', $line)) {
                break;
            }

            // only include newlines if we've already got documentation
            if (! empty($documentation) || $line != '') {
                $documentation[] = $line;
            }
        }

        return join("\n", $documentation);
    }
}
