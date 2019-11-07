<?php

namespace Icinga\Module\Vspheredb\Web\Form\Element;

use ipl\Html\ValidHtml;
use Zend_Form_Element_Xhtml;

class HtmlElement extends Zend_Form_Element_Xhtml
{
    /**
     * Always ignore this element
     * @codingStandardsIgnoreStart
     *
     * @var boolean
     */
    protected $_ignore = true;
    // @codingStandardsIgnoreEnd

    public function __construct($spec, $options = null)
    {
        parent::__construct($spec, $options);
    }

    public function render(\Zend_View_Interface $view_ = null)
    {
        $value = $this->getValue();
        if ($value instanceof ValidHtml) {
            return $this->getValue()->render();
        } else {
            return $value;
        }
    }
}
