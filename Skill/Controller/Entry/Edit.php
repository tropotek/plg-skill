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
    }


    protected function buildForm() 
    {
        $this->form = \App\Factory::createForm('entryEdit');
        $this->form->setParam('renderer', \App\Factory::createFormRenderer($this->form));

        //$this->form->addField(new Field\Input('uid'))->setNotes('(optional) Use this to match up questions from other collections, for generating reports');

        $list = \Skill\Db\CategoryMap::create()->findFiltered(array('collectionId' => $this->collection->getId()));
        $this->form->addField(new Field\Select('categoryId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list)))->prependOption('-- Select --', '')->setNotes('');

//        $list = \Skill\Db\DomainMap::create()->findFiltered(array('collectionId' => $this->collection->getId()));
//        $this->form->addField(new Field\Select('domainId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list)))->prependOption('-- None --', '')->setNotes('');

        $this->form->addField(new Field\Input('title'))->setRequired();
        $this->form->addField(new Field\Input('assessor'));
        $this->form->addField(new Field\Input('absent'));
        $this->form->addField(new Field\Input('status'));
        $this->form->addField(new Field\Textarea('notes'));

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

        $form->addFieldErrors($this->entry->validate());

        if ($form->hasErrors()) {
            return;
        }
        $this->entry->save();

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
<div>
    
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><i class="fa fa-pencil"></i> <span var="panel-title">Skill Entry Edit</span></h4>
    </div>
    <div class="panel-body">
      <div var="form"></div>
    </div>
  </div>
  
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}