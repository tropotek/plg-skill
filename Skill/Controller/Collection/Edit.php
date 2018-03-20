<?php
namespace Skill\Controller\Collection;

use App\Controller\AdminEditIface;
use Dom\Template;
use Skill\Db\CollectionMap;
use Tk\Form\Event;
use Tk\Form\Field;
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
        parent::__construct();
        $this->setPageTitle('Skill Collection Edit');
    }

    /**
     * @param Request $request
     * @throws \Exception
     * @throws \Tk\Db\Exception
     * @throws \Tk\Form\Exception
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

    /**
     * @throws \ReflectionException
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     * @throws \Tk\Form\Exception
     */
    protected function buildForm() 
    {
        $this->form = \App\Config::getInstance()->createForm('collectionEdit');
        $this->form->setRenderer(\App\Config::getInstance()->createFormRenderer($this->form));

        $tab = 'Details';
        $this->form->addField(new Field\Input('name'))->setTabGroup($tab)->setNotes('Create a label for this collection');
        $list = \Tk\Form\Field\Select::arrayToSelectList(\Tk\Object::getClassConstants('\Skill\Db\Collection', 'ROLE'));
        $this->form->addField(new Field\Select('role', $list))->setTabGroup($tab)->prependOption('-- Select --', '')
            ->setNotes('');

        $this->form->addField(new Field\Input('icon'))->setTabGroup($tab)
            ->setNotes('TODO: Create a jquery plugin to select icons.... Select an Icon for this collection.');

        $list = array('fa fa-eye', 'fa fa-check', 'fa fa-commenting-o', 'fa fa-cutlery', 'fa fa-desktop', 'fa fa-drivers-license'
        , 'fa fa-question', 'fa fa-database', 'fa fa-cut', 'fa fa-euro', 'fa fa-cube', 'fa fa-crop', 'tk tk-goals');
        $this->form->addField(new Field\Select('icon', Field\Select::arrayToSelectList($list, false)))->setTabGroup($tab)
            ->addCss('iconpicker')->setNotes('Select an icon for this collection');


        $this->form->addField(new Field\Input('color'))->setAttr('type', 'color')->setTabGroup($tab)
            ->setNotes('Select a color scheme for this collection');

        $this->form->addField(new Field\Checkbox('active'))->setTabGroup($tab)
            ->setNotes('Enable this collection for user submissions.');
        $this->form->addField(new Field\Checkbox('gradable'))->setTabGroup($tab)
            ->setNotes('Calculate totals for all results. If enabled then the student can view a summary of the results. (This can be enabled in the subject settings page)');
        $this->form->addField(new Field\Checkbox('includeZero'))->setTabGroup($tab)
            ->setNotes('Should the zero values be included in the weighted average calculation.');

        $this->form->addField(new Field\Input('confirm'))->setTabGroup($tab)
            ->setNotes('If enabled, the user will be prompted with the given text before they can submit their entry.');
        $this->form->addField(new Field\Textarea('instructions'))->setTabGroup($tab)
            ->addCss('mce')->setNotes('Enter any student instructions on how to complete placement entries.');
        $this->form->addField(new Field\Textarea('notes'))->setTabGroup($tab)
            ->addCss('tkTextareaTool')->setNotes('Staff only notes that can only be vied in this edit screen.');


        $tab = 'Placement';

        $this->form->addField(new Field\Checkbox('requirePlacement'))->addCss('tk-input-toggle')->setTabGroup($tab)
            ->setNotes('If a collection entry requires a placement to be associated with.');

        $list = \Tk\Form\Field\Select::arrayToSelectList(\Tk\Object::getClassConstants('\App\Db\Placement', 'STATUS'));
        $this->form->addField(new Field\Select('available[]', $list))->setTabGroup($tab)
            ->addCss('tk-dual-select')->setAttr('data-title', 'Placement Status')
            ->setNotes('Enable this collection on the following placement status');

        $list = \App\Db\PlacementTypeMap::create()->findFiltered(array('profileId' => $this->collection->getProfile()->getId()));
        $ptiField = $this->form->addField(new Field\Select('placementTypeId[]', \Tk\Form\Field\Option\ArrayObjectIterator::create($list)))
            ->setTabGroup($tab)->addCss('tk-dual-select')->setAttr('data-title', 'Placement Types')
            ->setNotes('Enable this collection for the selected placement types.');
        $list = \Skill\Db\CollectionMap::create()->findPlacementTypes($this->collection->getId());
        $ptiField->setValue($list);


        if ($this->collection->getId())
            $this->form->addField(new Event\Submit('update', array($this, 'doSubmit')));

        $this->form->addField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->form->addField(new Event\Link('cancel', \Uni\Ui\Crumbs::getInstance()->getBackUrl()));

    }

    /**
     * @param \Tk\Form $form
     * @throws \ReflectionException
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function doSubmit($form)
    {
        // Load the object with data from the form using a helper object
        \Skill\Db\CollectionMap::create()->mapForm($form->getValues(), $this->collection);
        $placemenTypeIds = $form->getFieldValue('placementTypeId');

        if($this->collection->requirePlacement && !count($placemenTypeIds)) {
            $form->addFieldError('placementTypeId', 'Please select at least one placement type for this collection to be enabled for.');
        }

        $form->addFieldErrors($this->collection->validate());

        if ($form->hasErrors()) {
            return;
        }
        $this->collection->save();

        \Skill\Db\CollectionMap::create()->removePlacementType($this->collection->getId());
        if (count($placemenTypeIds)) {
            foreach ($placemenTypeIds as $placementTypeId) {
                \Skill\Db\CollectionMap::create()->addPlacementType($this->collection->getId(), $placementTypeId);
            }
        }

        \Tk\Alert::addSuccess('Record saved!');
        if ($form->getTriggeredEvent()->getName() == 'update') {
            \Uni\Ui\Crumbs::getInstance()->getBackUrl()->redirect();
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
            $this->getActionPanel()->add(\Tk\Ui\Button::create('Domains', \Tk\Uri::create('/skill/domainManager.html')->set('collectionId', $this->collection->getId()), 'fa fa-black-tie'));
            $this->getActionPanel()->add(\Tk\Ui\Button::create('Categories', \Tk\Uri::create('/skill/categoryManager.html')->set('collectionId', $this->collection->getId()), 'fa fa-folder-o'));
            $this->getActionPanel()->add(\Tk\Ui\Button::create('Scale', \Tk\Uri::create('/skill/scaleManager.html')->set('collectionId', $this->collection->getId()), 'fa fa-balance-scale'));
            $this->getActionPanel()->add(\Tk\Ui\Button::create('Items', \Tk\Uri::create('/skill/itemManager.html')->set('collectionId', $this->collection->getId()), 'fa fa-question'));
        }

        // Render the form
        $template->insertTemplate('form', $this->form->getRenderer()->show());





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