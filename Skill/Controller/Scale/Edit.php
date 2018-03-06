<?php
namespace Skill\Controller\Scale;

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
     * @throws \Tk\Db\Exception
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


    protected function buildForm() 
    {
        $this->form = \App\Config::getInstance()->createForm('scaleEdit');
        $this->form->setRenderer(\App\Config::getInstance()->createFormRenderer($this->form));

        // text, textblock, select, checkbox, date, file(????)
        $this->form->addField(new Field\Input('name'))->setNotes('');
        //$this->form->addField(new Field\Input('value'))->setNotes('');
        $this->form->addField(new Field\Input('description'))->setNotes('A short description');

        $this->form->addField(new Event\Button('update', array($this, 'doSubmit')));
        $this->form->addField(new Event\Button('save', array($this, 'doSubmit')));
        $this->form->addField(new Event\Link('cancel', \Uni\Ui\Crumbs::getInstance()->getBackUrl()));

    }

    /**
     * @param \Tk\Form $form
     */
    public function doSubmit($form)
    {
        // Load the object with data from the form using a helper object
        \Skill\Db\ScaleMap::create()->mapForm($form->getValues(), $this->scale);

        $form->addFieldErrors($this->scale->validate());

        if ($form->hasErrors()) {
            return;
        }
        $this->scale->save();

        \Tk\Alert::addSuccess('Record saved!');
        if ($form->getTriggeredEvent()->getName() == 'update') {
            \Uni\Ui\Crumbs::getInstance()->getBackUrl()->redirect();
        }
        \Tk\Uri::create()->set('scaleId', $this->scale->getId())->redirect();
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