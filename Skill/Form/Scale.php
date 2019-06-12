<?php
namespace Skill\Form;

use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Form;

/**
 * Example:
 * <code>
 *   $form = new CompanyCategory::create();
 *   $form->setModel($obj);
 *   $formTemplate = $form->getRenderer()->show();
 *   $template->appendTemplate('form', $formTemplate);
 * </code>
 * 
 * @author Mick Mifsud
 * @created 2019-06-06
 * @link http://tropotek.com.au/
 * @license Copyright 2019 Tropotek
 */
class Scale extends \App\FormIface
{

    /**
     * @throws \Exception
     */
    public function init()
    {

        // text, textblock, select, checkbox, date, file(????)
        $this->appendField(new Field\Input('name'))->setNotes('');
        //$this->appendField(new Field\Input('value'))->setNotes('');
        $this->appendField(new Field\Input('description'))->setNotes('A short description');
        
        $this->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->appendField(new Event\Link('cancel', $this->getBackUrl()));

    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function execute($request = null)
    {
        $this->load(\Skill\Db\ScaleMap::create()->unmapForm($this->getScale()));
        parent::execute($request);
    }

    /**
     * @param Form $form
     * @param Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        // Load the object with form data
        \Skill\Db\ScaleMap::create()->mapForm($form->getValues(), $this->getScale());

        // Do Custom Validations

        $form->addFieldErrors($this->getScale()->validate());
        if ($form->hasErrors()) {
            return;
        }
        
        $isNew = (bool)$this->getScale()->getId();
        $this->getScale()->save();

        // Do Custom data saving

        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->set('scaleId', $this->getScale()->getId()));
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\Skill\Db\Scale
     */
    public function getScale()
    {
        return $this->getModel();
    }

    /**
     * @param \Skill\Db\Scale $scale
     * @return $this
     */
    public function setScale($scale)
    {
        return $this->setModel($scale);
    }
    
}