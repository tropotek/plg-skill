<?php
namespace Skill\Controller\Reports;

use Dom\Template;
use Tk\Request;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class ItemAverageReport extends \App\Controller\AdminManagerIface
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
        $this->setPageTitle('Item Average Report');
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


        $this->table = \Skill\Table\ItemAverage::create();
        $this->table->setCollectionObject($this->collection);
        $this->table->init();
        $filter = array(
            'collectionId' => $this->collection->id,
            //'subjectId' => $this->getConfig()->getSubjectId()
        );
        $this->table->setList($this->table->findList($filter, $this->table->getTool('', 0)));


    }




    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $panelTitle = sprintf('%s Item Average Report', $this->collection->name);
        $template->setAttr('panel', 'data-panel-title', $panelTitle);


        $template->appendTemplate('panel', $this->table->show());

        //$template->setAttr('stats-graph', 'data-src', \Tk\Uri::create('/ajax/stats.html'));
        //$template->setAttr('stats-graph', 'data-collection-id', $this->collection->getId());


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
  <div class="tk-panel" data-panel-icon="fa fa-line-chart" var="panel">
    <p>This table queries all Skill Entries submitted to the subject: <span var="subject"></span></p>
  </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}