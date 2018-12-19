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
class Historic extends \Uni\TableIface
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
        //$this->resetSession();

        $this->appendCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->appendCell(new \Tk\Table\Cell\Text('item_uid'));
        $this->appendCell(new \Tk\Table\Cell\Text('item_question'))->addCss('key');
        $this->appendCell(new \Tk\Table\Cell\Text('item_value'));
        $this->appendCell(new \Tk\Table\Cell\Text('placement_title'));
        $this->appendCell(\Tk\Table\Cell\Date::createDate('placement_dateStart'));
        $this->appendCell(\Tk\Table\Cell\Date::createDate('placement_dateEnd'));
        $this->appendCell(new \Tk\Table\Cell\Text('subject_name'));
        $this->appendCell(new \Tk\Table\Cell\Text('supervisor_name'));
        $this->appendCell(new \Tk\Table\Cell\Text('user_name'))->setLabel('Student Name');
        $this->appendCell(new \Tk\Table\Cell\Text('company_name'));

        $this->appendCell(new \Tk\Table\Cell\Text('category_name'));
        $this->appendCell(new \Tk\Table\Cell\Text('domain_name'));
        $this->appendCell(new \Tk\Table\Cell\Text('user_uid'))->setLabel('Student Number');

        $this->appendCell(new \Tk\Table\Cell\Text('item_id'));
        $this->appendCell(new \Tk\Table\Cell\Text('subject_id'));
        $this->appendCell(new \Tk\Table\Cell\Text('placement_id'));
        $this->appendCell(new \Tk\Table\Cell\Text('student_id'));
        $this->appendCell(new \Tk\Table\Cell\Text('company_id'));
        $this->appendCell(new \Tk\Table\Cell\Text('supervisor_id'));

        // Filters
        $list = \App\Db\SubjectMap::create()->findFiltered(array('profileId' => $this->getConfig()->getProfileId()), \Tk\Db\Tool::create('id DESC'));
        $this->appendFilter(new Field\CheckboxSelect('subjectId', $list));

        $list = \App\Db\CompanyMap::create()->findFiltered(array(
            'profileId' => $this->getConfig()->getProfileId(),
            'status' => array('approved')
        ), \Tk\Db\Tool::create('name'));
        $this->appendFilter(new Field\CheckboxSelect('companyId', $list));

        $list = \App\Db\SupervisorMap::create()->findFiltered(array(
            'profileId' => $this->getConfig()->getProfileId(),
            'status' => array('approved')
        ), \Tk\Db\Tool::create('id DESC'));
        $this->appendFilter(new Field\CheckboxSelect('supervisorId', $list));

        $list = \Skill\Db\ItemMap::create()->findFiltered(array(    // TODO: we need to use the uid here
            'collectionId' => $this->getCollectionObject()->getId()
        ), \Tk\Db\Tool::create('id DESC'));
        $this->appendFilter(Field\CheckboxSelect::createSelect('itemId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list, 'question', 'uid')))->setLabel('Question');

        $list = \Skill\Db\CategoryMap::create()->findFiltered(array(    // TODO: we need to use the uid here
            'collectionId' => $this->getCollectionObject()->getId()
        ), \Tk\Db\Tool::create('id DESC'));
        $this->appendFilter(new Field\CheckboxSelect('categoryId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list, 'name', 'uid')));

        $list = \Skill\Db\DomainMap::create()->findFiltered(array(    // TODO: we need to use the uid here
            'collectionId' => $this->getCollectionObject()->getId()
        ), \Tk\Db\Tool::create('id DESC'));
        $this->appendFilter(new Field\CheckboxSelect('domainId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list, 'name', 'uid')));

        $list = \Skill\Db\ScaleMap::create()->findFiltered(array(
            'collectionId' => $this->getCollectionObject()->getId()
        ), \Tk\Db\Tool::create('id DESC'));
        $this->appendFilter(new Field\CheckboxSelect('scaleId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list, 'name', 'value')));

        $this->appendFilter(new Field\DateRange('date'));

        $this->appendFilter(new Field\Input('studentNumber'))->setAttr('placeholder', 'Student Number');

        $this->appendFilter(new Field\Input('placementId'))->setLabel('Placement ID')->setAttr('placeholder', 'Placement ID');

//        $list = array('-- Exclude Zero Values --' => '', 'Yes' => '1', 'No' => '0');
//        $this->appendFilter(Field\Select::createSelect('excludeZero', $list));


        // Actions
        $this->appendAction(\Tk\Table\Action\ColumnSelect::create()->setUnselected(
            array('item_uid', 'item_id', 'subject_id', 'placement_id', 'student_id', 'company_id', 'supervisor_id')
        ));
        $this->appendAction(\Tk\Table\Action\Csv::create());
        $this->appendAction(\Tk\Table\Action\Delete::create());

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
        if (!$tool) $tool = $this->getTool();
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = \Skill\Db\ReportingMap::create()->findFiltered($filter, $tool);
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