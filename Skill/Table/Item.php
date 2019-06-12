<?php
namespace Skill\Table;


/**
 * Example:
 * <code>
 *   $table = new CsvLog::create();
 *   $table->init();
 *   $list = ObjectMap::getObjectListing();
 *   $table->setList($list);
 *   $tableTemplate = $table->show();
 *   $template->appendTemplate($tableTemplate);
 * </code>
 * 
 * @author Mick Mifsud
 * @created 2019-01-30
 * @link http://tropotek.com.au/
 * @license Copyright 2019 Tropotek
 */
class Item extends \App\TableIface
{

    /**
     * @var \Skill\Db\Collection
     */
    private $_collection = null;

    /**
     * @return $this
     * @throws \Exception
     */
    public function init()
    {

        $this->appendCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->appendCell(new \Tk\Table\Cell\Text('num'))->setLabel('#')->setOnPropertyValue(function ($cell, $obj, $value) {
            /** @var \Tk\Table\Cell\Text $cell */
            /** @var \Skill\Db\Item $obj */
            $value = $cell->getTable()->getRenderer()->getRowId()+1;
            return $value;
        });
        $this->appendCell(new \Tk\Table\Cell\Text('question'))->addCss('key')->setUrl($this->getEditUrl());
        $this->appendCell(new \Tk\Table\Cell\Text('categoryId'))->setOnPropertyValue(function ($cell, $obj, $value) {
            /** @var \Skill\Db\Item $obj */
            if ($obj->getCategory()) return $obj->getCategory()->name;
            return $value;
        });
        $domains = \Skill\Db\DomainMap::create()->findFiltered(array('collectionId' => $this->getCollectionObj()->getId()));
        if (count($domains)) {
            $this->appendCell(new \Tk\Table\Cell\Text('domainId'))->setOnPropertyValue(function ($cell, $obj, $value) {
                /** @var \Skill\Db\Item $obj */
                if ($obj->getDomain()) return $obj->getDomain()->name;
                return 'None';
            });
        }
        $this->appendCell(new \Tk\Table\Cell\Boolean('publish'));
        $this->appendCell(new \Tk\Table\Cell\Date('modified'));
        $this->appendCell(new \Tk\Table\Cell\Text('uid'))->setLabel('UID');

        $this->appendCell(new \Tk\Table\Cell\Text('values'))->setLabel('Val #')->setOnPropertyValue(function ($cell, $obj, $value) {
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
        //$this->appendCell(new \Tk\Table\Cell\OrderBy('orderBy'));
        $this->setStaticOrderBy('cat.order_by, order_by');

        // Filters
        $this->appendFilter(new \Tk\Form\Field\Input('keywords'))->setAttr('placeholder', 'Keywords');

        // Actions
        $this->appendAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name'))->setUnselected(array('publish', 'modified')));
        $this->appendAction(\Tk\Table\Action\Csv::create());
        $this->appendAction(\Tk\Table\Action\Delete::create());

        return $this;
    }

    /**
     * @param array $filter
     * @param null|\Tk\Db\Tool $tool
     * @return \Tk\Db\Map\ArrayObject|\Skill\Db\Item[]
     * @throws \Exception
     */
    public function findList($filter = array(), $tool = null)
    {
        if (!$tool) $tool = $this->getTool('cat.order_by', 100);
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = \Skill\Db\ItemMap::create()->findFiltered($filter, $tool);
        return $list;
    }

    /**
     * @return \Skill\Db\Collection
     */
    public function getCollectionObj(): \Skill\Db\Collection
    {
        return $this->_collection;
    }

    /**
     * @param \Skill\Db\Collection $collection
     * @return Item
     */
    public function setCollectionObj(\Skill\Db\Collection $collection): Item
    {
        $this->_collection = $collection;
        return $this;
    }

}