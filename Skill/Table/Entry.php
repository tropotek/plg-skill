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
class Entry extends \App\TableIface
{

    /**
     * @return $this
     * @throws \Exception
     */
    public function init()
    {
        $this->getActionCell()->addButton(\Tk\Table\Cell\ActionButton::create('View Entry',
            \Uni\Uri::createSubjectUrl('/entryView.html'), 'fa fa-eye'))->setAppendQuery();

        $this->appendCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->appendCell($this->getActionCell());
        $this->appendCell(new \Tk\Table\Cell\Text('title'))->addCss('key')->setUrl($this->getEditUrl());
        $this->appendCell(new \Tk\Table\Cell\Text('average'));
        $this->appendCell(new \Tk\Table\Cell\Text('status'));
        $this->appendCell(new \Tk\Table\Cell\Text('userId'))->setOnPropertyValue(function ($cell, $obj) {
            /** @var \Tk\Table\Cell\Text $cell */
            /** @var \Skill\Db\Entry $obj */
            if ($obj->getUser()) {
                $value = $obj->getUser()->name;
                $url = \Uni\Uri::createSubjectUrl('/entryResults.html')->set('userId', $obj->userId)->set('collectionId', $obj->collectionId);
                $cell->setUrl($url, '');
                $cell->setAttr('title', 'View student results for this subject.');
            }
            return $value;
        });
        $this->appendCell(new \Tk\Table\Cell\Text('assessor'));
        $this->appendCell(new \Tk\Table\Cell\Text('absent'));
        $this->appendCell(new \Tk\Table\Cell\Boolean('confirm'));
        $this->appendCell(new \Tk\Table\Cell\Date('created'));

        // Filters
        $this->appendFilter(new \Tk\Form\Field\Input('keywords'))->setAttr('placeholder', 'Keywords');

        $list = \App\Db\CompanyMap::create()->findFiltered(array(
            'profileId' => $this->getConfig()->getProfileId(),
            'status' => \App\Db\Placement::STATUS_APPROVED
        ), \Tk\Db\Tool::create('name'));
        $this->appendFilter(new \Tk\Form\Field\Select('companyId', $list))->prependOption('-- Company --', '');

        // Actions
        $this->appendAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'title')));
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
        if (!$tool) $tool = $this->getTool('created DESC');
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = \Skill\Db\EntryMap::create()->findFiltered($filter, $tool);
        return $list;
    }

}