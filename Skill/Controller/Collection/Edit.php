<?php
namespace Skill\Controller\Collection;

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
     * Iface constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Skill Collection Edit');
    }

    /**
     *
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
        $this->collection = new \Skill\Db\Collection();
        $this->collection->profileId = (int)$request->get('profileId');
        if ($request->get('collectionId')) {
            $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));
        }

        $this->buildForm();

        $this->form->load(\Skill\Db\CollectionMap::create()->unmapForm($this->collection));
        $this->form->execute($request);
    }


    protected function buildForm() 
    {
        $this->form = \App\Factory::createForm('collectionEdit');
        $this->form->setParam('renderer', \App\Factory::createFormRenderer($this->form));

        $this->form->addField(new Field\Input('name'))->setNotes('Create a label for this collection');
        $list = \Tk\Form\Field\Select::arrayToSelectList(\Tk\Object::getClassConstants('\Skill\Db\Collection', 'ROLE'));
        $this->form->addField(new Field\Select('role', $list))->prependOption('-- Select --', '');

        $this->form->addField(new Field\Input('icon'))->setNotes('TODO: Create a jquery plugin to select icons.... Select an Icon for this collection.');

        $list = array('fa fa-eye', 'fa fa-check', 'fa fa-commenting-o', 'fa fa-cutlery', 'fa fa-desktop', 'fa fa-drivers-license'
        , 'fa fa-question', 'fa fa-database', 'fa fa-cut', 'fa fa-euro', 'fa fa-cube', 'fa fa-crop', 'tk tk-goals');
        $this->form->addField(new Field\Select('icon', Field\Select::arrayToSelectList($list, false)))->addCss('iconpicker')->setNotes('Select an icon for this collection');

        $list = array('#ffffcc', '#e8fdff', '#ac725e', '#d06b64', '#f83a22', '#fa573c', '#ff7537', '#ffad46', '#42d692',
            '#16a765', '#7bd148', '#b3dc6c', '#fbe983', '#fad165', '#92e1c0', '#9fe1e7', '#9fc6e7', '#4986e7', '#9a9cff', '#b99aff',
            '#c2c2c2', '#cabdbf', '#cca6ac', '#f691b2', '#cd74e6', '#a47ae2');
        $this->form->addField(new Field\Select('color', Field\Select::arrayToSelectList($list, false)))->addCss('colorpicker')->setNotes('Select a color scheme for this collection');

        $list = \Tk\Form\Field\Select::arrayToSelectList(\Tk\Object::getClassConstants('\App\Db\Placement', 'STATUS'));
        $this->form->addField(new Field\Select('available[]', $list))->addCss('tk-dual-select')->setAttr('data-title', 'Placement Status')->setNotes('Enable this collection on the following placement status');


        // TODO TODO TODO TODO TODO TODO TODO
        // TODO TODO TODO TODO TODO TODO TODO
        // TODO TODO TODO TODO TODO TODO TODO
        // TODO TODO TODO TODO TODO TODO TODO
        // TODO TODO TODO TODO TODO TODO TODO
        // TODO TODO TODO TODO TODO TODO TODO
        $list = \App\Db\PlacementTypeMap::create()->findFiltered(array('profileId' => $this->collection->getProfile()->getId()));
        $this->form->addField(new Field\Select('placementtypeId[]', \Tk\Form\Field\Option\ArrayObjectIterator::create($list)))->addCss('tk-dual-select')->setAttr('data-title', 'Placement Types')->setNotes('Enable this collection for the selected placement types.');



        $this->form->addField(new Field\Checkbox('active'))->setNotes('Enable this collection for user submissions.');
        $this->form->addField(new Field\Checkbox('viewGrade'))->setNotes('Allow students to view their course results from all entries from this collection.');
        $this->form->addField(new Field\Checkbox('includeZero'))->setNotes('Should the zero values be included in the weighted average calculation.');

        $this->form->addField(new Field\Input('confirm'))->setNotes('If enabled, the user will be prompted with the given text before they can submit their entry.');
        $this->form->addField(new Field\Textarea('instructions'))->addCss('mce')->setNotes('Enter any student instructions on how to complete placement entries.');
        $this->form->addField(new Field\Textarea('notes'))->addCss('tkTextareaTool')->setNotes('Staff only notes that can only be vied in this edit screen.');

        if ($this->collection->getId())
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
        \Skill\Db\CollectionMap::create()->mapForm($form->getValues(), $this->collection);


        $form->addFieldErrors($this->collection->validate());

        if ($form->hasErrors()) {
            return;
        }
        $this->collection->save();


        \Tk\Alert::addSuccess('Record saved!');
        if ($form->getTriggeredEvent()->getName() == 'update') {
            \App\Factory::getCrumbs()->getBackUrl()->redirect();
        }
        \Tk\Uri::create()->set('collectionId', $this->collection->getId())->redirect();
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();
        if ($this->collection->getId()) {
            $this->getActionPanel()->addButton(\Tk\Ui\Button::create('Domains', \Tk\Uri::create('/skill/domainManager.html')->set('collectionId', $this->collection->getId()), 'fa fa-black-tie'));
            $this->getActionPanel()->addButton(\Tk\Ui\Button::create('Categories', \Tk\Uri::create('/skill/categoryManager.html')->set('collectionId', $this->collection->getId()), 'fa fa-folder-o'));
            $this->getActionPanel()->addButton(\Tk\Ui\Button::create('Scale', \Tk\Uri::create('/skill/scaleManager.html')->set('collectionId', $this->collection->getId()), 'fa fa-balance-scale'));
            $this->getActionPanel()->addButton(\Tk\Ui\Button::create('Items', \Tk\Uri::create('/skill/itemManager.html')->set('collectionId', $this->collection->getId()), 'fa fa-question'));
        }

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
      <h4 class="panel-title"><i class="fa fa-graduation-cap"></i> <span var="panel-title">Skill Collection Edit</span></h4>
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