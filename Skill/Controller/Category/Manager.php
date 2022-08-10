<?php
namespace Skill\Controller\Category;

use App\Controller\AdminManagerIface;
use Dom\Template;
use Tk\Request;

/**
 * @author Michael Mifsud <http://www.tropotek.com/>
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
        $this->setPageTitle('Skill Category Manager');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));

        $this->setTable(\Skill\Table\Category::create());
        $this->getTable()->setEditUrl(\Uni\Uri::createSubjectUrl('/categoryEdit.html'));
        $this->getTable()->init();

        $filter = array(
            'collectionId' => $this->collection->getId()
        );
        $this->getTable()->setList($this->getTable()->findList($filter));

    }

    /**
     *
     */
    public function initActionPanel()
    {
        $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('New Category',
            $this->getTable()->getEditUrl()->set('collectionId', $this->collection->getId()), 'fa fa-folder-o'));

    }

    /**
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        $this->initActionPanel();
        $template = parent::show();

        $template->appendTemplate('panel', $this->getTable()->show());

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
<div class="tk-panel" data-panel-title="Categories" data-panel-icon="fa fa-folder-o" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}

