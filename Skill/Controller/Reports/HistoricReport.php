<?php
namespace Skill\Controller\Reports;

use Dom\Template;
use Tk\Request;

/**
 * @author Michael Mifsud <info@tropotek.com>
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
        parent::__construct();
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
            throw new \Tk\Exception('A report is not available for this collection.');
        }


        $this->table = \Skill\Table\Historic::create();
        $this->table->setCollectionObject($this->collection);
        $this->table->init();


        $filter = array(
            'collectionId' => $this->collection->uid
        );
        $this->table->setList($this->table->findList($filter));


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
  <div class="tk-panel" data-panel-icon="fa fa-bar-chart" var="panel"></div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}