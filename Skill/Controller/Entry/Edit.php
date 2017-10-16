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
     * @var \Skill\Db\Collection
     */
    protected $collection = null;

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
        $this->collection = $this->entry->getCollection();

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


    protected function buildForm() 
    {
        $this->form = \App\Factory::createForm('entryEdit');
        $this->form->setParam('renderer', \App\Factory::createFormRenderer($this->form));

        $this->form->addField(new Field\Input('title'))->setRequired()->setFieldset('Entry Details');

        if ($this->getUser()->isStaff()) {
            $this->form->addField(new \App\Form\Field\CheckSelect('status', \App\Db\Company::getStatusList(),'notify', 'Un-check to disable sending of email messages.'))
                ->setRequired()->prependOption('-- Status --', '')->setNotes('Set the status. Use the checkbox to disable notification emails.')->setFieldset('Entry Details');
            $this->form->addField(new Field\Textarea('statusNotes'))->setNotes('Add a comment to the change status log.')->setFieldset('Entry Details');
        }

        $this->form->addField(new Field\Input('assessor'))->setFieldset('Entry Details')->setRequired();
        $this->form->addField(new Field\Input('absent'))->setFieldset('Entry Details');
        $this->form->addField(new Field\Textarea('notes'))->addCss('tkTextareaTool')->setFieldset('Entry Details');


        $items = \Skill\Db\ItemMap::create()->findFiltered(array('collectionId' => $this->collection->getId()),
            \Tk\Db\Tool::create('category_id, order_by'));
        /** @var \Skill\Db\Item $item */
        foreach ($items as $item) {
            $this->form->addField(new \Skill\Form\Field\Item($item))->setLabel(null)->setValue(\Skill\Db\EntryMap::create()->findValue($this->entry->getId(), $item->getId())->value);
        }

        $this->form->addField(new \Skill\Form\Field\Confirm('confirm', $this->collection->confirm))->setLabel(null)->setFieldset('Confirmation')->setValue(true);


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
        
        if (!$form->getFieldValue('status') || !in_array($form->getFieldValue('status'), \Tk\Object::getClassConstants($this->entry, 'STATUS'))) {
            $form->addFieldError('status', 'Please Select a valid status');
        }

        $form->addFieldErrors($this->entry->validate());



        vd($form->getValues());



        if ($form->hasErrors()) {
            return;
        }
        $this->entry->save();

        \App\Db\Status::create(\Skill\Status\EntryHandler::create($this->entry), $form->getFieldValue('status'),
            $this->entry->getId(), $this->entry->courseId, $form->getFieldValue('statusNotes'));

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

        if ($this->entry->getId()) {
            $template->setChoice('edit');

            if ($this->statusTable) {
                $template->replaceTemplate('statusTable', $this->statusTable->getTable()->getParam('renderer')->show());
                $template->setChoice('statusLog');
            }
        }

        $css = <<<CSS
.form-group.tk-item:nth-child(odd) .skill-item {
  background-color: {$this->collection->color};
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