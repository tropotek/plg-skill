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
class Item extends \App\FormIface
{

    /**
     * @throws \Exception
     */
    public function init()
    {

        $layout = $this->getRenderer()->getLayout();
        $layout->addRow('categoryId', 'col-md-6');
        $layout->removeRow('domainId', 'col-md-6');


        $list = \Skill\Db\CategoryMap::create()->findFiltered(array('collectionId' => $this->getItem()->getCollection()->getId()));
        $this->appendField(new Field\Select('categoryId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list)))
            ->prependOption('-- Select --', '')->setNotes('');

        $list = \Skill\Db\DomainMap::create()->findFiltered(array('collectionId' => $this->getItem()->getCollection()->getId()));
        if (count($list)) {
            $this->appendField(new Field\Select('domainId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list)))
                ->prependOption('-- None --', '')->setNotes('');
        }

        $this->appendField(new Field\Input('uid'))->setNotes('(optional) Use this to match up questions from other collections, for generating reports');

        $this->appendField(new Field\Input('question'))->setRequired()->setNotes('The question text to display');
        $this->appendField(new Field\Input('description'))->setNotes('Description or help text');
        $this->appendField(new Field\Checkbox('publish'))->setLabel('')->setCheckboxLabel('Publish');
        
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
        $this->load(\Skill\Db\ItemMap::create()->unmapForm($this->getItem()));
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
        \Skill\Db\ItemMap::create()->mapForm($form->getValues(), $this->getItem());

        // Do Custom Validations

        $form->addFieldErrors($this->getItem()->validate());
        if ($form->hasErrors()) {
            return;
        }
        
        $isNew = (bool)$this->getItem()->getId();
        $this->getItem()->save();

        // Do Custom data saving

        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->set('itemId', $this->getItem()->getId()));
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\Skill\Db\Item
     */
    public function getItem()
    {
        return $this->getModel();
    }

    /**
     * @param \Skill\Db\Item $item
     * @return $this
     */
    public function setItem($item)
    {
        return $this->setModel($item);
    }
    
}