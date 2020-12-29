<?php

namespace Skill\Controller\Entry;

use App\Controller\AdminEditIface;
use Dom\Template;
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
     * @var \Bs\Table\Status
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
        $this->setPageTitle('Skill Entry Edit');
        if ($this->getAuthUser() && $this->getAuthUser()->isStudent()) {
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
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->entry = new \Skill\Db\Entry();
        $this->entry->userId = (int)$request->get('userId', 0);
        if ($this->getAuthUser()) {
            $this->entry->userId = $this->getAuthUser()->getId();
        }
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
                $this->getConfig()->getUserHomeUrl()->redirect();
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

        if ($request->get('collectionId') && $request->get('userId') && $this->getAuthUser()->isStaff()) {          // Staff view student self assessment
            $e = \Skill\Db\EntryMap::create()->findFiltered(array(
                    'collectionId' => $request->get('collectionId'),
                    'userId' => $request->get('userId'))
            )->current();
            if ($e)
                $this->entry = $e;
        }

        if (!$request->has('userId') && !$request->has('subjectId') && $this->getAuthUser() && $this->getAuthUser()->isStudent()) {         // Assumed to be student self assessment form
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

        if (!$this->entry->getId() && $this->entry->getPlacement()) {
            $this->entry->title = $this->entry->getPlacement()->getTitle(true);
            if ($this->entry->getPlacement()->getCompany()) {
                $this->entry->assessor = $this->entry->getPlacement()->getCompany()->name;
            }
            if ($this->entry->getPlacement()->getSupervisor())
                $this->entry->assessor = $this->entry->getPlacement()->getSupervisor()->name;
        }

        if ($this->entry->isSelfAssessment() && !$this->entry->getId()) {
            $this->entry->title = $this->entry->getCollection()->name . ' for ' . $this->entry->getAuthUser()->getName();
            $this->entry->assessor = $this->entry->getAuthUser()->getName();
        }

        $this->setPageTitle($this->entry->getCollection()->name);


        $this->setForm(\Skill\Form\Entry::create());
        if ($this->isPublic)
            $this->getForm()->setMode(\Skill\Form\Entry::MODE_PUBLIC);
        $this->getForm()->setModel($this->entry);
        $this->initForm($request);
        $this->getForm()->execute();


        if ($this->getAuthUser() && $this->getAuthUser()->isStaff() && $this->entry->getId()) {
            $this->statusTable = \Bs\Table\Status::create(\App\Config::getInstance()->getUrlName().'-status')
                ->setEditUrl(\Uni\Uri::createSubjectUrl('/mailLogManager.html'))->init();
            $filter = array(
                'model' => $this->entry,
                'subjectId' => $this->entry->subjectId
            );
            $this->statusTable->setList($this->statusTable->findList($filter, $this->statusTable->getTool('created DESC')));
        }

//        if ($this->getUser() && !$this->getUser()->isStudent()) {
//            \Skill\Db\CollectionMap::create()->fixChangeoverEntries();
//        }

    }

    /**
     *
     */
    public function initActionPanel()
    {
        if ($this->entry->getId() && ($this->getAuthUser() && $this->getAuthUser()->isStaff())) {
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('View',
                \Uni\Uri::createSubjectUrl('/entryView.html')->set('entryId', $this->entry->getId()), 'fa fa-eye'));
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('PDF',
                \Uni\Uri::createSubjectUrl('/entryView.html')->set('entryId', $this->entry->getId())->set('p', 'p'), 'fa fa-file-pdf-o')->setAttr('target', '_blank'));
        }
    }

    /**
     * @return Template
     * @throws \Exception
     */
    public function show()
    {
        $this->initActionPanel();
        $template = parent::show();

        if ($this->isPublic) {
            if (count($this->errors)) {
                foreach ($this->errors as $error) {
                    \Tk\Alert::addWarning($error);
                }
                $template->setVisible('not-available');
                $template->setAttr('contact', 'href', \Tk\Uri::create('/contact.html')
                    ->set('subjectId', $this->entry->subjectId));
                return $template;
            } else {
                $template->setVisible('available');
            }
        } else {
            $template->setVisible('edit');
            if ($this->getAuthUser()->isStaff()) {
                if ($this->entry->getId()) {
                    if ($this->statusTable) {
                        $template->appendTemplate('statusLog', $this->statusTable->show());
                        $template->setVisible('statusLog');
                    }
                }
            } else {        // For students here
                //$template->insertHtml('instructions', $this->entry->getCollection()->instructions);
            }
        }

        // Render the form
        $title = $this->entry->getCollection()->name;
        if ($this->entry->getPlacement()) {
            $title .= ': ' . $this->entry->getPlacement()->getTitle(true);
        }
        if ($this->entry->getId()) {
            $title = sprintf('[ID: %s] ', $this->entry->getId()) . $title;
        }
        $template->setAttr('panel', 'data-panel-title', $title);

        if ($this->entry->getCollection()->icon) {
            //$template->setAttr('icon', 'class', $this->entry->getCollection()->icon);
            $template->setAttr('panel', 'data-panel-icon', $this->entry->getCollection()->icon);
        }
        if ($this->entry->getCollection()->instructions) {
            $template->insertHtml('instructions', $this->entry->getCollection()->instructions);
            $template->setVisible('instructions');
        }
        $template->appendTemplate('panel', $this->getForm()->show());

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

  <div class="tk-panel" data-panel-title="Skill Entry Edit" data-panel-icon="fa fa-question" var="panel">
      <div class="instructions" choice="instructions" var="instructions"></div>
      <hr choice="instructions"/>
  </div>

  <div class="tk-panel" data-panel-title="Status Log" data-panel-icon="fa fa-sitemap" var="statusLog" choice="statusLog"></div>
  
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
        <div var="panel"></div>
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