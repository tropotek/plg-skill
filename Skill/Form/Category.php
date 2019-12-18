<?php
namespace Skill\Form;

use Tk\Form;
use Tk\Form\Event;
use Tk\Form\Field;

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
class Category extends \App\FormIface
{

    /**
     * @throws \Exception
     */
    public function init()
    {

        $layout = $this->getRenderer()->getLayout();
        $layout->addRow('name', 'col-md-6');
        $layout->removeRow('label', 'col-md-6');

        $this->appendField(new Field\Input('name'))->setNotes('');
        $this->appendField(new Field\Input('label'))->setNotes('');
        $this->appendField(new Field\Checkbox('publish'))->setCheckboxLabel('Category is visible to students');
        $this->appendField(new Field\Textarea('description'))->addCss('tkTextareaTool')
            ->setNotes('A short description of the category');

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
        $this->load(\Skill\Db\CategoryMap::create()->unmapForm($this->getCategory()));
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
        \Skill\Db\CategoryMap::create()->mapForm($form->getValues(), $this->getCategory());

        // Do Custom Validations

        $form->addFieldErrors($this->getCategory()->validate());
        if ($form->hasErrors()) {
            return;
        }
        
        $isNew = (bool)$this->getCategory()->getId();
        $this->getCategory()->save();

        // Do Custom data saving

        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->set('categoryId', $this->getCategory()->getId()));
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\Skill\Db\Category
     */
    public function getCategory()
    {
        return $this->getModel();
    }

    /**
     * @param \Skill\Db\Category $category
     * @return $this
     */
    public function setCategory($category)
    {
        return $this->setModel($category);
    }
    
}