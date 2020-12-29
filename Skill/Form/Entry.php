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
class Entry extends \App\FormIface
{
    const MODE_PRIVATE = 'private';
    const MODE_PUBLIC = 'public';

    protected $mode = 'private';

    /**
     * @throws \Exception
     */
    public function init()
    {
        $user = $this->getConfig()->getAuthUser();
        $this->addCss('skill-entry-edit');

        if ($this->getEntry()->isSelfAssessment()) {
            $this->appendField(new Field\Html('title'))->setFieldset('Entry Details');
        } else {
            $f = $this->appendField(new Field\Input('title'))->setFieldset('Entry Details');
            if ($this->getEntry()->getPlacement() && $this->isPublic()) {
                $f->setReadonly();
            }
        }

        if ($this->getEntry()->getId() && $this->getEntry()->getCollection()->gradable && !$this->isPublic()) {
            $avg = $this->getEntry()->calcAverage();
            $avg = \Skill\Db\EntryMap::create()->getEntryAverage($this->getEntry()->getId());
            $ratio = \Skill\Db\EntryMap::create()->getEntryRatio($this->getEntry()->getId()) * 100;
            //$pct = round(($avg / ($this->entry->getCollection()->getScaleCount() - 1)) * 100);
            $this->appendField(new Field\Html('average', sprintf('%.2f &nbsp; (%d%%)', $avg, $ratio)))->setFieldset('Entry Details');
        }

        $urlRole = \Uni\Uri::create()->getRoleType($this->getConfig()->getUserTypeList());
        if ($user && $user->isStaff() && $user->hasType($urlRole)) {
            $this->appendField(new \App\Form\Field\StatusSelect('status', \Skill\Db\Entry::getStatusList()))
                ->setRequired()->prependOption('-- Status --', '')->setNotes('Set the status. Use the checkbox to disable notification emails.')->setFieldset('Entry Details');
        } else {
            $this->appendField(new \Tk\Form\Field\Html('status'))->setFieldset('Entry Details');
        }

        if (!$this->getEntry()->isSelfAssessment()) {
            $this->appendField(new Field\Input('assessor'))->setFieldset('Entry Details')->setRequired();
            $this->appendField(new Field\Input('absent'))->setFieldset('Entry Details')->setNotes('Enter the number of days absent if any.');
        }

        $this->appendField(new Field\Textarea('notes'))->addCss('')->setLabel('Comments')->setFieldset('Entry Details');


        $items = \Skill\Db\ItemMap::create()->findFiltered(array('collectionId' => $this->getEntry()->getCollection()->getId()),
            \Tk\Db\Tool::create('category_id, order_by'));
        /** @var \Skill\Db\Item $item */
        foreach ($items as $item) {
            $fld = $this->appendField(new \Skill\Form\Field\Item($item))->setLabel(null);
            $val = \Skill\Db\EntryMap::create()->findValue($this->getEntry()->getId(), $item->getId());
            if ($val) {
                $fld->setValue($val->value);
            }
        }

        if ($this->getEntry()->getCollection()->getConfirm()) {
            $radioBtn = new \Tk\Form\Field\RadioButton('confirm', $this->getEntry()->getCollection()->confirm);
            $radioBtn->appendOption('Yes', '1', 'fa fa-check')->appendOption('No', '0', 'fa fa-ban');
            $this->appendField($radioBtn)->setLabel(null)->setFieldset('Confirmation')->setValue(true);
        }

        if ($this->isPublic()) {
            $this->appendField(new Event\Submit('submit', array($this, 'doSubmit')))->addCss('btn-success')->setIconRight('fa fa-arrow-right')->addCss('pull-right')->setLabel('Submit ');
            $this->appendField(new Event\Link('cancel', \Uni\Uri::create('/index.html')));
        } else {
            $this->appendField(new Event\Submit('update', array($this, 'doSubmit')));
            $this->appendField(new Event\Submit('save', array($this, 'doSubmit')));
            $this->appendField(new Event\Link('cancel', $this->getConfig()->getBackUrl()));
        }

        $template = $this->getRenderer()->getTemplate();
        $template->appendCssUrl(\Tk\Uri::create('/plugin/plg-skill/assets/skill.less'));
        $template->appendJsUrl(\Tk\Uri::create('/plugin/plg-skill/assets/skill.js'));

        $css = <<<CSS
.skill-group .form-row:nth-child(even) {
  background-color: {$this->getEntry()->getCollection()->color};
}
/*.tk-form fieldset:first-child legend {*/
/*  display: none !important;*/
/*}*/
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
CSS;
        $template->appendCss($css);

        $js = <<<JS
jQuery(function ($) {
  if (config.roleType === 'staff' && $('fieldset.skill-group').length > 0) {
    $('.skill-entry-edit .tk-form-events').clone(true).appendTo($('.skill-entry-edit fieldset.EntryDetails'));
  }
});
JS;
        $template->appendJs($js);

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
    public function doSubmit($form, $event)
    {
        // Load the object with data from the form using a helper object
        \Skill\Db\EntryMap::create()->mapForm($form->getValues(), $this->getEntry());

        if (!$this->isPublic()) {
//            if ($form->getField('status') && (!$form->getFieldValue('status') || !in_array($form->getFieldValue('status'),
//                        \Tk\ObjectUtil::getClassConstants($this->getEntry(), 'STATUS')))) {
//                $form->addFieldError('status', 'Please Select a valid status');
//            }
        } else {
            $this->getEntry()->setStatus(\Skill\Db\Entry::STATUS_PENDING);
        }

        $hasValue = false;
        foreach ($form->getValues('/^item\-/') as $name => $val) {
            if ($val > 0) $hasValue = true;
        }
        $items = \Skill\Db\ItemMap::create()->findFiltered(array('collectionId' => $this->getEntry()->getCollection()->getId()),
            \Tk\Db\Tool::create('category_id, order_by'));
        if (!$items->count()) {
            $hasValue = true;
        }
        if (!$hasValue) {
            $form->addError('Use the slider at the end of the question to leave feedback.');
        }

        $form->addFieldErrors($this->getEntry()->validate());
        if ($form->hasErrors()) {
            return;
        }

        // Save Item values
        \Skill\Db\EntryMap::create()->removeValue($this->getEntry()->getVolatileId());
        foreach ($form->getValues('/^item\-/') as $name => $val) {
            $id = (int)substr($name, strrpos($name, '-') + 1);
            \Skill\Db\EntryMap::create()->saveValue($this->getEntry()->getVolatileId(), $id, (int)$val);
        }

        // TODO: Although this seems redundant, there was a bug where the getEntry()->userId == placement->id (try to trace?)
        $this->getEntry()->setUserId($this->getEntry()->getPlacement()->getUserId());
        $this->getEntry()->setStatusNotify(true);
        $this->getEntry()->save();

        \Tk\Alert::addSuccess('You response has been successfully submitted. Please return at any time to make changes while this Entry remains in the pending status.');
        $url = \Tk\Uri::create()->set('entryId', $this->getEntry()->getId());
        if ($form->getTriggeredEvent()->getName() == 'update') {
            $url = $this->getConfig()->getBackUrl();
            if ($this->getEntry()->getPlacement() && $this->getConfig()->isSubjectUrl()) {
                $url = \Uni\Uri::createSubjectUrl('/placementEdit.html')->set('placementId', $this->getEntry()->getPlacement()->getId());
            }
        }
        $event->setRedirect($url);
    }

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

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * use self::MODE_PUBLIC, self::MODE_PRIVATE
     *
     * @param string $mode
     * @return Entry
     */
    public function setMode(string $mode): Entry
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return ($this->getMode() == self::MODE_PUBLIC);
    }
    
}