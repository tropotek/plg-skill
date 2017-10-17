<?php
namespace Skill\Controller\Domain;

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
     * @var \Skill\Db\Domain
     */
    protected $domain = null;



    /**
     * Iface constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Skill Domain Edit');
    }

    /**
     *
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
        $this->domain = new \Skill\Db\Domain();
        $this->domain->collectionId = (int)$request->get('collectionId');
        if ($request->get('domainId')) {
            $this->domain = \Skill\Db\DomainMap::create()->find($request->get('domainId'));
        }

        $this->buildForm();

        $this->form->load(\Skill\Db\DomainMap::create()->unmapForm($this->domain));
        $this->form->execute($request);
    }


    protected function buildForm() 
    {
        $this->form = \App\Factory::createForm('domainEdit');
        $this->form->setParam('renderer', \App\Factory::createFormRenderer($this->form));

        // text, textblock, select, checkbox, date, file(????)
        $this->form->addField(new Field\Input('name'))->setNotes('');
        $this->form->addField(new Field\Input('label'))->setNotes('Create a short label for this domain');
        $this->form->addField(new Field\Input('weight'))->setNotes('for weighted marks ad a multiplier value here from 0.0 to 1.0');
        $this->form->addField(new Field\Input('description'))->setNotes('A short description of the domain');

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
        \Skill\Db\DomainMap::create()->mapForm($form->getValues(), $this->domain);

        $form->addFieldErrors($this->domain->validate());

        if ($form->hasErrors()) {
            return;
        }
        $this->domain->save();

        \Tk\Alert::addSuccess('Record saved!');
        if ($form->getTriggeredEvent()->getName() == 'update') {
            \App\Factory::getCrumbs()->getBackUrl()->redirect();
        }
        \Tk\Uri::create()->set('domainId', $this->domain->getId())->redirect();
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
      <h4 class="panel-title"><i class="fa fa-black-tie"></i> <span var="panel-title">Skill Domain Edit</span></h4>
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