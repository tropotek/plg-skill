<?php
namespace Skill\Controller\Report;

use Dom\Template;
use Tk\Request;

/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class HistoricReport extends \App\Controller\AdminManagerIface
{

    /**
     * @var \Skill\Db\Collection
     */
    protected $collection = null;


    /**
     * Iface constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Historic Report');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));
        if (!$this->collection->gradable) {
            \Tk\Alert::addError('A report is not available for this collection.');
            $this->getBackUrl()->redirect();
        }

        $this->setTable(\Skill\Table\Historic::create());
        $this->getTable()->setCollectionObject($this->collection);
        $this->getTable()->init();

        $filter = array(
            'collectionId' => $this->collection->getId()
        );
        $this->getTable()->setList($this->getTable()->findList($filter));
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $panelTitle = sprintf('%s Historic Report', $this->collection->name);
        $template->setAttr('panel', 'data-panel-title', $panelTitle);
        //$template->setAttr('panel', 'data-panel-icon', $this->collection->icon);

        $template->appendTemplate('panel', $this->table->show());

        $template->insertText('subject', $this->getConfig()->getSubject()->getName());

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
<div class="historic-report">
  <div class="tk-panel" data-panel-icon="fa fa-table" var="panel">
    <p>This table queries all Skill Entries submitted to the subject: <span var="subject"></span></p>
  </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}