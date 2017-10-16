<?php
namespace Skill\Form\Field;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Confirm extends \Tk\Form\Field\Radio
{

    /**
     * @var string
     */
    protected $text = null;



    /**
     * Item constructor.
     *
     * @param string $name
     * @param string $text
     */
    public function __construct($name, $text)
    {
        $options = array('Yes' => '1', 'No' => '0');

        parent::__construct($name, new \Tk\Form\Field\Option\ArrayIterator($options));
        $this->text = $text;
        $this->onShowOption = array($this, 'showOption');
    }

    /**
     * @param \Dom\Template $template
     * @param \Tk\Form\Field\Option $option
     * @param boolean $checkedSet
     */
    public function showOption($template, $option, $checkedSet)
    {
        // allow only one radio to be selected.
        if ($this->isSelected($option->getValue()) && !$checkedSet) {
            $template->addCss('label', 'active');
        }
    }
    
    /**
     * Compare a value and see if it is selected.
     *
     * @param string $val
     * @return bool
     */
    public function isSelected($val = '')
    {
        $value = $this->getValue();
        if ($value !== null && $value == $val) {
            return true;
        }
        return false;
    }


    public function load($values)
    {
        parent::load($values);
        return $this;
    }

    /**
     * Get the element HTML
     *
     * @return string|\Dom\Template
     */
    public function show()
    {
        $template = parent::show();
        $template->insertText('text', $this->text);
        return $template;
    }

    /**
     * makeTemplate
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="">
  <p class="" var="text"></p>
  <div class="radio" data-toggle="buttons">
    <label class="btn btn-default" repeat="option" var="option label"><input type="radio" var="element" autocomplete="off" class="hide" /><span var="text"></span></label>
  </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }
    
    
}