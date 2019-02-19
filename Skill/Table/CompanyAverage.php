<?php
namespace Skill\Table;

use Coa\Adapter\Company;
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
class CompanyAverage extends \Uni\TableIface
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

        //$this->setStaticOrderBy('');

        //$this->appendCell(new \Tk\Table\Cell\Checkbox('id'));

        $this->appendCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl(\Tk\Uri::create('#'));
        //$this->appendCell(new \Tk\Table\Cell\Text('company_id'));
        $this->appendCell(new \Tk\Table\Cell\Text('avg'));
        $this->appendCell(new \Tk\Table\Cell\Text('pct'));
        $this->appendCell(new \Tk\Table\Cell\Text('entry_count'));
        $this->appendCell(new \Tk\Table\Cell\Text('min'));
        $this->appendCell(new \Tk\Table\Cell\Text('max'));
        $this->appendCell(new \Tk\Table\Cell\Date('created'))->setOnPropertyValue(function ($cell, $obj, $value) {
            /** @var $cell \Tk\Table\Cell\Text */
            /** @var $obj \stdClass */
            //vd($obj);
            return $value;
        });


        // Filters
        $values = array();

//        $this->appendFilter(new Field\DateRange('date')); //->setValue($values);
//
        $list = \App\Db\CompanyMap::create()->findFiltered(array(
            'profileId' => $this->getConfig()->getProfileId(),
            'placementSubjectId' => $this->getConfig()->getSubjectId(),
            'status' => array('approved'),
            'placementsOnly' => true
        ), \Tk\Db\Tool::create('name'));
        $this->appendFilter(new Field\CheckboxSelect('companyId', $list));
//
//        $list = \App\Db\SupervisorMap::create()->findFiltered(array(
//            'profileId' => $this->getConfig()->getProfileId(),
//            'status' => array('approved')
//        ), \Tk\Db\Tool::create('id DESC'));
//        $this->appendFilter(new Field\CheckboxSelect('supervisorId', $list));
//
//        $list = \Skill\Db\CategoryMap::create()->findFiltered(array(
//            'collectionId' => $this->getCollectionObject()->getId()
//        ), \Tk\Db\Tool::create('id DESC'));
//        $this->appendFilter(new Field\CheckboxSelect('categoryId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list, 'name', 'uid')));
//
//        $list = \Skill\Db\DomainMap::create()->findFiltered(array(
//            'collectionId' => $this->getCollectionObject()->getId()
//        ), \Tk\Db\Tool::create('id DESC'));
//        $this->appendFilter(new Field\CheckboxSelect('domainId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list, 'name', 'uid')));

        $this->appendFilter(new Field\Input('minEntries'))->setAttr('placeholder', 'Minimum Entries/Placements');


        $this->getFilterForm()->load($values);
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
        if (!$tool) $tool = $this->getTool();
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = \Skill\Db\ReportingMap::create()->findCompanyAverage($filter, $tool);
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
     * @return CompanyAverage
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