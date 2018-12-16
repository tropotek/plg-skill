<?php
namespace Skill\Controller\Item;

use App\Controller\AdminManagerIface;
use Dom\Template;
use Tk\Form\Field;
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
     * @var null|\Tk\Uri
     */
    private $editUrl = null;



    /**
     * Manager constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Skill Item Manager');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));

        $this->editUrl = \Uni\Uri::createSubjectUrl('/itemEdit.html');

        $u = clone $this->editUrl;
        $this->getActionPanel()->append(\Tk\Ui\LINK::createBtn('New Item',
            $u->set('collectionId', $this->collection->getId()), 'fa fa-question'));

        $this->table = \App\Config::getInstance()->createTable(\App\Config::getInstance()->getUrlName());
        $this->table->setRenderer(\App\Config::getInstance()->createTableRenderer($this->table));

        $this->table->appendCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('num'))->setLabel('#')->setOnPropertyValue(function ($cell, $obj, $value) {
            /** @var \Tk\Table\Cell\Text $cell */
            /** @var \Skill\Db\Item $obj */
            $value = $cell->getTable()->getRenderer()->getRowId()+1;
            return $value;
        });
        $this->table->appendCell(new \Tk\Table\Cell\Text('question'))->addCss('key')->setUrl(clone $this->editUrl);
        $this->table->appendCell(new \Tk\Table\Cell\Text('categoryId'))->setOnPropertyValue(function ($cell, $obj, $value) {
            /** @var \Skill\Db\Item $obj */
            if ($obj->getCategory()) return $obj->getCategory()->name;
            return $value;
        });
        $domains = \Skill\Db\DomainMap::create()->findFiltered(array('collectionId' => $this->collection->getId()));
        if (count($domains)) {
            $this->table->appendCell(new \Tk\Table\Cell\Text('domainId'))->setOnPropertyValue(function ($cell, $obj, $value) {
                /** @var \Skill\Db\Item $obj */
                if ($obj->getDomain()) return $obj->getDomain()->name;
                return 'None';
            });
        }
        $this->table->appendCell(new \Tk\Table\Cell\Boolean('publish'));
        $this->table->appendCell(new \Tk\Table\Cell\Date('modified'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('uid'))->setLabel('UID');

        $this->table->appendCell(new \Tk\Table\Cell\Text('values'))->setLabel('Val #')->setOnPropertyValue(function ($cell, $obj, $value) {
            /** @var \Tk\Table\Cell\Text $cell */
            /** @var \Skill\Db\Item $obj */
            $sql = sprintf('SELECT a.id, a.question, COUNT(b.item_id) as \'count\'
FROM skill_item a, skill_value b
WHERE a.id = %s AND a.id = b.item_id
GROUP BY a.id', $obj->getId());
            $res = \App\Config::getInstance()->getDb()->query($sql);
            $value = (int)$res->fetchColumn(2);

            return $value;
        });

        // TODO: this needs to be a nested sub level order system ???????
        //$this->table->appendCell(new \Tk\Table\Cell\OrderBy('orderBy'));
        $this->table->setStaticOrderBy('cat.order_by, order_by');

        // Filters
        $this->table->appendFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Keywords');

        // Actions
        $this->table->appendAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name'))->setUnselected(array('publish', 'modified')));
        $this->table->appendAction(\Tk\Table\Action\Csv::create());
        $this->table->appendAction(\Tk\Table\Action\Delete::create());

        $this->table->setList($this->getList());
        $this->table->resetSession();

    }

    /**
     * @return \Skill\Db\Item[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    protected function getList()
    {
        $filter = $this->table->getFilterValues();
        $filter['collectionId'] = $this->collection->getId();
        return \Skill\Db\ItemMap::create()->findFiltered($filter, $this->table->getTool('cat.order_by', 100));
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $template->replaceTemplate('table', $this->table->getRenderer()->show());

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
<div>

  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><i class="fa fa-question"></i> Item Manager</h4>
    </div>
    <div class="panel-body">
      <div var="table"></div>
    </div>
  </div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}

