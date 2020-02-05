<?php
namespace Skill\Table;

use Tk\Form\Field;

/**
 * Example:
 * <code>
 *   $table = new Supervisor::create();
 *   $table->init();
 *   $list = ObjectMap::getObjectListing();
 *   $table->setList($list);
 *   $tableTemplate = $table->show();
 *   $template->appendTemplate($tableTemplate);
 * </code>
 * 
 * @author Mick Mifsud
 * @created 2018-11-29
 * @link http://tropotek.com.au/
 * @license Copyright 2018 Tropotek
 */
class ItemAverage extends \Uni\TableIface
{

    /**
     * @var \Skill\Db\Collection
     */
    protected $collectionObject = null;


    /**
     * Supervisor constructor.
     * @param string $tableId
     */
    public function __construct($tableId = '')
    {
        parent::__construct($tableId);
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function init()
    {
        $this->resetSession();

        $this->setStaticOrderBy('');

        //$this->appendCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->appendCell(new \Tk\Table\Cell\Text('num'))->setLabel('#')->addOnPropertyValue(function ($cell, $obj, $value) {
            /** @var $cell \Tk\Table\Cell\Text */
            $value = $cell->getTable()->getRenderer()->getRowId()+1;
            return $value;
        });
        $this->appendCell(new \Tk\Table\Cell\Text('item_question'))->addCss('key')->setLabel('Item');
        $this->appendCell(new \Tk\Table\Cell\Text('average'));
        $this->appendCell(new \Tk\Table\Cell\Text('category_name'))->setLabel('Category');
        $this->appendCell(new \Tk\Table\Cell\Text('domain_name'))->setLabel('Domain');
        $this->appendCell(new \Tk\Table\Cell\Text('item_uid'))->setLabel('UID');
        $this->appendCell(new \Tk\Table\Cell\Text('count'))->setLabel('Entry Count');



        // Filters

        $lastYear = \Tk\Date::create()->sub(new \DateInterval('P1Y'));
        //$values = array('dateStart' => \Tk\Date::getYearStart($lastYear)->format(\Tk\Date::$formFormat), 'dateEnd' => \Tk\Date::getYearEnd($lastYear)->format(\Tk\Date::$formFormat));
        $values = array('dateStart' => $this->getConfig()->getSubject()->dateStart->format(\Tk\Date::$formFormat), 'dateEnd' => $this->getConfig()->getSubject()->dateEnd->format(\Tk\Date::$formFormat));

        $this->appendFilter(new Field\DateRange('date')); //->setValue($values);

        $list = \App\Db\SubjectMap::create()->findFiltered(array('courseId' => $this->getConfig()->getCourseId()),
            \Tk\Db\Tool::create('a.name DESC'));
        $c = $this->appendFilter(new Field\CheckboxSelect('subjectId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list)));
        if ($this->getConfig()->getSubjectId()) {
            $c->setValue(array($this->getConfig()->getSubjectId()));
        }

        $list = \App\Db\CompanyMap::create()->findFiltered(array(
            'courseId' => $this->getConfig()->getCourseId(),
            'placementSubjectId' => $this->getConfig()->getSubjectId(),
            'status' => array('approved'),
            'placementsOnly' => true
        ), \Tk\Db\Tool::create('name'));
        $this->appendFilter(new Field\CheckboxSelect('companyId', $list));

        $list = \App\Db\SupervisorMap::create()->findFiltered(array(
            'courseId' => $this->getConfig()->getCourseId(),
            'placementSubjectId' => $this->getConfig()->getSubjectId(),
            'status' => array('approved'),
            'placementsOnly' => true
        ), \Tk\Db\Tool::create('id DESC'));
        $this->appendFilter(new Field\CheckboxSelect('supervisorId', $list));

        $list = \Skill\Db\CategoryMap::create()->findFiltered(array(
            'collectionId' => $this->getCollectionObject()->getId()
        ), \Tk\Db\Tool::create('id DESC'));
        $this->appendFilter(new Field\CheckboxSelect('categoryId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list, 'name', 'uid')));

        $list = \Skill\Db\DomainMap::create()->findFiltered(array(
            'collectionId' => $this->getCollectionObject()->getId()
        ), \Tk\Db\Tool::create('id DESC'));
        $this->appendFilter(new Field\CheckboxSelect('domainId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list, 'name', 'uid')));

        $this->appendFilter(new Field\Input('studentNumber'))->setAttr('placeholder', 'Student Number');


        $this->getFilterForm()->load($values);

        // Actions
        $this->appendAction(\Tk\Table\Action\Csv::create());
        $this->appendAction(\Skill\Table\Action\Graph::createGraph('graph'));

        return $this;
    }

    /**
     * @param array $filter
     * @param null|\Tk\Db\Tool $tool
     * @return \Tk\Db\Map\ArrayObject|\App\Db\Supervisor[]
     * @throws \Exception
     */
    public function findList($filter = array(), $tool = null)
    {
        if (!$tool) $tool = $this->getTool('cat.order_by, sd.order_by, si.order_by');
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = \Skill\Db\ReportingMap::create()->findItemAverage($filter, $tool->setOrderBy('cat.order_by, sd.order_by, si.order_by'));
        return $list;
    }

    /**
     * @return \Skill\Db\Collection
     */
    public function getCollectionObject()
    {
        return $this->collectionObject;
    }

    /**
     * @param \Skill\Db\Collection $collection
     * @return $this
     */
    public function setCollectionObject($collection)
    {
        $this->collectionObject = $collection;
        return $this;
    }

}