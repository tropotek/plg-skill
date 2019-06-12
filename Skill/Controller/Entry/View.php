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
class View extends AdminEditIface
{

    /**
     * @var \Skill\Db\Entry
     */
    protected $entry = null;


    /**
     * View constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Skill Entry View');
        if ($this->getUser()->isStudent()) {
            $this->getActionPanel()->setEnabled(false);
        }
    }

    /**
     * @param Request $request
     * @return \Dom\Renderer\Renderer|Template|null
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->entry = \Skill\Db\EntryMap::create()->find($request->get('entryId'));

        if (!$this->entry)
            throw new \Tk\Exception('Invalid entry record.');

        if ($request->has('p')) {
            return $this->doPdf($request);
        }

        $this->setForm(\Skill\Form\EntryView::create()->setModel($this->entry));
        $this->initForm($request);
        $this->getForm()->execute();

    }

    /**
     * @param Request $request
     * @return \Dom\Renderer\Renderer|Template|null|void
     * @throws \Exception
     */
    public function doPdf(Request $request)
    {
        $watermark = '';
        $ren = \Skill\Ui\Pdf\Entry::create($this->entry, $watermark);
        //$ren->download();
        $ren->output();     // comment this to see html version
        //return $ren->show();
    }

    /**
     *
     */
    public function initActionPanel()
    {
        if ($this->entry->getId() && $this->getUser()->isStaff()) {
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('PDF',
                \App\Uri::create()->set('p', 'p'), 'fa fa-file-pdf-o')->setAttr('target', '_blank'));
        }
    }

    /**
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        $this->initActionPanel();
        $template = parent::show();

        // Render the form
        $template->setAttr('panel', 'data-panel-title', $this->entry->getCollection()->name . ' View');
        if ($this->entry->getCollection()->icon) {
            $template->setAttr('panel', 'data-panel-icon', $this->entry->getCollection()->icon);
        }
        $template->appendTemplate('panel', $this->form->getRenderer()->show());

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
  <div class="tk-panel" data-panel-title="Skill Entry View" data-panel-icon="fa fa-question" var="panel"></div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}