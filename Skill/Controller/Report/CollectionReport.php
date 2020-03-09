<?php
namespace Skill\Controller\Report;

use Dom\Template;
use Tk\Request;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CollectionReport extends \App\Controller\AdminManagerIface
{

    /**
     * @var \Skill\Db\Collection
     */
    protected $collection = null;

    /**
     * @var null|\Tk\Uri
     */
    protected $editUrl = null;

    protected $resCache = array();


    /**
     * Iface constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Collection Report');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));
        $this->setPageTitle( $this->collection->name . ' Report');

        if (!$this->collection->gradable) {
            \Tk\Alert::addError('A report is not available for this collection.');
            $this->getBackUrl()->redirect();
        }

        $this->setTable(\Skill\Table\CollectionReport::create());
        $this->getTable()->setCollectionObj($this->collection);
        $this->getTable()->setEditUrl(\Uni\Uri::createSubjectUrl('/entryResults.html')->set('collectionId', $this->collection->getId()));
        $this->getTable()->init();

        $filter = array(
            'collectionId' => $this->collection->getId(),
            'subjectId' => $this->getConfig()->getSubjectId(),
            'type' => \Skill\Db\Collection::TYPE_STUDENT
        );
        $this->getTable()->setList($this->getTable()->findList($filter));

    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $template->appendTemplate('panel', $this->getTable()->show());
        if ($this->collection->icon) {
            $template->setAttr('panel', 'data-panel-icon', $this->collection->icon);
        }
        $panelTitle = sprintf('%s Report', $this->collection->name);
        $template->setAttr('panel', 'data-panel-title', $panelTitle);

        $template->appendCss('.tk-table td.total {border-right: double 3px #CCC; border-left: double 3px #CCC; background-color: #EEE;} ');

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
<div class="tk-panel" data-panel-title="Skill Report" data-panel-icon="fa fa-eye" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}