<?php
namespace Skill\Controller\Report;

use Dom\Template;
use Tk\Request;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CompanyAverageReport extends \App\Controller\AdminManagerIface
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
        $this->setPageTitle('Company Average Report');
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

        $this->setTable(\Skill\Table\CompanyAverage::create());
        $this->getTable()->setCollectionObject($this->collection);
        $this->getTable()->init();
        $filter = array(
            'collectionId' => $this->collection->id,
        );
        $this->getTable()->setList($this->getTable()->findList($filter));
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $panelTitle = sprintf('%s Company Average Report', $this->collection->name);
        $template->setAttr('panel', 'data-panel-title', $panelTitle);
        $template->appendTemplate('panel', $this->getTable()->show());
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
  <div class="tk-panel" data-panel-icon="fa fa-building-o" var="panel">
    <p>This table queries all Skill Entries submitted to the subject: <span var="subject"></span></p>
    <p><em>Note: These averages do not include any grade weighting.</em></p>
  </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}