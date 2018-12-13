<?php
namespace Skill\Controller\Scale;

use App\Controller\AdminEditIface;
use Dom\Template;
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
     * @var \Skill\Db\Scale
     */
    protected $scale = null;



    /**
     * Iface constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Skill Scale Edit');
    }

    /**
     *
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->scale = new \Skill\Db\Scale();
        $this->scale->collectionId = (int)$request->get('collectionId');
        if ($request->get('scaleId')) {
            $this->scale = \Skill\Db\ScaleMap::create()->find($request->get('scaleId'));
        }

        $this->buildForm();

        $this->form->load(\Skill\Db\ScaleMap::create()->unmapForm($this->scale));
        $this->form->execute($request);
    }

    /**
     * @throws \Exception
     */
    protected function buildForm() 
    {
        $this->form = \Uni\Config::getInstance()->createForm('scaleEdit');
        $this->form->setRenderer(\Uni\Config::getInstance()->createFormRenderer($this->form));

        // text, textblock, select, checkbox, date, file(????)
        $this->form->appendField(new Field\Input('name'))->setNotes('');
        //$this->form->appendField(new Field\Input('value'))->setNotes('');
        $this->form->appendField(new Field\Input('description'))->setNotes('A short description');

        $this->form->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->form->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->form->appendField(new Event\Link('cancel', $this->getConfig()->getBackUrl()));

    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        // Load the object with data from the form using a helper object
        \Skill\Db\ScaleMap::create()->mapForm($form->getValues(), $this->scale);

        $form->addFieldErrors($this->scale->validate());

        if ($form->hasErrors()) {
            return;
        }
        $this->scale->save();

        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getConfig()->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->set('scaleId', $this->scale->getId()));
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
      <h4 class="panel-title"><i class="fa fa-balance-scale"></i> <span var="panel-title">Skill Scale Edit</span></h4>
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