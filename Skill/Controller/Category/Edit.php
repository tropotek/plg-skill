<?php
namespace Skill\Controller\Category;

use Dom\Template;
use Tk\Form\Event;
use Tk\Form\Field;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Edit extends \App\Controller\AdminEditIface
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
     * @throws \Exception
     * @throws \Tk\Db\Exception
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

    /**
     * @throws \Tk\Db\Exception
     * @throws \Tk\Form\Exception
     */
    protected function buildForm() 
    {
        $this->form = \App\Config::getInstance()->createForm('categoryEdit');
        $this->form->setRenderer(\App\Config::getInstance()->createFormRenderer($this->form));

        $this->form->addField(new Field\Input('name'))->setNotes('');
        $this->form->addField(new Field\Checkbox('publish'))->setNotes('is this category contents visible to students');
        $this->form->addField(new Field\Textarea('description'))->addCss('tkTextareaTool')->setNotes('A short description of the category');

        $this->form->addField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->form->addField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->form->addField(new Event\Link('cancel', $this->getConfig()->getBackUrl()));
    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \ReflectionException
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function doSubmit($form, $event)
    {
        // Load the object with data from the form using a helper object
        \Skill\Db\CategoryMap::create()->mapForm($form->getValues(), $this->category);

        $form->addFieldErrors($this->category->validate());

        if ($form->hasErrors()) {
            return;
        }
        $this->category->save();

        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getConfig()->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->set('categoryId', $this->category->getId()));
        }
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

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