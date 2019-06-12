<?php
namespace Skill\Controller\Entry;

use App\Controller\AdminManagerIface;
use Dom\Template;
use Tk\Request;



/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Manager extends AdminManagerIface
{

    /**
     * @var \Skill\Db\Collection
     */
    private $collection = null;


    /**
     * Manager constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Skill Entry Manager');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));

        $this->setTable(\Skill\Table\Entry::create());
        $this->getTable()->setEditUrl(\Uni\Uri::createSubjectUrl('/entryEdit.html'));
        $this->getTable()->init();

        $filter = array(
            'collectionId' => $this->collection->getId(),
            'subjectId' => $this->getConfig()->getSubjectId()
        );
        $this->getTable()->setList($this->getTable()->findList($filter));
    }

    /**
     *
     */
    public function initActionPanel()
    {
        if ($this->collection->gradable) {
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Grade Report',
                \Uni\Uri::createSubjectUrl('/collectionReport.html')->set('collectionId', $this->collection->getId()), 'fa fa-pie-chart'));
        }
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $this->initActionPanel();
        $template = parent::show();

        $template->appendTemplate('panel', $this->getTable()->show());
        $template->setAttr('panel', 'data-panel-title', $this->collection->name . ' entries for ' . $this->getSubject()->name);

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
<div class="tk-panel" data-panel-title="Entry Manager" data-panel-icon="fa fa-pencil" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}

