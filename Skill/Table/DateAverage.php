<?php
namespace Skill\Table;

use Tk\Form\Field;
use Tk\Table\Cell;

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
class DateAverage extends \Uni\TableIface
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

        //$this->appendCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->appendCell(new \Tk\Table\Cell\Text('item_uid'));
        $this->appendCell(new \Tk\Table\Cell\Text('item_question'))->addCss('key');
        $this->appendCell(new \Tk\Table\Cell\Text('average'));
        $this->appendCell(new \Tk\Table\Cell\Text('count'));
        $this->appendCell(new \Tk\Table\Cell\Text('date'));


        $list = \App\Db\SubjectMap::create()->findFiltered(array('courseId' => $this->getConfig()->getCourseId()),
            \Tk\Db\Tool::create('a.name DESC'));
        $c = $this->appendFilter(new Field\CheckboxSelect('subjectId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list)));
        if ($this->getConfig()->getSubjectId()) {
            $c->setValue(array($this->getConfig()->getSubjectId()));
        }

        $list = \Skill\Db\ItemMap::create()->findFiltered(array(    // TODO: we need to use the uid here
            'collectionId' => $this->getCollectionObject()->getId()
        ), \Tk\Db\Tool::create('uid'));
        $this->appendFilter(Field\CheckboxSelect::createSelect('itemId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list, 'question', 'uid')))->setLabel('Question');

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

        $this->appendFilter(new Field\Input('studentNumber'))->setAttr('placeholder', 'Student Number');

        $list = array('Monthly' => '1M', 'Daily' => '1D');
        $this->appendFilter(Field\Select::createSelect('interval', $list));

        // Actions
        $this->appendAction(\Tk\Table\Action\Csv::create());


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
        if (!$tool) $tool = $this->getTool('', 0);
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = \Skill\Db\ReportingMap::create()->findDateAverage($filter, $tool);
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
     * @return Historic
     */
    public function setCollectionObject($collection)
    {
        $this->collectionObject = $collection;
        return $this;
    }


    /**
     * @return \App\Config|\Uni\Config
     */
    public function getConfig()
    {
        return parent::getConfig();
    }
}