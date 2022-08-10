<?php
namespace Skill\Controller\Report;

use Dom\Template;
use Tk\Request;

/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class DateAverageReport extends \App\Controller\AdminManagerIface
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
        $this->setPageTitle('Date Average Report');
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

        $this->setTable(\Skill\Table\DateAverage::create());
        $this->getTable()->setCollectionObject($this->collection);
        $this->getTable()->init();
        $filter = array(
            'collectionUid' => $this->collection->uid,
            //'collectionId' => $this->collection->getId()
        );
        $this->getTable()->setList($this->getTable()->findList($filter));

    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $panelTitle = sprintf('%s Date Average Report', $this->collection->name);

        $template->appendTemplate('panel', $this->getTable()->show());
        $template->setAttr('panel', 'data-panel-title', $panelTitle);
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
  <div class="tk-panel" data-panel-icon="fa fa-calendar" var="panel">
    <p>This table queries all Skill Entries submitted to the subject: <span var="subject"></span></p>
  </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}