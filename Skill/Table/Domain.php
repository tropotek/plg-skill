<?php
namespace Skill\Table;


/**
 * Example:
 * <code>
 *   $table = new Domain::create();
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
class Domain extends \App\TableIface
{

    /**
     * @return $this
     * @throws \Exception
     */
    public function init()
    {

        $this->appendCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->appendCell(new \Tk\Table\Cell\Text('uid'))->setLabel('UID');
        $this->appendCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl($this->getEditUrl());
        $this->appendCell(new \Tk\Table\Cell\Text('label'));
        $this->appendCell(new \Tk\Table\Cell\Text('weight'));
        $this->appendCell(new \Tk\Table\Cell\Boolean('active'));
        $this->appendCell(new \Tk\Table\Cell\Date('modified'));
        $this->appendCell(new \Tk\Table\Cell\OrderBy('orderBy'));

        // Filters
        $this->appendFilter(new \Tk\Form\Field\Input('keywords'))->setAttr('placeholder', 'Keywords');

        // Actions
        $this->appendAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name')));
        $this->appendAction(\Tk\Table\Action\Csv::create());
        $this->appendAction(\Tk\Table\Action\Delete::create());

        return $this;
    }

    /**
     * @param array $filter
     * @param null|\Tk\Db\Tool $tool
     * @return \Tk\Db\Map\ArrayObject|\Skill\Db\Domain[]
     * @throws \Exception
     */
    public function findList($filter = array(), $tool = null)
    {
        if (!$tool) $tool = $this->getTool();
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = \Skill\Db\DomainMap::create()->findFiltered($filter, $tool);
        return $list;
    }

}