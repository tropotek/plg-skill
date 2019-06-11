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
class Collection extends \App\TableIface
{

    /**
     * @return $this
     * @throws \Exception
     */
    public function init()
    {

        $this->appendCell(new \Tk\Table\Cell\Checkbox('id'));

        $this->getActionCell()->addButton(\Tk\Table\Cell\ActionButton::create('Edit Collection',
            \Uni\Uri::createSubjectUrl('/collectionEdit.html'), 'fa fa-edit'))->setAppendQuery();
        $this->getActionCell()->addButton(\Tk\Table\Cell\ActionButton::create('View Entries',
            \Uni\Uri::createSubjectUrl('/entryManager.html'), 'fa fa-files-o'))->setAppendQuery();

        $this->appendCell($this->getActionCell());

        $this->appendCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl($this->getEditUrl());
        $this->appendCell(new \Tk\Table\Cell\Text('role'));
        $this->appendCell(new \Tk\Table\Cell\ArrayObject('available'))->setLabel('Placement Enabled Status');
        $this->appendCell(new \Tk\Table\Cell\Boolean('gradable'));
        $this->appendCell(new \Tk\Table\Cell\Boolean('viewGrade'));
        $this->appendCell(new \Tk\Table\Cell\Boolean('requirePlacement'));
        $this->appendCell(new \Tk\Table\Cell\Boolean('publish'));
        $this->appendCell(new \Tk\Table\Cell\Boolean('active'));
        $this->appendCell(new \Tk\Table\Cell\Text('entries'))->setOnPropertyValue(function ($cell, $obj) {
            /** @var \Skill\Db\Collection $obj */
            $filter = array('collectionId' => $obj->getId());
            return \Skill\Db\EntryMap::create()->findFiltered($filter)->count();
        });
        $this->appendCell(new \Tk\Table\Cell\Date('modified'));

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
     * @return \Tk\Db\Map\ArrayObject|\Skill\Db\Collection[]
     * @throws \Exception
     */
    public function findList($filter = array(), $tool = null)
    {
        if (!$tool) $tool = $this->getTool();
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = \Skill\Db\CollectionMap::create()->findFiltered($filter, $tool);
        return $list;
    }

}