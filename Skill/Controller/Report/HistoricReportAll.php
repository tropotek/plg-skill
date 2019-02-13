<?php
namespace Skill\Controller\Report;

use Dom\Template;
use Tk\Request;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class HistoricReportAll extends \App\Controller\AdminManagerIface
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
        parent::__construct();
        $this->setPageTitle('All Historic Report');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));
        if (!$this->collection->gradable) {
            throw new \Tk\Exception('A report is not available for this collection.');
        }


        $this->table = \Skill\Table\HistoricAll::create();
        $this->table->setCollectionObject($this->collection);
        $this->table->init();


        $filter = array(
            'collectionUid' => $this->collection->uid
        );
        $this->table->setList($this->table->findList($filter));


    }




    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $panelTitle = sprintf('%s All Historic Report', $this->collection->name);
        $template->setAttr('panel', 'data-panel-title', $panelTitle);
        //$template->setAttr('panel', 'data-panel-icon', $this->collection->icon);

        $template->appendTemplate('panel', $this->table->show());

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
    <p><em>Notice: trying to query a large number of subjects can cause out of memory errors.</em></p>
  </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}