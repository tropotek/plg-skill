<?php
namespace Skill\Controller\Category;

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
     * @var \Skill\Db\Category
     */
    protected $category = null;



    /**
     * Iface constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Skill Category Edit');
    }

    /**
     *
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
        $this->category = new \Skill\Db\Category();
        $this->category->collectionId = (int)$request->get('collectionId');
        if ($request->get('categoryId')) {
            $this->category = \Skill\Db\CategoryMap::create()->find($request->get('categoryId'));
        }

        $this->buildForm();

        $this->form->load(\Skill\Db\CategoryMap::create()->unmapForm($this->category));
        $this->form->execute($request);
    }


    protected function buildForm() 
    {
        $this->form = \App\Factory::createForm('categoryEdit');
        $this->form->setRenderer(\App\Factory::createFormRenderer($this->form));

        $this->form->addField(new Field\Input('name'))->setNotes('');
        $this->form->addField(new Field\Checkbox('publish'))->setNotes('is this category contents visible to students');
        $this->form->addField(new Field\Textarea('description'))->addCss('tkTextareaTool')->setNotes('A short description of the category');

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
        \Skill\Db\CategoryMap::create()->mapForm($form->getValues(), $this->category);

        $form->addFieldErrors($this->category->validate());

        if ($form->hasErrors()) {
            return;
        }
        $this->category->save();

        \Tk\Alert::addSuccess('Record saved!');
        if ($form->getTriggeredEvent()->getName() == 'update') {
            \App\Factory::getCrumbs()->getBackUrl()->redirect();
        }
        \Tk\Uri::create()->set('categoryId', $this->category->getId())->redirect();
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        $template->insertTemplate('form', $this->form->getRenderer()->show()->getTemplate());

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
      <h4 class="panel-title"><i class="fa fa-folder-o"></i> <span var="panel-title">Skill Category Edit</span></h4>
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