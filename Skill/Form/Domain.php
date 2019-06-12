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
class Domain extends \App\FormIface
{

    /**
     * @throws \Exception
     */
    public function init()
    {

        $this->appendField(new Field\Input('name'))->setNotes('');
        $this->appendField(new Field\Input('label'))->setNotes('Create a short label for this domain');
        $this->appendField(new Field\Input('weight'))->setNotes('for weighted marks ad a multiplier value here from 0.0 to 1.0');
        $this->appendField(new Field\Checkbox('active'));
        $this->appendField(new Field\Input('description'))->setNotes('A short description of the domain');
        
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
        $this->load(\Skill\Db\DomainMap::create()->unmapForm($this->getDomain()));
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
        \Skill\Db\DomainMap::create()->mapForm($form->getValues(), $this->getDomain());

        // Do Custom Validations

        $form->addFieldErrors($this->getDomain()->validate());
        if ($form->hasErrors()) {
            return;
        }
        
        $isNew = (bool)$this->getDomain()->getId();
        $this->getDomain()->save();

        // Do Custom data saving

        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->set('domainId', $this->getDomain()->getId()));
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\Skill\Db\Domain
     */
    public function getDomain()
    {
        return $this->getModel();
    }

    /**
     * @param \Skill\Db\Domain $domain
     * @return $this
     */
    public function setDomain($domain)
    {
        return $this->setModel($domain);
    }
    
}