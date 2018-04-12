<?php
/**
 * @author   : matt
 * @copyright: 2018 Claritum Limited
 * @license  : Commercial
 */

namespace ZendTest\Soap\TestAsset;

class PropertyDocumentationTestClass
{
    /**
     * Property documentation
     */
    public $withoutType;

    /**
     * Property documentation
     * @type int
     */
    public $withType;

    public $noDoc;
}
