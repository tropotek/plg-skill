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
        $cid = $this->collectionObject->getId();

        //$this->resetSession();
        //$this->setStaticOrderBy('');
        //$this->appendCell(new \Tk\Table\Cell\Checkbox('id'));

        $this->appendCell(new \Tk\Table\Cell\Text('company_id'));
        $this->appendCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl(\App\Uri::create('#'))
            ->setOnCellHtml(function ($cell, $obj, $html) use ($cid) {
                /** @var $cell \Tk\Table\Cell\Text */
                /** @var $obj \stdClass */
                $config = \App\Config::getInstance();
                $list = \Skill\Db\ReportingMap::create()->findCompanyAverage(array('collectionId' => $cid, 'companyId' => $obj->company_id));
                $tbl = '';
                if ($list->count()) {
                    $ttable = $config->createTable('ptable-'.$obj->company_id);
                    $ttable->setRenderer($config->createTableRenderer($ttable));
                    $ttable->setStaticOrderBy('');
                    $ttable->addCss('table-sm table-sub');
                    $ttable->removeCss('table-striped table-hover');
                    //$ttable->getRenderer()->setAttr('style', 'display:none;');

                    $ttable->appendCell(\Tk\Table\Cell\Text::create('placement_id'))->addCss('key')
                        ->setOnPropertyValue(function ($cell, $obj, $value) {
                            /** @var $cell \Tk\Table\Cell\Text */
                            /** @var $obj \stdClass */
                            /** @var \App\Db\Placement $placement */
                            $placement = \App\Db\PlacementMap::create()->find($value);
                            if ($placement)
                                return $placement->getTitle(true);
                            return $value;
                        });
                    $ttable->appendCell(\Tk\Table\Cell\Text::create('supervisor_id'))
                        ->setOnPropertyValue(function ($cell, $obj, $value) {
                            /** @var $cell \Tk\Table\Cell\Text */
                            /** @var $obj \stdClass */
                            /** @var \App\Db\Supervisor $supervisor */
                            $supervisor = \App\Db\SupervisorMap::create()->find($value);
                            if ($supervisor) {
                                $value = $supervisor->name;
                                if ($supervisor->academic) {
                                    $value .= ' [aa]';
                                }
                            }
                            return $value;
                        });
                    $ttable->appendCell(\Tk\Table\Cell\Text::create('avg'));
                    $ttable->appendCell(\Tk\Table\Cell\Text::create('pct'));
                    //$ttable->appendCell(\Tk\Table\Cell\Date::createDate('date_start', \Tk\Date::FORMAT_SHORT_DATE));
                    $ttable->setList($list);
                    $ttable->getRenderer()->getTemplate()->setAttr('tk-table', 'style', 'display: none;');
                    $ttable->getRenderer()->enableFooter(false);
                    $tbl = $ttable->getRenderer()->show()->toString();
                }

                return $html . $tbl;
            });
        $this->appendCell(new \Tk\Table\Cell\Text('min'));
        $this->appendCell(new \Tk\Table\Cell\Text('max'));
        $this->appendCell(new \Tk\Table\Cell\Text('pct'));
        $this->appendCell(new \Tk\Table\Cell\Text('avg'));
        $this->appendCell(new \Tk\Table\Cell\Text('entry_count'));
        $this->appendCell(\Tk\Table\Cell\Date::createDate('created', \Tk\Date::FORMAT_SHORT_DATE));


        // Filters
        $values = array();

        $list = \App\Db\CompanyMap::create()->findFiltered(array(
            'profileId' => $this->getConfig()->getProfileId(),
            'placementSubjectId' => $this->getConfig()->getSubjectId(),
            'status' => array('approved'),
            'placementsOnly' => true
        ), \Tk\Db\Tool::create('name'));
        $this->appendFilter(new Field\CheckboxSelect('companyId', $list));

        $this->appendFilter(new Field\Input('minEntries'))->setAttr('placeholder', 'Minimum Entries/Placements');


        $this->getFilterForm()->load($values);
        // Actions
        $this->appendAction(\Tk\Table\Action\Csv::create());


        $js = <<<JS
jQuery(function ($) {
  $('.mName').each(function () {
    var trigger = $(this).find('> a');
    var table = $(this).find('> .tk-table');
    var upIcon = 'fa-caret-up';
    var dnIcon = 'fa-caret-down';
    
    trigger.append(' <i class="fa fa-caret-up"></i>');
    table.hide();
    
    trigger.on('click', function () {
      if (table.isVisible()) {
         $(this).find('.fa').removeClass(dnIcon).addClass(upIcon);
         //table.hide();
         table.slideUp();
      } else {
         $(this).find('.fa').removeClass(upIcon).addClass(dnIcon);
         //table.show();
         table.slideDown();
      }
    });
  });
});
JS;
        $this->getRenderer()->getTemplate()->appendJs($js);






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
        $list = \Skill\Db\ReportingMap::create()->findCompanyTotalAverage($filter, $tool);
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