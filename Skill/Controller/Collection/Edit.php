<?php
namespace Skill\Controller\Collection;

use App\Controller\AdminEditIface;
use Dom\Template;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Edit extends AdminEditIface
{

    /**
     * @var \Skill\Db\Collection
     */
    protected $collection = null;


    /**
     * Iface constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Skill Collection Edit');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->collection = new \Skill\Db\Collection();
        $this->collection->subjectId = $this->getConfig()->getSubjectId();
        if ($request->get('collectionId')) {
            $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));
        }

        $this->setForm(\Skill\Form\Collection::create()->setModel($this->collection));
        $this->initForm($request);
        $this->getForm()->execute();

//        $this->buildForm();
//        $this->form->load(\Skill\Db\CollectionMap::create()->unmapForm($this->collection));
//        $this->form->execute($request);
    }

    /**
     * @throws \Exception
     */
//    protected function buildForm()
//    {
//        $this->form = \Uni\Config::getInstance()->createForm('collectionEdit');
//        $this->form->setRenderer(\Uni\Config::getInstance()->createFormRenderer($this->form));
//
//        $layout = $this->form->getRenderer()->getLayout();
//        $layout->addRow('name', 'col-md-6');
//        $layout->removeRow('role', 'col-md-6');
//        $layout->addRow('icon', 'col-md-6');
//        $layout->removeRow('color', 'col-md-6');
//
//        $layout->addRow('active', 'col-md-6');
//        $layout->removeRow('publish', 'col-md-6');
//
//        $tab = 'Details';
//        $this->form->appendField(new Field\Input('name'))->setTabGroup($tab)->setNotes('Create a label for this collection');
//        $list = \Tk\Form\Field\Select::arrayToSelectList(\Tk\ObjectUtil::getClassConstants('\Skill\Db\Collection', 'ROLE'));
//        $this->form->appendField(new Field\Select('role', $list))->setTabGroup($tab)->prependOption('-- Select --', '')
//            ->setNotes('');
//
//        $list = array('tk tk-clear', 'tk tk-goals', 'fa fa-eye', 'fa fa-user-circle-o', 'fa fa-bell', 'fa fa-certificate', 'fa fa-tv', 'fa fa-drivers-license',
//            'fa fa-leaf', 'fa fa-trophy', 'fa fa-ambulance', 'fa fa-rebel', 'fa fa-empire', 'fa fa-font-awesome', 'fa fa-heartbeat',
//            'fa fa-medkit', 'fa fa-user-md', 'fa fa-user-secret', 'fa fa-heart');
//        $this->form->appendField(new Field\Select('icon', Field\Select::arrayToSelectList($list, false)))->setTabGroup($tab)
//            ->addCss('iconpicker')->setNotes('Select an icon for this collection');
//
//        $this->form->appendField(new Field\Input('color'))->setAttr('type', 'color')->setTabGroup($tab)
//            ->setNotes('Select a base color for this collection. Used to highlight the question background.');
//
//
//        $this->form->appendField(new Field\Checkbox('active'))->setTabGroup($tab)
//            ->setCheckboxLabel('Enable/Disable this collection for the subject.');
//        $this->form->appendField(new Field\Checkbox('publish'))->setTabGroup($tab)
//            ->setCheckboxLabel('Allow students to view their supervisor submissions (with comments by default) and any results if this collection is gradable');
////        $this->form->appendField(new Field\Checkbox('includeZero'))->setTabGroup($tab)
////            ->setCheckboxLabel('Should the zero values be included in the weighted average calculation.');
//
//        $this->form->appendField(new Field\Input('confirm'))->setTabGroup($tab)
//            ->setNotes('If set, the user will be prompted with the given text before they can submit their entry.');
//
//        $tab = 'Information';
//        $this->form->appendField(new Field\Textarea('instructions'))->setTabGroup($tab)
//            ->setNotes('Enter any student instructions on how to complete placement entries.')
//            ->addCss('mce')->setAttr('data-elfinder-path', $this->getInstitution()->getDataPath().'/media');
////        $this->form->appendField(new Field\Textarea('notes'))->setTabGroup($tab)
////            ->addCss('tkTextareaTool')->setNotes('Staff only notes that can only be vied in this edit screen.');
//
//
//        $tab = 'Placement';
//
//        $this->form->appendField(new Field\Checkbox('requirePlacement'))->addCss('tk-input-toggle')->setTabGroup($tab)
//            ->setCheckboxLabel('If a collection entry requires a placement to be associated with.');
//
//        $this->form->appendField(new Field\Checkbox('gradable'))->setTabGroup($tab)
//            ->setCheckboxLabel('If enabled (Requires Placement) then the student can view a summary of the results for this collection.');
//
//        $list = \App\Db\PlacementTypeMap::create()->findFiltered(array('subjectId' => $this->collection->getSubject()->getId(), 'active' => true));
//        $ptiField = $this->form->appendField(new Field\Select('placementTypeId[]', \Tk\Form\Field\Option\ArrayObjectIterator::create($list)))
//            ->setTabGroup($tab)->addCss('tk-dual-select')->setAttr('data-title', 'Placement Types')
//            ->setNotes('Enable this collection for the selected placement types.');
//        $list = \Skill\Db\CollectionMap::create()->findPlacementTypes($this->collection->getId());
//        $ptiField->setValue($list);
//
//        $list = \Tk\Form\Field\Select::arrayToSelectList(\Tk\ObjectUtil::getClassConstants('\App\Db\Placement', 'STATUS'));
//        $this->form->appendField(new Field\Select('available[]', $list))->setTabGroup($tab)
//            ->addCss('tk-dual-select')->setAttr('data-title', 'Placement Status')
//            ->setNotes('Enable this collection on the following placement status');
//
//
//        if ($this->collection->getId())
//            $this->form->appendField(new Event\Submit('update', array($this, 'doSubmit')));
//
//        $this->form->appendField(new Event\Submit('save', array($this, 'doSubmit')));
//        $this->form->appendField(new Event\Link('cancel', $this->getConfig()->getBackUrl()));
//
//    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
//    public function doSubmit($form, $event)
//    {
//        // Load the object with data from the form using a helper object
//        \Skill\Db\CollectionMap::create()->mapForm($form->getValues(), $this->collection);
//
//        $placemenTypeIds = $form->getFieldValue('placementTypeId');
//        if($this->collection->requirePlacement && !count($placemenTypeIds)) {
//            $form->addFieldError('placementTypeId', 'Please select at least one placement type for this collection to be enabled for.');
//        }
//
//        $form->addFieldErrors($this->collection->validate());
//
//        if ($form->hasErrors()) {
//            return;
//        }
//        $this->collection->save();
//
//        \Skill\Db\CollectionMap::create()->removePlacementType($this->collection->getId());
//        if (count($placemenTypeIds)) {
//            foreach ($placemenTypeIds as $placementTypeId) {
//                \Skill\Db\CollectionMap::create()->addPlacementType($this->collection->getId(), $placementTypeId);
//            }
//        }
//
//        \Tk\Alert::addSuccess('Record saved!');
//        $event->setRedirect($this->getConfig()->getBackUrl());
//        if ($form->getTriggeredEvent()->getName() == 'save') {
//            $event->setRedirect(\Tk\Uri::create()->set('collectionId', $this->collection->getId()));
//        }
//    }

    /**
     *
     */
    public function initActionPanel()
    {
        if ($this->collection->getId()) {
            if ($this->collection->gradable) {
                if ($this->getConfig()->isDebug())
                    $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Historic Report All', \Uni\Uri::createSubjectUrl('/historicReportAll.html')->set('collectionId', $this->collection->getId()), 'fa fa-list-alt'));
                if ($this->collection->requirePlacement) {
                    $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Historic Report', \Uni\Uri::createSubjectUrl('/historicReport.html')->set('collectionId', $this->collection->getId()), 'fa fa-table'));
                    $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Item Average Report', \Uni\Uri::createSubjectUrl('/itemAverageReport.html')->set('collectionId', $this->collection->getId()), 'fa fa-line-chart'));
                    $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Date Average Report', \Uni\Uri::createSubjectUrl('/dateAverageReport.html')->set('collectionId', $this->collection->getId()), 'fa fa-calendar'));
                    $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Company Average Report', \Uni\Uri::createSubjectUrl('/companyAverageReport.html')->set('collectionId', $this->collection->getId()), 'fa fa-building-o'));
                }
            }

            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('View Entries', \Uni\Uri::createSubjectUrl('/entryManager.html')->set('collectionId', $this->collection->getId()), 'fa fa-files-o'));
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Domains', \Uni\Uri::createSubjectUrl('/domainManager.html')->set('collectionId', $this->collection->getId()), 'fa fa-black-tie'));
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Categories', \Uni\Uri::createSubjectUrl('/categoryManager.html')->set('collectionId', $this->collection->getId()), 'fa fa-folder-o'));
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Scale', \Uni\Uri::createSubjectUrl('/scaleManager.html')->set('collectionId', $this->collection->getId()), 'fa fa-balance-scale'));
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Items', \Uni\Uri::createSubjectUrl('/itemManager.html')->set('collectionId', $this->collection->getId()), 'fa fa-question'));
        }
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $this->initActionPanel();
        $template = parent::show();

        // Render the form
        $template->appendTemplate('panel', $this->getForm()->show());

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-panel" data-panel-title="Skill Collection Edit" data-panel-icon="fa fa-graduation-cap" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}