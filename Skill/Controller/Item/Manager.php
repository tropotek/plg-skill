<?php
namespace Skill\Controller\Item;

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
        $this->setPageTitle('Skill Item Manager');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));

        $this->setTable(\Skill\Table\Item::create());
        $this->getTable()->setCollectionObj($this->collection);
        $this->getTable()->setEditUrl(\Uni\Uri::createSubjectUrl('/itemEdit.html'));
        $this->getTable()->init();

        $filter = array(
            'collectionId' => $this->collection->getId()
        );
        $this->getTable()->setList($this->getTable()->findList($filter));

    }

    public function initActionPanel()
    {
        $this->getActionPanel()->append(\Tk\Ui\LINK::createBtn('New Item',
            $this->getTable()->getEditUrl()->set('collectionId', $this->collection->getId()), 'fa fa-question'));
    }

    /**
     * @return \Dom\Template
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
<div class="tk-panel" data-panel-title="Item Manager" data-panel-icon="fa fa-question" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}

