<?php
namespace Skill\Form;

use Tk\Form;
use Tk\Form\Event;
use Tk\Form\Field;

/**
 * Example:
 * <code>
 *   $form = new Collection::create();
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
class Collection extends \App\FormIface
{

    /**
     * @throws \Exception
     */
    public function init()
    {

        $layout = $this->getRenderer()->getLayout();
        $layout->addRow('name', 'col-md-6');
        $layout->removeRow('role', 'col-md-6');
        $layout->addRow('icon', 'col-md-6');
        $layout->removeRow('color', 'col-md-6');

        $layout->addRow('active', 'col-md-6');
        $layout->removeRow('publish', 'col-md-6');

        $tab = 'Details';
        $this->appendField(new Field\Input('name'))->setTabGroup($tab)->setNotes('Create a label for this collection');

        $list = \Tk\Form\Field\Select::arrayToSelectList(\Tk\ObjectUtil::getClassConstants('\Skill\Db\Collection', 'TYPE'));
        $this->appendField(new Field\Select('role', $list))->setTabGroup($tab)->prependOption('-- Select --', '')
            ->setNotes('');

        $list = array('tk tk-clear', 'tk tk-goals', 'fa fa-eye', 'fa fa-user-circle-o', 'fa fa-bell', 'fa fa-certificate', 'fa fa-tv', 'fa fa-drivers-license',
            'fa fa-leaf', 'fa fa-trophy', 'fa fa-ambulance', 'fa fa-rebel', 'fa fa-empire', 'fa fa-font-awesome', 'fa fa-heartbeat',
            'fa fa-medkit', 'fa fa-user-md', 'fa fa-user-secret', 'fa fa-heart');
        $this->appendField(new Field\Select('icon', Field\Select::arrayToSelectList($list, false)))->setTabGroup($tab)
            ->addCss('iconpicker')->setNotes('Select an icon for this collection');

        $this->appendField(new Field\Input('color'))->setAttr('type', 'color')->setTabGroup($tab)
            ->setNotes('Select a base color for this collection. Used to highlight the question background.');


        $this->appendField(new Field\Checkbox('active'))->setTabGroup($tab)
            ->setCheckboxLabel('Enable/Disable this collection for the subject.');
        $this->appendField(new Field\Checkbox('publish'))->setTabGroup($tab)
            ->setCheckboxLabel('Allow students to view their supervisor submissions (with comments by default) and any results if this collection is gradable');
//        $this->appendField(new Field\Checkbox('includeZero'))->setTabGroup($tab)
//            ->setCheckboxLabel('Should the zero values be included in the weighted average calculation.');

        $this->appendField(new Field\Input('confirm'))->setTabGroup($tab)
            ->setNotes('If set, the user will be prompted with the given text before they can submit their entry.');

        $tab = 'Information';
        $this->appendField(new Field\Textarea('instructions'))->setTabGroup($tab)
            ->setNotes('Enter any student instructions on how to complete placement entries.')
            ->addCss('mce')->setAttr('data-elfinder-path', $this->getConfig()->getInstitution()->getDataPath().'/media');
//        $this->appendField(new Field\Textarea('notes'))->setTabGroup($tab)
//            ->addCss('tkTextareaTool')->setNotes('Staff only notes that can only be vied in this edit screen.');


        $tab = 'Placement';

        $this->appendField(new Field\Checkbox('requirePlacement'))->addCss('tk-input-toggle')->setTabGroup($tab)
            ->setCheckboxLabel('If a collection entry requires a placement to be associated with.');

        $this->appendField(new Field\Checkbox('gradable'))->setTabGroup($tab)
            ->setCheckboxLabel('If enabled (Requires Placement) then the student can view a summary of the results for this collection.');

        $list = \App\Db\PlacementTypeMap::create()->findFiltered(array('subjectId' => $this->getCollectionObj()->getSubject()->getId()));
        $ptiField = $this->appendField(new Field\Select('placementTypeId[]', \Tk\Form\Field\Option\ArrayObjectIterator::create($list)))
            ->setTabGroup($tab)->addCss('tk-dual-select')->setAttr('data-title', 'Placement Types')
            ->setNotes('Enable this collection for the selected placement types.');
        $list = \Skill\Db\CollectionMap::create()->findPlacementTypes($this->getCollectionObj()->getId());
        $ptiField->setValue($list);

        $list = \Tk\Form\Field\Select::arrayToSelectList(\Tk\ObjectUtil::getClassConstants('\App\Db\Placement', 'STATUS'));
        $this->appendField(new Field\Select('available[]', $list))->setTabGroup($tab)
            ->addCss('tk-dual-select')->setAttr('data-title', 'Placement Status')
            ->setNotes('Enable editing for entries on the following placement status');
        

        if ($this->getCollectionObj()->getId())
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
        $this->load(\Skill\Db\CollectionMap::create()->unmapForm($this->getCollectionObj()));
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
        \Skill\Db\CollectionMap::create()->mapForm($form->getValues(), $this->getCollectionObj());

        // Do Custom Validations
        $placemenTypeIds = $form->getFieldValue('placementTypeId');
        if($this->getCollectionObj()->requirePlacement && is_array($placemenTypeIds) && !count($placemenTypeIds)) {
            $form->addFieldError('placementTypeId', 'Please select at least one placement type for this collection to be enabled for.');
        }

        $form->addFieldErrors($this->getCollectionObj()->validate());
        if ($form->hasErrors()) {
            return;
        }
        
        $isNew = (bool)$this->getCollectionObj()->getId();
        $this->getCollectionObj()->save();

        \Skill\Db\CollectionMap::create()->removePlacementType($this->getCollectionObj()->getId());
        if ($this->getCollectionObj()->requirePlacement && count($placemenTypeIds)) {
            foreach ($placemenTypeIds as $placementTypeId) {
                \Skill\Db\CollectionMap::create()->addPlacementType($this->getCollectionObj()->getId(), $placementTypeId);
            }
        }

        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->set('collectionId', $this->getCollectionObj()->getId()));
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\Skill\Db\Collection
     */
    public function getCollectionObj()
    {
        return $this->getModel();
    }

    /**
     * NOTE: get/setCollection() cannot be used as  methods due to a clash with the ArrayCollection object
     * @param \Skill\Db\Collection $collection
     * @return $this
     */
    public function setCollectionObj($collection)
    {
        return $this->setModel($collection);
    }
    
}