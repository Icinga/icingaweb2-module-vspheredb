<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use dipl\Html\ValidHtml;
use ipl\Html\Html;
use Icinga\Module\Director\Web\Form\DirectorForm;

class BaseForm extends DirectorForm implements ValidHtml
{
    private $hintCount = 0;

    /**
     * BaseForm constructor.
     * @param null $options
     * @throws \Zend_Form_Exception
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
        $this->addPrefixPath(
            __NAMESPACE__ . '\\Element\\',
            __DIR__ . '/Element',
            static::ELEMENT
        );

        return $this;
    }

    /**
     * @param $html
     * @param array $options
     * @return DirectorForm|BaseForm
     * @throws \Zend_Form_Exception
     */
    public function addHint($html, $options = [])
    {
        return $this->addHtml(Html::tag('p', ['class' => 'information'], $html), $options);
    }

    /**
     * @param $html
     * @param array $options
     * @return $this|DirectorForm
     * @throws \Zend_Form_Exception
     */
    public function addHtml($html, $options = [])
    {
        if (array_key_exists('name', $options)) {
            $name = $options['name'];
            unset($options['name']);
        } else {
            $name = '_HINT' . ++$this->hintCount;
        }

        $this->addElement('htmlElement', $name, $options);
        $this->getElement($name)
            ->setValue($html)
            ->setIgnore(true)
            ->setDecorators([]);

        return $this;
    }
}
