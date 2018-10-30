<?php

namespace Skill\Controller\Entry;

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
     * @var \Skill\Db\Entry
     */
    protected $entry = null;
    /**
     * @var \App\Db\Placement
     */
    protected $placement = null;

    /**
     * @var \App\Ui\Table\Status
     */
    protected $statusTable = null;

    /**
     * @var bool
     */
    protected $isPublic = false;

    /**
     * @var array
     */
    protected $errors = array();


    /**
     * Iface constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Skill Entry Edit');
        if ($this->getUser()->isStudent()) {
            $this->getActionPanel()->setEnabled(false);
        }
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doPublicSubmission(Request $request)
    {
        $this->isPublic = true;
        $this->getActionPanel()->setEnabled(false);
        $this->setTemplate($this->__makePublicTemplate());
        $this->doDefault($request);
    }

    /**
     * @param \Skill\Db\Entry $entry
     * @return bool
     * @throws \Exception
     */
    public function isSelfAssessment($entry)
    {
        if (!$entry->getCollection()->gradable && !$entry->getCollection()->requirePlacement &&
            $entry->getCollection()->role == \Skill\Db\Collection::ROLE_STUDENT) {
            return true;
        }
        return false;
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->entry = new \Skill\Db\Entry();
        $this->entry->userId = ($request->has('userId')) ? (int)$request->get('userId') : $this->getUser()->getId();
        $this->entry->subjectId = (int)$request->get('subjectId');
        $this->entry->collectionId = (int)$request->get('collectionId');
        $this->entry->placementId = (int)$request->get('placementId');

        if ($request->get('entryId')) {
            $this->entry = \Skill\Db\EntryMap::create()->find($request->get('entryId'));
        }
        if (preg_match('/[0-9a-f]{32}/i', $request->get('h'))) {
            // EG: h=13644394c4d1473f1547513fc21d7934
            // http://ems.vet.unimelb.edu.au/goals.html?h=13644394c4d1473f1547513fc21d7934&collectionId=2
            $this->placement = \App\Db\PlacementMap::create()->findByHash($request->get('h'));
            if (!$this->placement) {
                \Tk\Alert::addError('Invalid URL. Please contact your course coordinator.');
                $this->getUser()->getHomeUrl()->redirect();
            }
            $e = \Skill\Db\EntryMap::create()->findFiltered(array(
                    'collectionId' => $request->get('collectionId'),
                    'placementId' => $this->placement->getId()
                )
            )->current();

            if ($e) {
                $this->entry = $e;
            } else {
                $this->entry->placementId = $this->placement->getId();
                $this->entry->userId = $this->placement->userId;
                $this->entry->subjectId = $this->placement->subjectId;
//                // TODO: Remove this once all old EMS II email urls are no longer valid, sometime after June 2018
//                if (!$this->entry->collectionId)
//                    $this->entry->collectionId = 1; // This should be supplied in the request.
                if (!$this->entry->collectionId) {
                    throw new \Tk\Exception('Invalid collection ID. Please contact the site Administrator.');
                }
            }
        }
        if (!$this->entry->subjectId && $this->getSubject()) {
            $this->entry->subjectId = $this->getSubject()->getId();
        }

        if ($request->get('collectionId') && $request->get('placementId')) {
            $e = \Skill\Db\EntryMap::create()->findFiltered(array(
                    'collectionId' => $request->get('collectionId'),
                    'placementId' => $request->get('placementId'))
            )->current();
            if ($e)
                $this->entry = $e;
        }

        if ($request->get('collectionId') && $request->get('userId') && $this->getUser()->isStaff()) {          // Staff view student self assessment
            $e = \Skill\Db\EntryMap::create()->findFiltered(array(
                    'collectionId' => $request->get('collectionId'),
                    'userId' => $request->get('userId'))
            )->current();
            if ($e)
                $this->entry = $e;
        }

        if (!$request->has('userId') && !$request->has('subjectId') && $this->getUser()->isStudent()) {         // Assumed to be student self assessment form
            $e = \Skill\Db\EntryMap::create()->findFiltered(array(
                    'collectionId' => $this->entry->collectionId,
                    'subjectId' => $this->entry->subjectId,
                    'userId' => $this->entry->userId)
            )->current();
            if ($e)
                $this->entry = $e;
        }

        if ($this->isPublic) {
            if ($this->entry->status == \Skill\Db\Entry::STATUS_APPROVED || $this->entry->status == \Skill\Db\Entry::STATUS_NOT_APPROVED) {
                $this->errors[] = 'This entry has already been submitted.';
                return;
            }
            if ($this->entry->getPlacement() && !$this->entry->getCollection()->isAvailable($this->entry->getPlacement())) {
                $this->errors[] = 'This entry is no longer available.';
                return;
            }
        }

//        if ($this->entry->getId()) {
//            $this->getActionPanel()->add(\Tk\Ui\Button::create('View', \App\Uri::createSubjectUrl('/entryView.html')->set('entryId', $this->entry->getId()), 'fa fa-eye'));
//        }

        if (!$this->entry->getId() && $this->entry->getPlacement()) {
            $this->entry->title = $this->entry->getPlacement()->getTitle(true);
            if ($this->entry->getPlacement()->getCompany()) {
                $this->entry->assessor = $this->entry->getPlacement()->getCompany()->name;
            }
            if ($this->entry->getPlacement()->getSupervisor())
                $this->entry->assessor = $this->entry->getPlacement()->getSupervisor()->name;
        }

        if ($this->isSelfAssessment($this->entry) && !$this->entry->getId()) {
            $this->entry->title = $this->entry->getCollection()->name . ' for ' . $this->entry->getUser()->getName();
            $this->entry->assessor = $this->entry->getUser()->getName();
        }

        $this->buildForm();

        $this->form->load(\Skill\Db\EntryMap::create()->unmapForm($this->entry));
        $this->form->execute($request);

        if ($this->getUser()->isStaff()) {
            $this->statusTable = new \App\Ui\Table\Status(\App\Uri::createSubjectUrl('/mailLogManager.html'));
            if ($this->entry->getId()) {
                $filter = $this->statusTable->getTable()->getFilterValues();
                $filter['model'] = $this->entry;
                $filter['subjectId'] = $this->entry->subjectId;
                $list = \App\Db\StatusMap::create()->findFiltered($filter, $this->statusTable->getTable()->getTool('created DESC'));
                $this->statusTable->setList($list);
            }
        }

    }

    /**
     * @throws \Exception
     */
    protected function buildForm()
    {
        $this->form = \App\Config::getInstance()->createForm('entryEdit');
        $this->form->setRenderer(\App\Config::getInstance()->createFormRenderer($this->form));

        if ($this->isSelfAssessment($this->entry)) {
            $this->form->addField(new Field\Html('title'))->setFieldset('Entry Details');
        } else {
            $f = $this->form->addField(new Field\Input('title'))->setFieldset('Entry Details');
            if ($this->entry->getPlacement() && $this->isPublic) {
                $f->setReadonly();
            }
        }

        if ($this->entry->getId() && $this->entry->getCollection()->gradable && !$this->isPublic) {
            $avg = $this->entry->calcAverage();
            $pct = round(($avg / ($this->entry->getCollection()->getScaleLength() - 1)) * 100);
            $this->form->addField(new Field\Html('average', sprintf('%.2f &nbsp; (%d%%)', $avg, $pct)))->setFieldset('Entry Details');
        }

        if ($this->getUser()->isStaff() && $this->getConfig()->getPageRole() == \App\Db\User::ROLE_STAFF) {
            $this->form->addField(new \App\Form\Field\CheckSelect('status', \Skill\Db\Entry::getStatusList()))
                ->setRequired()->prependOption('-- Status --', '')->setNotes('Set the status. Use the checkbox to disable notification emails.')->setFieldset('Entry Details');
        }

        if (!$this->isSelfAssessment($this->entry)) {
            $this->form->addField(new Field\Input('assessor'))->setFieldset('Entry Details')->setRequired();
            $this->form->addField(new Field\Input('absent'))->setFieldset('Entry Details');
        }

        $this->form->addField(new Field\Textarea('notes'))->addCss('')->setLabel('Comments')->setFieldset('Entry Details');


        $items = \Skill\Db\ItemMap::create()->findFiltered(array('collectionId' => $this->entry->getCollection()->getId()),
            \Tk\Db\Tool::create('category_id, order_by'));
        /** @var \Skill\Db\Item $item */
        foreach ($items as $item) {
            $fld = $this->form->addField(new \Skill\Form\Field\Item($item))->setLabel(null);
            $val = \Skill\Db\EntryMap::create()->findValue($this->entry->getId(), $item->getId());
            if ($val) {
                $fld->setValue($val->value);
            }
        }

        if ($this->entry->getCollection()->confirm) {
            $radioBtn = new \Tk\Form\Field\RadioButton('confirm', $this->entry->getCollection()->confirm);
            $radioBtn->appendOption('Yes', '1', 'fa fa-check')->appendOption('No', '0', 'fa fa-ban');
            $this->form->addField($radioBtn)->setLabel(null)->setFieldset('Confirmation')->setValue(true);
        }

        if ($this->isPublic) {
            $this->form->addField(new Event\Submit('submit', array($this, 'doSubmit')))->setIconRight('fa fa-arrow-right')->addCss('pull-right')->setLabel('Submit ');
        } else {
            $this->form->addField(new Event\Submit('update', array($this, 'doSubmit')));
            $this->form->addField(new Event\Submit('save', array($this, 'doSubmit')));
            $this->form->addField(new Event\Link('cancel', $this->getConfig()->getBackUrl()));
        }

    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        // Load the object with data from the form using a helper object
        \Skill\Db\EntryMap::create()->mapForm($form->getValues(), $this->entry);

        if (!$this->isPublic) {
            if ($form->getField('status') && (!$form->getFieldValue('status') || !in_array($form->getFieldValue('status'),
                        \Tk\ObjectUtil::getClassConstants($this->entry, 'STATUS')))) {
                $form->addFieldError('status', 'Please Select a valid status');
            }
        } else {
            $this->entry->status = \Skill\Db\Entry::STATUS_PENDING;
        }

        $hasValue = false;
        foreach ($form->getValues('/^item\-/') as $name => $val) {
            if ($val > 0) $hasValue = true;
        }
        if (!$hasValue) {
            $form->addError('Use the slider at the end of the question to leave feedback.');
        }

        $form->addFieldErrors($this->entry->validate());

        if ($form->hasErrors()) {
            return;
        }

        // Save Item values
        \Skill\Db\EntryMap::create()->removeValue($this->entry->getVolatileId());
        foreach ($form->getValues('/^item\-/') as $name => $val) {
            $id = (int)substr($name, strrpos($name, '-') + 1);
            \Skill\Db\EntryMap::create()->saveValue($this->entry->getVolatileId(), $id, (int)$val);
        }

        // TODO: Although this seems redundant, there was a bug where the entry->userId == placement->id (try to trace?)
        $this->entry->userId = $this->entry->getPlacement()->userId;
        $this->entry->save();

        // Create status if changed and trigger notifications
        if (!$this->isPublic && $form->getField('status')) {
            \App\Db\Status::createFromField($this->entry, $form->getField('status'),
                $this->entry->getSubject()->getProfile(), $this->entry->getSubject());
        } else {
            \App\Db\Status::create($this->entry, $this->entry->status, true, '',
                $this->entry->getSubject()->getProfile(), $this->entry->getSubject());
        }

        \Tk\Alert::addSuccess('You response has been successfully submitted. Please return at any time to make changes while this Entry remains in the pending status.');
        $url = \Tk\Uri::create()->set('entryId', $this->entry->getId());
        if ($form->getTriggeredEvent()->getName() == 'update') {
            $url = $this->getConfig()->getBackUrl();
            if ($this->entry->getPlacement() && $this->getUser()->isStaff()) {
                $url = \App\Uri::createSubjectUrl('/placementEdit.html')->set('placementId', $this->entry->getPlacement()->getId());
            }
        }
        $event->setRedirect($url);
    }

    /**
     * @return Template
     * @throws \Exception
     */
    public function show()
    {
        $template = parent::show();

        $template->insertText('panel-title', $this->entry->getCollection()->name . ' Edit');
        if ($this->entry->getCollection()->icon) {
            $template->setAttr('icon', 'class', $this->entry->getCollection()->icon);
        }
        if ($this->entry->getCollection()->instructions) {
            $template->insertHtml('instructions', $this->entry->getCollection()->instructions);
            $template->show('instructions');
        }
        if ($this->isPublic) {
            if (count($this->errors)) {
                foreach ($this->errors as $error) {
                    \Tk\Alert::addWarning($error);
                }
                $template->setChoice('not-available');
                $template->setAttr('contact', 'href', \Tk\Uri::create('/contact.html')
                    ->set('subjectId', $this->entry->subjectId));
                return $template;
            } else {
                $template->setChoice('available');
            }
        } else {
            $template->setChoice('edit');
            if ($this->getUser()->isStaff()) {
                if ($this->entry->getId()) {
                    if ($this->statusTable) {
                        $template->replaceTemplate('statusTable', $this->statusTable->getTable()->getRenderer()->show());
                        $template->setChoice('statusLog');
                    }
                }
            } else {        // For students here
                //$template->insertHtml('instructions', $this->entry->getCollection()->instructions);
            }
        }

        // Render the form
        $template->insertTemplate('form', $this->form->getRenderer()->show());
        $template->appendCssUrl(\Tk\Uri::create('/plugin/plg-skill/assets/skill.less'));
        $template->appendJsUrl(\Tk\Uri::create('/plugin/plg-skill/assets/skill.js'));


        $css = <<<CSS
.form-group.tk-item:nth-child(odd) .skill-item {
  background-color: {$this->entry->getCollection()->color};
}
.tk-form fieldset:first-child legend {
  display: none !important;
}
CSS;
        $template->appendCss($css);

        $js = <<<JS
jQuery(function ($) {
  if (config.role === 'staff') {
    $('#entryEdit .tk-form-events').clone(true).appendTo($('.tk-form fieldset.EntryDetails'));
  }
});
JS;
        $template->appendJs($js);

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
      <h4 class="panel-title"><i class="fa fa-pencil" var="icon"></i> <span var="panel-title">Skill Entry Edit</span></h4>
    </div>
    <div class="panel-body">
      <div class="instructions" choice="instructions" var="instructions"></div>
      <hr choice="instructions"/>
      <div var="form"></div>
    </div>
  </div>

  <div class="panel panel-default" choice="statusLog">
    <div class="panel-heading">
      <h4 class="panel-title"><i class="fa fa-sitemap"></i> <span>Status Log</span></h4>
    </div>
    <div class="panel-body">
      <div var="statusTable"></div>
    </div>
  </div>
  
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makePublicTemplate()
    {
        $xhtml = <<<HTML
<div class="content EntryEdit">
  <div class="container">
    <div class="layout layout-stack-sm layout-main-left">
      <div class="layout-main" choice="available">
        <div var="instructions"></div>
        <div var="form"></div>
      </div>
      <div class="layout-main" choice="not-available">
        <p>Please <a href="/contact.html?subjectId=0" var="contact">contact</a> the subject coordinator as this resource is no longer available.</p>
      </div>
    </div>
  </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}