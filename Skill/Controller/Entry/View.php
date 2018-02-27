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
class View extends AdminEditIface
{

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
        $this->setPageTitle('Skill Entry View');
        if ($this->getUser()->isStudent()) {
            $this->getActionPanel()->setEnabled(false);
        }
    }

    /**
     *
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->entry = \Skill\Db\EntryMap::create()->find($request->get('entryId'));

        $this->buildForm();

        $this->form->load(\Skill\Db\EntryMap::create()->unmapForm($this->entry));
        $this->form->execute($request);

    }

    /**
     *
     */
    protected function buildForm() 
    {
        $this->form = \App\Config::getInstance()->createForm('entryEdit');
        $this->form->setRenderer(\App\Config::getInstance()->createFormRenderer($this->form));

        $this->form->addField(new Field\Html('title', htmlentities($this->entry->title)))->setFieldset('Entry Details');
        //if($this->entry->getCollection()->gradable && $this->getUser()->isStaff()) {
        if($this->entry->getCollection()->gradable) {
            $pct = round(($this->entry->average/($this->entry->getCollection()->getScaleLength()-1))*100);
            $this->form->addField(new Field\Html('average', sprintf('%.2f &nbsp; (%d%%)', $this->entry->average, $pct)))->setFieldset('Entry Details');
            if ($this->getUser()->isStaff()) {
                $pct = round(($this->entry->weightedAverage / ($this->entry->getCollection()->getScaleLength() - 1)) * 100);
                $this->form->addField(new Field\Html('weightedAverage', sprintf('%.2f &nbsp; (%d%%)', $this->entry->weightedAverage, $pct)))->setFieldset('Entry Details');
            }
        }


        $this->form->addField(new Field\Html('status'))->setFieldset('Entry Details');
        $this->form->addField(new Field\Html('assessor'))->setFieldset('Entry Details');
        if ($this->entry->getCollection()->requirePlacement)
            $this->form->addField(new Field\Html('absent'))->setLabel('Days Absent')->setFieldset('Entry Details');

        if ($this->entry->getCollection()->confirm && $this->getUser()->isStaff()) {
            $s = ($this->entry->confirm === null) ? '' : ($this->entry->confirm ? 'Yes' : 'No');
            $this->form->addField(new Field\Html('confirm', $s))->setFieldset('Entry Details')->setNotes($this->entry->getCollection()->confirm);
        }
        if ($this->entry->notes)
            $this->form->addField(new Field\Html('notes', htmlentities($this->entry->notes)))->setLabel('Comments')->setFieldset('Entry Details');

        $items = \Skill\Db\ItemMap::create()->findFiltered(array('collectionId' => $this->entry->getCollection()->getId()),
            \Tk\Db\Tool::create('category_id, order_by'));

        /** @var \Skill\Db\Item $item */
        foreach ($items as $item) {
            $fld = $this->form->addField(new \Skill\Form\Field\Item($item))->setLabel(null)->setDisabled();
            $val = \Skill\Db\EntryMap::create()->findValue($this->entry->getId(), $item->getId());
            if ($val)
                $fld->setValue($val->value);
        }

    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();
        $template->insertText('panel-title', $this->entry->getCollection()->name . ' View');
        if ($this->entry->getCollection()->icon) {
            $template->setAttr('icon', 'class', $this->entry->getCollection()->icon);
        }

        // Render the form
        $template->insertTemplate('form', $this->form->getRenderer()->show());

        $template->appendCssUrl(\Tk\Uri::create('/plugin/ems-skill/assets/skill.less'));
        $template->appendJsUrl(\Tk\Uri::create('/plugin/ems-skill/assets/skill.js'));

        $css = <<<CSS
.form-group.tk-item:nth-child(odd) .skill-item {
  background-color: {$this->entry->getCollection()->color};
}
.tk-form fieldset:first-child legend {
  display: none !important;
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
      <h4 class="panel-title"><i class="fa fa-eye" var="icon"></i> <span var="panel-title">Skill Entry View</span></h4>
    </div>
    <div class="panel-body">
      <div var="instructions"></div>
      <div var="form"></div>
    </div>
  </div>
  
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}