<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Soap\Wsdl\ComplexTypeStrategy;

use ReflectionClass;
use Zend\Soap\Exception;
use Zend\Soap\Wsdl;

class DefaultComplexType extends AbstractComplexTypeStrategy
{
    /**
     * @var array
     */
    protected $_simpleTypeList = array();

    /**
     * Add a complex type by recursively using all the class properties fetched via Reflection.
     *
     * @param  string $type Name of the class to be specified
     * @return string XSD Type for the given PHP type
     * @throws Exception\InvalidArgumentException if class does not exist
     */
    public function addComplexType($type)
    {
        if (!class_exists($type)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Cannot add a complex type %s that is not an object or where '
                . 'class could not be found in "DefaultComplexType" strategy.',
                $type
            ));
        }

        $class   = new ReflectionClass($type);
        $phpType = $class->getName();

        if (($soapType = $this->scanRegisteredTypes($phpType)) !== null) {
            return $soapType;
        }

        $dom = $this->getContext()->toDomDocument();
        $soapTypeName = $this->getContext()->translateType($phpType);
        $soapType     = Wsdl::TYPES_NS . ':' . $soapTypeName;

        // Register type here to avoid recursion
        $this->getContext()->addType($phpType, $soapType);

        $defaultProperties = $class->getDefaultProperties();

        $complexType = $dom->createElementNS(Wsdl::XSD_NS_URI, 'complexType');
        $complexType->setAttribute('name', $soapTypeName);

        $all = $dom->createElementNS(Wsdl::XSD_NS_URI, 'all');

        /*
         * If, in fact, we treat a simple type, we avoid rest of treatment
         * of complex type and focuse on add a simple type to the document.
         */
        if (preg_match_all('/@xsd\s+simpleType([ \t\f]+(.*))?/m', $class->getDocComment(), $matches)) {
            $this->_addSimpleType($soapType, $class, $matches[1][0]);
            return "$soapType";
        }

        /*
         * In some case, should want to keep direct declaration as complexType
         * and not wrapping it inside of element because we don't need
         * to make reference on it and/or add restriction like minOccurs/maxOccurs.
         * It's the case at least for the Response element which need a type in it's
         * definition using class specified in return of the Doc in function added to
         * webservice.
         */
        $wantType = true;
        if (preg_match_all('/@xsd\s+element([ \t\f]+(.*))?/m', $class->getDocComment(), $matches)) {
            $wantType = false;
        } else if (preg_match_all('/@xsd\s+complexType([ \t\f]+(.*))?/m', $class->getDocComment(), $matches)) {
        }

        $complexType = $dom->createElement('xsd:complexType');

        /*
         * Skipp element level creation if we don't want to
         * wrap the complexType in element.
         */
        if ($wantType) {
            $complexType->setAttribute('name', $type);
            $rootTypeElement = $complexType;
        } else {
            $rootTypeElement = $dom->createElement('xsd:element');
            $rootTypeElement->setAttribute('name', $type);
        }

        //$matches[1][0]=substr($matches[1][0], 0, -1);
        $matches[1][0] = isset($matches[1][0]) ? $matches[1][0] : "";
        $this->_addAttributes($matches[1][0], $rootTypeElement);

        /*
         * Inside of complexType, choose the wished container.
         * Like sequence or choice or all, depending on restriction
         * you want on each sub-element. If nothing specified, keep all.
         */

        if (preg_match_all('/@xsd\s+([^\s]+)([ \t\f]+(.*))?/m', $class->getDocComment(), $matches)) {
            foreach($matches[1] as $key => $value) {
                switch ($matches[1][$key]) {
                    case 'element' :
                    case 'complexType' :
                        break;
                    default :
                        $all = $dom->createElement('xsd:' . $matches[1][$key]);
                        $this->_addAttributes($matches[2][$key], $all);
                        break;
                }
            }
            if(!isset($all)) {
                $all = $dom->createElement('xsd:all');
            }
        } else {
            $all = $dom->createElement('xsd:all');
        }

        /*
         * Keep copy of initial $all position in the tree for
         * easy backup tree point later.
         */
        $initAll = $all;

        /*
         * Keep instant copy of just before new $all position for
         * easy append of new element.
         */
        $oldAll = $all;

        /*
         * Start looking at each public property of a class.
         * Add ref in case of complexType exist.
         * Add element in case of base type.
         * Add wrapper at any point if we find sequence, choice or all.
         * Add restriction on each element if specified in array in Doc.
         */
        foreach ($class->getProperties() as $property) {
            if ($property->isPublic() && preg_match_all('/@([^\s]+)\s+([^\s]+)([ \t\f]+(.*))?/m', $property->getDocComment(), $matches)) {
                foreach ($matches[0] as $key => $someValue) {
                    switch ($matches[1][$key]) {
                        case 'var' :
                            $element = $dom->createElement('xsd:element');
                            if (class_exists(trim($matches[2][$key])) && $this->_isElement(trim($matches[2][$key])) && !$this->_isSimpleType(trim($matches[2][$key]))) {
                                /*
                                 * Known issue: Do not use ref style for now, it can't work
                                 * with how is generated the soap response currently.
                                 * With reference, should add the namespace in tag name of the
                                 * element in the soapresponse. So it will NOT pass the validation
                                 * currently. You'll get an unresolved symbol.
                                 */
                                $element->setAttribute('ref', $this->getContext()->getType(trim($matches[2][$key])));
                            } else {
                                $element->setAttribute('name', $property->getName());
                                $element->setAttribute('type', $this->getContext()->getType(trim($matches[2][$key])));
                            }
                            $this->_addAttributes($matches[3][$key], $element);
                            // If the default value is null, then this property is nillable.
                            if ($defaultProperties[$property->getName()] === null) {
                                $element->setAttribute('nillable', 'true');
                            }
                            $all->appendChild($element);
                            break;
                        case 'xsd' :
                            if (preg_match_all('/@xsd\s+([^\s]+)\s+([^\s]+)([ \t\f]+(.*))?/m', $matches[0][$key], $subMatches)) {
                                switch ($subMatches[1][0]) {
                                    default :
                                        if ($subMatches[2][0] == 'start') {
                                            $all = $dom->createElement('xsd:' . $subMatches[1][0]);
                                            $this->_addAttributes($subMatches[3][0], $all);
                                            $oldAll->appendChild($all);
                                            $oldAll = $all;
                                        } else if ($subMatches[2][0] == 'end') {
                                            $all = $all->parentNode;
                                            $oldAll = $oldAll->parentNode;
                                        }
                                        break;
                                }
                            }
                            break;
                    }
                }
            }
        }

        /*
         * Switch back to initial point
         */
        $all = $initAll;

        /*
         * Skipp append of first element wrapper if we just want a complexType definition
         */
        if ($wantType) {
            $complexType->appendChild($all);
        } else {
            $complexType->appendChild($all);
            $rootTypeElement->appendChild($complexType);
        }

        $this->getContext()->getSchema()->appendChild($rootTypeElement);
        $this->getContext()->addType($type, $soapType);

        return "tns:$type";
    }


    /**
     * Add attributes to any element found via Reflection.
     *
     * @param   string  $attributes List of attributes to add to element
     * @param   \DOMElement $element Element to add attributes on
     * @return  bool    true on success or false on failure
     */
    protected function _addAttributes($attributes, $element)
    {
        if ($attributes != '') {
            $attArgs = null;
            eval('$attArgs = ' . $attributes . ';');
            if (is_array($attArgs)) {
                foreach ($attArgs as $attribut => $value) {
                    if ($attribut == 'base') {
                        $valueDetails=explode(':', $value);
                        if ($valueDetails[0] == 'tns' && class_exists($valueDetails[1])) {
                            $class = new ReflectionClass($valueDetails[1]);

                            if (preg_match_all('/@xsd\s+simpleType([ \t\f]+(.*))?/m', $class->getDocComment(), $subMatches)) {
                                $this->_addSimpleType($valueDetails[1], $class, $subMatches[1][0]);
                                $element->setAttribute($attribut, $value);
                            }
                        } else if ($valueDetails[0] == 'xsd') {
                            $element->setAttribute($attribut, $value);
                        }
                    } else {
                        $element->setAttribute($attribut, $value);
                    }
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Add simpleType
     *
     * @param   string  $name Name of simpleType
     * @param   \ReflectionClass  $classReflection Result of reflection on class definition of simpleType
     * @param   string  $attributs Attributs to add to simple type tag
     * @return  bool    true on success or false on failure
     */
    protected function _addSimpleType($name, $classReflection, $attributs)
    {
        if(in_array($name, $this->_simpleTypeList))
            return true;

        $this->_simpleTypeList[] = $name;

        $dom = $this->getContext()->toDomDocument();

        $rootTypeElement = $dom->createElement('xsd:simpleType');
        $rootTypeElement->setAttribute('name', $name);

        if ($attributs != '')
            $this->_addAttributes($attributs, $rootTypeElement);


        $list = array();
        if (preg_match_all('/@xsd\s+([^\s]+)([ \t\f]+(.*))?/m', $classReflection->getDocComment(), $firstMatches)) {
            foreach ($firstMatches[0] as $firstKey => $someValue) {
                switch ($firstMatches[1][$firstKey]) {
                    case 'restriction' :
                        $all = $dom->createElement('xsd:restriction');
                        $this->_addAttributes($firstMatches[2][$firstKey], $all);
                        foreach ($classReflection->getProperties() as $property) {
                            if ($property->isPublic()
                                && preg_match_all('/@xsd\s+([^\s]+)([ \t\f]+(.*))?/m', $property->getDocComment(), $matches)) {
                                foreach ($matches[0] as $key => $someValue) {
                                    switch ($matches[1][$key]) {
                                        default :
                                            $enum = $dom->createElement('xsd:' . $matches[1][$key]);
                                            $this->_addAttributes($matches[2][$key], $enum);
                                            $all->appendChild($enum);
                                            break;
                                    }
                                }
                            }
                        }
                        break;
                    case 'union' :
                        $all = $dom->createElement('xsd:union');
                        $this->_addAttributes($firstMatches[2][$firstKey], $all);
                        foreach ($classReflection->getProperties() as $property) {
                            if ($property->isPublic() && preg_match_all('/@xsd\s+simpleType([ \t\f]+(.*))?/m', $property->getDocComment(), $matches)) {
                                foreach ($matches[0] as $key => $someValue) {
                                    if (class_exists($matches[1][$key])) {
                                        $list[] = 'tns:' . $matches[1][$key];
                                        $class = new ReflectionClass($matches[1][$key]);
                                        if (preg_match_all('/@xsd\s+simpleType([ \t\f]+(.*))?/m', $class->getDocComment(), $subMatches)) {
                                            $this->_addSimpleType($matches[1][$key], $class, $subMatches[1][0]);
                                        }
                                    } else {
                                        $list[] = 'xsd:' . $matches[1][$key];
                                    }
                                }
                            }
                        }
                        if (count($list) > 0)
                            $all->setAttribute('memberTypes', implode(" ", $list));
                        break;
                    case 'list' :
                        $all = $dom->createElement('xsd:list');
                        $this->_addAttributes($firstMatches[2][$firstKey], $all);
                        foreach ($classReflection->getProperties() as $property) {
                            if ($property->isPublic() && preg_match_all('/@xsd\s+itemType([ \t\f]+(.*))?/m', $property->getDocComment(), $matches)) {
                                foreach ($matches[0] as $key => $someValue) {
                                    if (class_exists($matches[1][$key])) {
                                        $list[] = 'tns:' . $matches[1][$key];
                                        $class = new ReflectionClass($matches[1][$key]);
                                        if (preg_match_all('/@xsd\s+simpleType([ \t\f]+(.*))?/m', $class->getDocComment(), $subMatches)) {
                                            $this->_addSimpleType($matches[1][$key], $class, $subMatches[1][0]);
                                        }
                                    }
                                }
                            }
                        }
                        if (count($list) > 0)
                            $all->setAttribute('itemType', $list[0]);
                        break;
                }
            }
        } else {
            return false;
        }


        $phpType = $classReflection->getName();

        if (($soapType = $this->scanRegisteredTypes($phpType)) !== null) {
        } else {
            $soapTypeName = $this->getContext()->translateType($phpType);
            $soapType = Wsdl::TYPES_NS . ':' . $soapTypeName;
        }

        $rootTypeElement->appendChild($all);
        $this->getContext()->getSchema()->appendChild($rootTypeElement);
        $this->getContext()->addType($name, $soapType);
    }

    /**
     * Check if it is simpleType class
     *
     * @param   string  $name Name of simpleType
     * @return  bool    true on success or false on failure
     */
    protected function _isSimpleType($name)
    {
        $class = new ReflectionClass($name);
        if (preg_match_all('/@xsd[\s]+simpleType([ \t\f]+(.*))?/m', $class->getDocComment(), $matches)) {
            return true;
        }
        return false;
    }

    /**
     * Check if it is element or complexType
     *
     * @param   string  $name Name of simpleType
     * @return  bool    true on success or false on failure
     */
    protected function _isElement($name)
    {
        $class = new ReflectionClass($name);
        if (preg_match_all('/@xsd[\s]+element([ \t\f]+(.*))?/m', $class->getDocComment(), $matches)) {
            return true;
        }
        return false;
    }
}
