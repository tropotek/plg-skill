<?php
namespace Skill\Controller\Entry;

use App\Controller\AdminEditIface;
use Dom\Template;
use Tk\Form\Event;
use Tk\Form\Field;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Edit extends AdminEditIface
{

    /**
     * @var \Skill\Db\Entry
     */
    protected $entry = null;

    /**
     * @var \App\Ui\Table\Status
     */
    protected $statusTable = null;


    /**
     * Iface constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Skill Entry Edit');
    }

    /**
     * @param Request $request
     * @param $institutionHash
     * @param $collectionId
     * @param $placementHash
     * @return string
     */
    public function doEntrySubmission(Request $request, $institutionHash, $collectionId, $placementHash)
    {
        vd($institutionHash, $collectionId, $placementHash);
        return '<div><h4>This is waiting for the goods</h4></div>';
    }

    /**
     *
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
        $this->entry = new \Skill\Db\Entry();
        $this->entry->collectionId = (int)$request->get('collectionId');
        if ($request->get('entryId')) {
            $this->entry = \Skill\Db\EntryMap::create()->find($request->get('entryId'));
        }
        if ($request->get('collectionId') && $request->get('placementId')) {
            $e = \Skill\Db\EntryMap::create()->findFiltered(array('collectionId' => $request->get('collectionId'),
                'placementId' => $request->get('placementId')))->current();
            if ($e) $this->entry = $e;
        }
        $this->collection = $this->entry->getCollection();

        if ($this->entry->getId()) {
            $this->getActionPanel()->addButton(\Tk\Ui\Button::create('View', \App\Uri::create('/skill/entryView.html')->set('entryId', $this->entry->getId()), 'fa fa-eye'));
        }

        $this->buildForm();

        $this->form->load(\Skill\Db\EntryMap::create()->unmapForm($this->entry));
        $this->form->execute($request);

        if ($this->getUser()->isStaff()) {
            $this->statusTable = new \App\Ui\Table\Status(\App\Uri::createCourseUrl('/mailLogManager.html'));
            if ($this->entry->getId()) {
                $filter = $this->statusTable->getTable()->getFilterValues();
                $filter['model'] = $this->entry;
                $filter['courseId'] = $this->entry->courseId;
                $list = \App\Db\StatusMap::create()->findFiltered($filter, $this->statusTable->getTable()->makeDbTool('created DESC'));
                $this->statusTable->setList($list);
            }
        }

    }

    /**
     * buildForm
     */
    protected function buildForm() 
    {
        $this->form = \App\Factory::createForm('entryEdit');
        $this->form->setParam('renderer', \App\Factory::createFormRenderer($this->form));

        $this->form->addField(new Field\Input('title'))->setRequired()->setFieldset('Entry Details');
        if ($this->entry->getId() && $this->entry->getCollection()->gradable) {
            $pct = round(($this->entry->average/($this->entry->getCollection()->getScaleLength()-1))*100);
            $this->form->addField(new Field\Html('average', sprintf('%.2f &nbsp; (%d%%)', $this->entry->average, $pct)))->setFieldset('Entry Details');
            $pct = round(($this->entry->weightedAverage/($this->entry->getCollection()->getScaleLength()-1))*100);
            $this->form->addField(new Field\Html('weightedAverage', sprintf('%.2f &nbsp; (%d%%)', $this->entry->weightedAverage, $pct)))->setFieldset('Entry Details');
        }

        if ($this->getUser()->isStaff()) {
            $this->form->addField(new \App\Form\Field\CheckSelect('status', \Skill\Db\Entry::getStatusList()))
                ->setRequired()->prependOption('-- Status --', '')->setNotes('Set the status. Use the checkbox to disable notification emails.')->setFieldset('Entry Details');
        }

        $this->form->addField(new Field\Input('assessor'))->setFieldset('Entry Details')->setRequired();
        $this->form->addField(new Field\Input('absent'))->setFieldset('Entry Details');
        $this->form->addField(new Field\Textarea('notes'))->addCss('tkTextareaTool')->setFieldset('Entry Details');

        $items = \Skill\Db\ItemMap::create()->findFiltered(array('collectionId' => $this->entry->getCollection()->getId()),
            \Tk\Db\Tool::create('category_id, order_by'));
        /** @var \Skill\Db\Item $item */
        foreach ($items as $item) {
            $fld = $this->form->addField(new \Skill\Form\Field\Item($item))->setLabel(null);
            $val = \Skill\Db\EntryMap::create()->findValue($this->entry->getId(), $item->getId());
            if ($val) {
                $fld->setValue($val->value);
            }
        }

        $radioBtn = new \Tk\Form\Field\RadioButton('confirm', $this->entry->getCollection()->confirm);
        $radioBtn->appendOption('Yes', '1', 'fa fa-check')->appendOption('No', '0', 'fa fa-ban');
        $this->form->addField($radioBtn)->setLabel(null)->setFieldset('Confirmation')->setValue(true);

        $this->form->addField(new Event\Button('update', array($this, 'doSubmit')));
        $this->form->addField(new Event\Button('save', array($this, 'doSubmit')));
        $this->form->addField(new Event\Link('cancel', \App\Factory::getCrumbs()->getBackUrl()));

    }

    /**
     * @param \Tk\Form $form
     */
    public function doSubmit($form)
    {
        // Load the object with data from the form using a helper object
        \Skill\Db\EntryMap::create()->mapForm($form->getValues(), $this->entry);
        
        if (!$form->getFieldValue('status') || !in_array($form->getFieldValue('status'),
                \Tk\Object::getClassConstants($this->entry, 'STATUS'))) {
            $form->addFieldError('status', 'Please Select a valid status');
        }

        $form->addFieldErrors($this->entry->validate());

        if ($form->hasErrors()) {
            return;
        }

        // Save Item values
        \Skill\Db\EntryMap::create()->removeValue($this->entry->getVolatileId());
        foreach ($form->getValues('/^item\-/') as $name => $val) {
            $id = (int)substr($name, strrpos($name, '-')+1);
            \Skill\Db\EntryMap::create()->saveValue($this->entry->getVolatileId(), $id, (int)$val);
        }

        $this->entry->save();

        // Create status if changed and trigger notifications
        \App\Db\Status::createFromField($this->entry, $form->getField('status'),
            $this->entry->getCourse()->getProfile(), $this->entry->getCourse());


        \Tk\Alert::addSuccess('Record saved!');
        if ($form->getTriggeredEvent()->getName() == 'update') {
            \App\Factory::getCrumbs()->getBackUrl()->redirect();
        }
        \Tk\Uri::create()->set('entryId', $this->entry->getId())->redirect();
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        $template->insertTemplate('form', $this->form->getParam('renderer')->show()->getTemplate());

        $template->appendCssUrl(\Tk\Uri::create('/plugin/ems-skill/assets/skill.less'));
        $template->appendJsUrl(\Tk\Uri::create('/plugin/ems-skill/assets/skill.js'));

        if ($this->entry->getId()) {
            $template->setChoice('edit');

            if ($this->statusTable) {
                $template->replaceTemplate('statusTable', $this->statusTable->getTable()->getParam('renderer')->show());
                $template->setChoice('statusLog');
            }
        }

        if (!$this->getUser()->isStaff()) {
            $template->insertHtml('instructions', $this->entry->getCollection()->instructions);
        }

        $css = <<<CSS
.form-group.tk-item:nth-child(odd) .skill-item {
  background-color: {$this->entry->getCollection()->color};
}
.tk-form fieldset:first-child legend {
  display: none !important;
}
CSS;
        $template->appendCss($css);

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
<div class="EntryEdit">

  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><i class="fa fa-pencil"></i> <span var="panel-title">Skill Entry Edit</span></h4>
    </div>
    <div class="panel-body">
      <div var="instructions"></div>
      <div var="form"></div>
    </div>
  </div>

  <div class="panel panel-default" choice="edit">
    <div class="panel-heading">
      <h4 class="panel-title"><i class="fa fa-sitemap"></i> <span>Status Log</span></h4>
    </div>
    <div class="panel-body">
      <div var="statusTable"></div>
    </div>
  </div>
  
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}