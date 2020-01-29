<?php
namespace Skill\Form;

use Tk\Form;
use Tk\Form\Event;
use Tk\Form\Field;

/**
 * Example:
 * <code>
 *   $form = new CompanyCategory::create();
 *   $form->setModel($obj);
 *   $formTemplate = $form->getRenderer()->show();
 *   $template->appendTemplate('form', $formTemplate);
 * </code>
 * 
 * @author Mick Mifsud
 * @created 2019-06-06
 * @link http://tropotek.com.au/
 * @license Copyright 2019 Tropotek
 */
class EntryView extends \App\FormIface
{

    /**
     * @throws \Exception
     */
    public function init()
    {
        $user = $this->getConfig()->getAuthUser();
        $this->addCss('skill-entry-view');
        $this->addCss('form-horizontal');

        $this->appendField(new Field\Html('title', htmlentities($this->getEntry()->title)))
            ->addCss('form-control form-control-static form-control-plaintext')->setFieldset('Entry Details');

        if($this->getEntry()->getCollection()->gradable) {
            $pct = round(($this->getEntry()->calcAverage()/($this->getEntry()->getCollection()->getScaleCount()))*100);
            $this->appendField(new Field\Html('average', sprintf('%.2f &nbsp; (%d%%)', $this->getEntry()->calcAverage(), $pct)))
                ->addCss('form-control form-control-static form-control-plaintext')->setFieldset('Entry Details');
        }

        $this->appendField(new Field\Html('status'))
            ->addCss('form-control form-control-static form-control-plaintext')->setFieldset('Entry Details');
        $this->appendField(new Field\Html('assessor', htmlentities($this->getEntry()->assessor)))
            ->addCss('form-control form-control-static form-control-plaintext')->setFieldset('Entry Details');
        if ($this->getEntry()->getCollection()->requirePlacement)
            $this->appendField(new Field\Html('absent'))->setLabel('Days Absent')
                ->addCss('form-control form-control-static form-control-plaintext')->setFieldset('Entry Details');

        if ($this->getEntry()->getCollection()->confirm && $this->getAuthUser()->isStaff()) {
            $s = ($this->getEntry()->confirm === null) ? '' : ($this->getEntry()->confirm ? 'Yes' : 'No');
            $this->appendField(new Field\Html('confirm', $s))
                ->addCss('form-control form-control-static form-control-plaintext')
                ->setFieldset('Entry Details')
                ->setNotes($this->getEntry()->getCollection()->confirm);
        }
        if ($this->getEntry()->notes)
            $this->appendField(new Field\Html('notes', htmlentities($this->getEntry()->notes)))->setLabel('Comments')
                ->addCss('form-control form-control-static form-control-plaintext')
                ->setFieldset('Entry Details');

        $items = \Skill\Db\ItemMap::create()->findFiltered(array('collectionId' => $this->getEntry()->getCollection()->getId()),
            \Tk\Db\Tool::create('category_id, order_by'));

        /** @var \Skill\Db\Item $item */
        foreach ($items as $item) {
            $fld = $this->appendField(new \Skill\Form\Field\Item($item))->setLabel(null)->setDisabled();
            $val = \Skill\Db\EntryMap::create()->findValue($this->getEntry()->getId(), $item->getId());
            if ($val)
                $fld->setValue($val->value);
        }

        $template = $this->getRenderer()->getTemplate();
        $template->appendCssUrl(\Tk\Uri::create('/plugin/plg-skill/assets/skill.less'));
        $template->appendJsUrl(\Tk\Uri::create('/plugin/plg-skill/assets/skill.js'));

        $css = <<<CSS
/*.tk-form fieldset:first-child legend {*/
/*  display: none !important;*/
/*}*/
.skill-group .form-row:nth-child(even) {
  background-color: {$this->getEntry()->getCollection()->color};
}
/*form.tk-form .skill-group .form-row {*/
/*  margin: 0;*/
/*}*/
/*.skill-group .form-row .skill-item-name {*/
/*  vertical-align: middle;*/
/*  padding-top: 20px;*/
/*}*/
/*.skill-group .form-row .skill-input {*/
/*  padding-top: 10px;*/
/*}*/
/*.skill-group .form-row span.uid {*/
/*  display: inline-block;*/
/*  margin-right: 10px;*/
/*}*/
/*.skill-group .error-block { display: none !important; position: absolute !important; }*/
CSS;
        $template->appendCss($css);

    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function execute($request = null)
    {
        $this->load(\Skill\Db\EntryMap::create()->unmapForm($this->getEntry()));
        parent::execute($request);
    }

    /**
     * @param Form $form
     * @param Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event) { }

    /**
     * @return \Tk\Db\ModelInterface|\Skill\Db\Entry
     */
    public function getEntry()
    {
        return $this->getModel();
    }

    /**
     * @param \Skill\Db\Entry $entry
     * @return $this
     */
    public function setEntry($entry)
    {
        return $this->setModel($entry);
    }
    
}