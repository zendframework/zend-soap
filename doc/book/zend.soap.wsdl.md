# WSDL Accessor

> ### Note
`Zend\Soap\Wsdl` class is used by `Zend\Soap\Server` component internally to operate with WSDL
documents. Nevertheless, you could also use functionality provided by this class for your own needs.
The `Zend\Soap\Wsdl` package contains both a parser and a builder of WSDL documents.
If you don't plan to do this, you can skip this documentation section.

## Zend\\Soap\\Wsdl constructor

`Zend\Soap\Wsdl` constructor takes three parameters:

* `$name` - name of the Web Service being described.
* `$uri` - *URI* where the WSDL will be available (could also be a reference to the file in the
filesystem.)
* `$strategy` - optional flag used to identify the strategy for complex types (objects) detection.
To read more on complex type detection strategies go to the section: \[Add complex
types\](zend.soap.wsdl.types.add\_complex).
* `$classMap` - Optional array of class name translations from PHP Type (key) to WSDL type (value).

## addMessage() method

`addMessage($name, $parts)` method adds new message description to the WSDL document
(/definitions/message element).

Each message correspond to methods in terms of `Zend\Soap\Server` and `Zend\Soap\Client`
functionality.

`$name` parameter represents the message name.

`$parts` parameter is an array of message parts which describes *SOAP* call parameters. It's an
associative array: 'part name' (SOAP call parameter name) =&gt; 'part type'.

Type mapping management is performed using `addTypes()`, `addTypes()` and `addComplexType()` methods
(see below).

> ### Note
Messages parts can use either 'element' or 'type' attribute for typing (see
<http://www.w3.org/TR/wsdl#_messages).
'element' attribute must refer to a corresponding element of data type definition. 'type' attribute
refers to a corresponding complexType entry.
All standard XSD types have both 'element' and 'complexType' definitions (see
<http://schemas.xmlsoap.org/soap/encoding/).
All non-standard types, which may be added using `Zend\Soap\Wsdl::addComplexType()` method, are
described using 'complexType' node of '/definitions/types/schema/' section of WSDL document.
So `addMessage()` method always uses 'type' attribute to describe types.

## addPortType() method

`addPortType($name)` method adds new port type to the WSDL document (/definitions/portType) with the
specified port type name.

It joins a set of Web Service methods defined in terms of `Zend\Soap\Server` implementation.

See <http://www.w3.org/TR/wsdl#_porttypes> for the details.

## addPortOperation() method

`addPortOperation($portType, $name, $input = false, $output = false, $fault = false)` method adds
new port operation to the specified port type of the WSDL document
(/definitions/portType/operation).

Each port operation corresponds to a class method (if Web Service is based on a class) or function
(if Web Service is based on a set of methods) in terms of `Zend\Soap\Server` implementation.

It also adds corresponding port operation messages depending on specified `$input`, `$output` and
`$fault` parameters.

> ### Note
`Zend\Soap\Server` component generates two messages for each port operation while describing service
based on `Zend\Soap\Server` class:
* input message with name *$methodName . 'Request'*.
* output message with name *$methodName . 'Response'*.

See <http://www.w3.org/TR/wsdl#_request-response> for the details.

## addBinding() method

`addBinding($name, $portType)` method adds new binding to the WSDL document (/definitions/binding).

'binding' WSDL document node defines message format and protocol details for operations and messages
defined by a particular portType (see <http://www.w3.org/TR/wsdl#_bindings>).

The method creates binding node and returns it. Then it may be used to fill with actual data.

`Zend\Soap\Server` implementation uses *$serviceName . 'Binding'* name for 'binding' element of WSDL
document.

## addBindingOperation() method

`addBindingOperation($binding, $name, $input = false, $output = false, $fault = false)` method adds
an operation to a binding element (/definitions/binding/operation) with the specified name.

It takes an *XML\_Tree\_Node* object returned by `addBinding()` as an input (`$binding` parameter)
to add 'operation' element with input/output/false entries depending on specified parameters

`Zend\Soap\Server` implementation adds corresponding binding entry for each Web Service method with
input and output entries defining 'soap:body' element as '&lt;soap:body use="encoded"
encodingStyle="<http://schemas.xmlsoap.org/soap/encoding/%22/>&gt;

See <http://www.w3.org/TR/wsdl#_bindings> for the details.

## addSoapBinding() method

`addSoapBinding($binding, $style = 'document', $transport = 'http://schemas.xmlsoap.org/soap/http')`
method adds *SOAP* binding ('soap:binding') entry to the binding element (which is already linked to
some port type) with the specified style and transport (Zend\\Soap\\Server implementation uses RPC
style over *HTTP*).

'/definitions/binding/soap:binding' element is used to signify that the binding is bound to the
*SOAP* protocol format.

See <http://www.w3.org/TR/wsdl#_bindings> for the details.

## addSoapOperation() method

`addSoapOperation($binding, $soap_action)` method adds *SOAP* operation ('soap:operation') entry to
the binding element with the specified action. 'style' attribute of the 'soap:operation' element is
not used since programming model (RPC-oriented or document-oriented) may be using `addSoapBinding()`
method

'soapAction' attribute of '/definitions/binding/soap:operation' element specifies the value of the
*SOAP*Action header for this operation. This attribute is required for *SOAP* over *HTTP* and **must
not** be specified for other transports.

`Zend\Soap\Server` implementation uses *$serviceUri . '\#' . $methodName* for *SOAP* operation
action name.

See <http://www.w3.org/TR/wsdl#_soap:operation> for the details.

## addService() method

`addService($name, $port_name, $binding, $location)` method adds '/definitions/service' element to
the WSDL document with the specified Wed Service name, port name, binding, and location.

WSDL 1.1 allows to have several port types (sets of operations) per service. This ability is not
used by `Zend\Soap\Server` implementation and not supported by `Zend\Soap\Wsdl` class.

`Zend\Soap\Server` implementation uses:

* *$name . 'Service'* as a Web Service name,
* *$name . 'Port'* as a port type name,
* *'tns:' . $name . 'Binding'* [1] as binding name,
* script *URI* [2] as a service URI for Web Service definition using classes.

where `$name` is a class name for the Web Service definition mode using class and script name for
the Web Service definition mode using set of functions.

See <http://www.w3.org/TR/wsdl#_services> for the details.

## Type mapping

`ZendSoap` WSDL accessor implementation uses the following type mapping between *PHP* and *SOAP*
types:

* PHP strings &lt;-&gt; *xsd:string*.
* PHP integers &lt;-&gt; *xsd:int*.
* PHP floats and doubles &lt;-&gt; *xsd:float*.
* PHP booleans &lt;-&gt; *xsd:boolean*.
* PHP arrays &lt;-&gt; *soap-enc:Array*.
* PHP object &lt;-&gt; *xsd:struct*.
* *PHP* class &lt;-&gt; based on complex type strategy (See: \[this
section\](zend.soap.wsdl.types.add\_complex)) [3].
* PHP void &lt;-&gt; empty type.
* If type is not matched to any of these types by some reason, then *xsd:anyType* is used.

Where *xsd:* is "<http://www.w3.org/2001/XMLSchema>" namespace, *soap-enc:* is a
"<http://schemas.xmlsoap.org/soap/encoding/>" namespace, *tns:* is a "target namespace" for a
service.

### Retrieving type information

`getType($type)` method may be used to get mapping for a specified *PHP* type:

```php
...
$wsdl = new Zend\Soap\Wsdl('My_Web_Service', $myWebServiceUri);

...
$soapIntType = $wsdl->getType('int');

...
class MyClass
{
    ...
}
...
$soapMyClassType = $wsdl->getType('MyClass');
```

### Adding complex type information

`addComplexType($type)` method is used to add complex types (PHP classes) to a WSDL document.

It's automatically used by `getType()` method to add corresponding complex types of method
parameters or return types.

Its detection and building algorithm is based on the currently active detection strategy for complex
types. You can set the detection strategy either by specifying the class name as string or instance
of a `Zend\Soap\Wsdl\ComplexTypeStrategy` implementation as the third parameter of the constructor
or using the `setComplexTypeStrategy($strategy)` function of `Zend\Soap\Wsdl`. The following
detection strategies currently exist:

* Class `Zend\Soap\Wsdl\ComplexTypeStrategy\DefaultComplexType`: Enabled by default (when no third
constructor parameter is set). Iterates over the public attributes of a class type and registers
them as subtypes of the complex object type.
* Class `Zend\Soap\Wsdl\ComplexTypeStrategy\AnyType`: Casts all complex types into the simple XSD
type xsd:anyType. Be careful this shortcut for complex type detection can probably only be handled
successfully by weakly typed languages such as *PHP*.
* Class `Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence`: This strategy allows to specify
return parameters of the type: *int\[\]* or *string\[\]*. As of Zend Framework version 1.9 it can
handle both simple *PHP* types such as int, string, boolean, float as well as objects and arrays of
objects.
* Class `Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex`: This strategy allows to detect very
complex arrays of objects. Objects types are detected based on the
`Zend\Soap\Wsdl\Strategy\DefaultComplexType` and an array is wrapped around that definition.
* Class `Zend\Soap\Wsdl\ComplexTypeStrategy\Composite`: This strategy can combine all strategies by
connecting *PHP* Complex types (Classnames) to the desired strategy via the
`connectTypeToStrategy($type, $strategy)` method. A complete typemap can be given to the constructor
as an array with `$type`-&gt; `$strategy` pairs. The second parameter specifies the default strategy
that will be used if an unknown type is requested for adding. This parameter defaults to the
`Zend\Soap\Wsdl\Strategy\DefaultComplexType` strategy.

`addComplexType()` method creates '/definitions/types/xsd:schema/xsd:complexType' element for each
described complex type with name of the specified *PHP* class.

Class property **MUST** have docblock section with the described *PHP* type to have property
included into WSDL description.

`addComplexType()` checks if type is already described within types section of the WSDL document.

It prevents duplications if this method is called two or more times and recursion in the types
definition section.

See <http://www.w3.org/TR/wsdl#_types> for the details.

## addDocumentation() method

`addDocumentation($input_node, $documentation)` method adds human readable documentation using
optional 'wsdl:document' element.

'/definitions/binding/soap:binding' element is used to signify that the binding is bound to the
*SOAP* protocol format.

See <http://www.w3.org/TR/wsdl#_documentation> for the details.

## Get finalized WSDL document

`toXML()`, `toDomDocument()` and `dump($filename = false)` methods may be used to get WSDL document
as an *XML*, DOM structure or a file.

[1] *'tns:' namespace* is defined as script *URI* (*'<http://>' .$\_SERVER\['HTTP\_HOST'\] .
$\_SERVER\['SCRIPT\_NAME'\]*).

[2] *'<http://>' .$\_SERVER\['HTTP\_HOST'\] . $\_SERVER\['SCRIPT\_NAME'\]*

[3] By default `Zend\Soap\Wsdl` will be created with the
`Zend\Soap\Wsdl\ComplexTypeStrategy\DefaultComplexType` class as detection algorithm for complex
types. The first parameter of the AutoDiscover constructor takes any complex type strategy
implementing `Zend\Soap\Wsdl\ComplexTypeStrategy\ComplexTypeStrategyInterface` or a string with the
name of the class. For backwards compatibility with `$extractComplexType` boolean variables are
parsed the following way: If `TRUE`, `Zend\Soap\Wsdl\ComplexTypeStrategy\DefaultComplexType`, if
`FALSE` `Zend\Soap\Wsdl\ComplexTypeStrategy\AnyType`.
