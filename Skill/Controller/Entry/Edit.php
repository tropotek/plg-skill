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

        $this->form->addField(new Field\Input('title'))->setRequired();

        if ($this->getUser()->isStaff()) {
            $this->form->addField(new \App\Form\Field\CheckSelect('status', \App\Db\Company::getStatusList(),'notify', 'Un-check to disable sending of email messages.'))
                ->setRequired()->prependOption('-- Status --', '')->setNotes('Set the status. Use the checkbox to disable notification emails.');
            $this->form->addField(new Field\Textarea('statusNotes'))->setNotes('Add a comment to the change status log.');
        }

        $this->form->addField(new Field\Input('assessor'))->setRequired();
        $this->form->addField(new Field\Input('absent'));
        $this->form->addField(new Field\Textarea('notes'))->addCss('tkTextareaTool');

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
            $template->addCss('panel', 'col-md-7');
            $template->setChoice('edit');

            if ($this->statusTable) {
                $template->replaceTemplate('statusTable', $this->statusTable->getTable()->getParam('renderer')->show());
                $template->setChoice('statusLog');
            }
        }

        $i = 1;
        $categories = \Skill\Db\CategoryMap::create()->findFiltered(array('collectionId' => $this->entry->collectionId));
        /** @var \Skill\Db\Category $category */
        foreach ($categories as $category) {
            $cRow = $template->getRepeat('category');
            $items = \Skill\Db\ItemMap::create()->findFiltered(array('categoryId' => $category->getId()));
            if (!$items->count()) continue;
            $cRow->insertText('name', $category->name);
            $cRow->insertText('item-count', $items->count());

            /** @var \Skill\Db\Item $item */
            foreach ($items as $item) {
                $iRow = $cRow->getRepeat('item');
                if ($i%2 > 0)
                    $iRow->setAttr('item', 'style', 'background-color: '.$this->entry->getCollection()->color.';');

                //$iRow->insertText('uid', $item->uid);
                $iRow->insertText('uid', $i);
                $iRow->insertText('question', $item->question);
                if ($item->description)
                    $iRow->setAttr('question', 'title', substr($item->description, 0 , 100));

                $value = \Skill\Db\EntryMap::create()->findValue($this->entry->getId(), $item->getId());
                $iRow->setAttr('value', 'name', 'item-'.$item->getId());
                $iRow->setAttr('value', 'id', 'id-item-'.$item->getId());
                $iRow->setAttr('value', 'value', $value->value);

                $iRow->appendRepeat();
                $i++;
            }


            $cRow->appendRepeat();
        }


        // TODO: setup slider javascript etc

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

  <div class="row">
    <div class="col-md-12" var="panel">

      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 class="panel-title"><i class="fa fa-pencil"></i> <span var="panel-title">Skill Entry Edit</span></h4>
        </div>
        <div class="panel-body" style="height: 490px;overflow: auto">
          <div var="form"></div>
        </div>
      </div>

    </div>
    <div class="col-md-5" choice="edit">
      
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 class="panel-title"><i class="fa fa-sitemap"></i> <span>Status Log</span></h4>
        </div>
        <div class="panel-body" style="height: 490px;overflow: auto">
          <div var="statusTable"></div>
        </div>
      </div>
      
    </div>

  </div>
      
      <div class="panel panel-default" choice1="statusLog">
        <div class="panel-heading">
          <h4 class="panel-title"><i class="fa fa-sitemap"></i> <span>Skills</span></h4>
        </div>
        <div class="panel-body">
          
          
        <div>
          <div class="skill-block entry-edit clearfix" var="entry-edit">
            <!-- Basic Skill Category template -->
            <div class="skill-list">
            
              <div class="skill-category" repeat="category" var="category">
                <h3 class="skill-category-header">
                  <span class="skill-category-title" var="name">Parent Title</span> <span class="skill-count pull-right" var="item-count">0</span>
                </h3>
                  
                <div class="skill-item clearfix" repeat="item" var="item">
                  <div class="col-md-8">
                    <p class="skill-item-name">
                      <span var="uid">1</span>. <span for="fid-cb" var="question">Skill item question or description text</span>
                    </p>
                  </div>
                  <div class="col-md-4">
                    <div class="skill-input">
                      <div class="skill-slider">
                        <input type="text" name="item-00" class="form-control skill-input-field tk-skillSlider" value="0" var="value"/>
                      </div>
                    </div>
                  </div>
                </div>
                
              </div>
            </div>
          </div>
        </div>
          
          
          
          
        </div>
      </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}