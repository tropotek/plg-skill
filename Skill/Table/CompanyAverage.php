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
        set_time_limit(0);
        parent::__construct($tableId);
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function init()
    {
        $collection = $this->getCollectionObject();

        $this->appendCell(new \Tk\Table\Cell\Text('company_id'));
        $this->appendCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl(\Uni\Uri::create('#'))
            ->setOnPropertyValue(function ($cell, $obj, $value) use ($collection) {
                /** @var $cell \Tk\Table\Cell\Text */
                /** @var $obj \stdClass */
                $config = \App\Config::getInstance();
                $subjectId = array();
                if ($config->getSubjectId()) $subjectId = array($config->getSubjectId());
                if ($cell->getTable()->getFilterForm()->getField('subjectId')->getValue())
                    $subjectId = $cell->getTable()->getFilterForm()->getField('subjectId')->getValue();
                $filter = array('collectionUid' => $collection->uid, 'companyId' => $obj->company_id, 'subjectId' => $subjectId);

                if ($cell->getTable()->getFilterForm()->getField('date')->getValue()) {
                    $filter = array_merge($filter, $cell->getTable()->getFilterForm()->getField('date')->getValue());
                }
                $list = \Skill\Db\ReportingMap::create()->findCompanyAverage($filter,
                    \Tk\Db\Tool::create('b.date_start DESC', 0));
                $cell->getRow()->set('cellList', $list);

                return $value;
            })
            ->setOnCellHtml(function ($cell, $obj, $html) use ($collection) {
                /** @var $cell \Tk\Table\Cell\Text */
                /** @var $obj \stdClass */
                $config = \App\Config::getInstance();

                $list = $cell->getRow()->get('cellList');
                $tbl = '';
                if ($list->count()) {
                    $ttable = $config->createTable('ptable-'.$obj->company_id);
                    $ttable->setRenderer($config->createTableRenderer($ttable));
                    $ttable->setStaticOrderBy('');
                    $ttable->addCss('table-sm table-sub');
                    $ttable->removeCss('table-striped table-hover');
                    //$ttable->getRenderer()->setAttr('style', 'display:none;');

                    $ttable->appendCell(\Tk\Table\Cell\Text::create('company_id'));
                    $ttable->appendCell(\Tk\Table\Cell\Text::create('placement_id'))->addCss('key')
                        ->setUrlProperty(null)
                        ->setOnPropertyValue(function ($cell, $obj, $value) {
                            /** @var $cell \Tk\Table\Cell\Text */
                            /** @var $obj \stdClass */
                            /** @var \App\Db\Placement $placement */
                            $placement = \App\Db\PlacementMap::create()->find($value);
                            $cell->setAttr('data-slt-title', 'N/A');
                            $cell->setUrl(\Uni\Uri::createSubjectUrl('/placementEdit.html', $placement->getSubject())->set('placementId', $placement->getId()), null);

                            if ($placement) {
                                if ($placement->getUser()) {
                                    $cell->setAttr('data-slt-title', $placement->getUser()->getName() . ' - ' . $placement->getDateStart()->format(\Tk\Date::FORMAT_SHORT_DATE));
                                }
                                return $placement->getTitle(true);
                            }
                            return $value;
                        })->setOnCellHtml(function ($cell, $obj, $html) {
                            /** @var $cell \Tk\Table\Cell\Text */
                            /** @var $obj \stdClass */
                            $value = $propValue = $cell->getPropertyValue($obj);
                            if ($cell->getCharLimit() && strlen($propValue) > $cell->getCharLimit()) {
                                $propValue = \Tk\Str::wordcat($propValue, $cell->getCharLimit()-3, '...');
                            }
                            if (!$cell->hasAttr('title')) {
                                $cell->setAttr('title', htmlentities($propValue));
                            }

                            $str = htmlentities($propValue);
                            $url = $cell->getCellUrl($obj);
                            if ($url) {
                                $str = sprintf('<a href="%s" title="Click to view placement" target="_blank">%s</a>', htmlentities($url->toString()), htmlentities($propValue));
                            }
                            return $str;
                        });
                    $ttable->appendCell(\Tk\Table\Cell\Text::create('supervisor_id'))
                        ->setOnPropertyValue(function ($cell, $obj, $value) {
                            /** @var $cell \Tk\Table\Cell\Text */
                            /** @var $obj \stdClass */
                            /** @var \App\Db\Supervisor $supervisor */
                            if (!$value) $value = 'N/A';
                            $supervisor = \App\Db\SupervisorMap::create()->find($value);
                            if ($supervisor) {
                                $value = $supervisor->name;
                                if ($supervisor->academic) {
                                    $value .= ' [AA]';
                                }
                            }
                            return $value;
                        });
                    $ttable->appendCell(\Tk\Table\Cell\Text::create('avg'));
                    $ttable->appendCell(\Tk\Table\Cell\Text::create('pct'));

                    $ttable->appendAction(\Tk\Table\Action\Csv::create());
                    //$ttable->appendCell(\Tk\Table\Cell\Date::createDate('date_start', \Tk\Date::FORMAT_SHORT_DATE));
                    $ttable->setList($list);
                    $ttable->getRenderer()->getTemplate()->setAttr('tk-table', 'style', 'display: none;');
                    $ttable->getRenderer()->enableFooter(false);
                    $tbl = $ttable->getRenderer()->show()->toString();
                }

                return $html . $tbl;
            });
        $this->appendCell(new \Tk\Table\Cell\Text('graph'))
            ->setOrderProperty('avg')
            ->setOnPropertyValue(function ($cell, $obj, $value) {
                $list = $cell->getRow()->get('cellList');
                $value = implode('; ', $list->toArray('pct'));
                return $value;
            })
            ->setOnCellHtml(function ($cell, $obj, $html) {
              /** @var $cell \Tk\Table\Cell\Text */
              /** @var $obj \stdClass */
              $cell->setAttr('title', '');
              return sprintf('<span class="%s" data-company-id="%d"></span>', 'spark', $obj->company_id);
            });
        $this->appendCell(new \Tk\Table\Cell\Text('min'));
        $this->appendCell(new \Tk\Table\Cell\Text('max'));
        $this->appendCell(new \Tk\Table\Cell\Text('pct'));
        $this->appendCell(new \Tk\Table\Cell\Text('avg'));
        $this->appendCell(new \Tk\Table\Cell\Text('entry_count'));
        $this->appendCell(\Tk\Table\Cell\Date::createDate('created', \Tk\Date::FORMAT_SHORT_DATE));

        // Filters
        $values = array();
//        $values = array(
//            'dateStart' => $this->getConfig()->getSubject()->dateStart->format(\Tk\Date::$formFormat),
//            'dateEnd' => $this->getConfig()->getSubject()->dateEnd->format(\Tk\Date::$formFormat)
//        );

        $list = \App\Db\CompanyMap::create()->findFiltered(array(
            'courseId' => $this->getConfig()->getCourseId(),
            'placementSubjectId' => $this->getConfig()->getSubjectId(),
            'status' => array('approved'),
            'placementsOnly' => true
        ), \Tk\Db\Tool::create('name'));
        $this->appendFilter(new Field\CheckboxSelect('companyId', $list));

        $list = \App\Db\SubjectMap::create()->findFiltered(array('courseId' => $this->getConfig()->getCourseId()),
            \Tk\Db\Tool::create('a.name DESC'));
        $c = $this->appendFilter(new Field\CheckboxSelect('subjectId', \Tk\Form\Field\Option\ArrayObjectIterator::create($list)));
        if ($this->getConfig()->getSubjectId()) {
            $c->setValue(array($this->getConfig()->getSubjectId()));
        }

        $this->appendFilter(new Field\Input('minEntries'))->setAttr('placeholder', 'Minimum Entries/Placements');
        $this->appendFilter(new Field\DateRange('date')); //->setValue($values);

        // Actions
        $this->appendAction(\Tk\Table\Action\Csv::create());


        //$this->appendAction(\Tk\Table\Action\ColumnSelect::create()->addUnselected(array('company_id')));


        $this->getFilterForm()->load($values);


        $js = <<<JS
jQuery(function ($) {
  $('.mName').each(function () {
    //var trigger = $(this).find('> a');
    var trigger = $(this);
    var table = $(this).find('> .tk-table');
    var upIcon = 'fa-caret-up';
    var dnIcon = 'fa-caret-down';

    trigger.append(' <i class="fa fa-caret-up pull-right" style="position: absolute; right: 10px; top: 5px;"></i>');
    table.hide();

    trigger.on('click', function (e) {
      console.log(e);
      if ($(e.target).is('a[target="_blank"], button')) return;
      if (table.isVisible()) {
        $(this).find('.fa.fa-caret-up').removeClass(dnIcon).addClass(upIcon);
        //table.hide();
        table.slideUp();
      } else {
        $(this).find('.fafa-caret-up').removeClass(upIcon).addClass(dnIcon);
        //table.show();
        table.slideDown();
      }
    });
  });

  //$('td.mGraph .spark').html('<i class="fa fa-spinner fa-spin"></i>');
  
  $('td.mGraph .spark').each(function () {
    var spark = $(this);
    var table = spark.closest('tr').find('.mName table');
    if (!table) {
      return;
    }
    var data = [];
    var names = [];
    table.find('.mPct').each(function () {
      data.push(Math.round($(this).text(), 2));
      names.push($(this).parent('tr').find('.mPlacement_id').data('sltTitle'));
    });
    spark.empty().sparkline(data, {
      type: 'bar',
      chartRangeMin: 0,
      chartRangeMax: 100,
      colorMap: {':49': 'red', '50:69': 'orange', '70:': 'blue'},
      barWidth: 5,
      tooltipFormat: $.spformat('{{offset:offset}} [{{value}}%]', 'tooltip-class'),
      tooltipValueLookups: {
        'offset': names
    }
    });

  });

});
JS;
        $this->getRenderer()->getTemplate()->appendJs($js);

        $css = <<<CSS
.jqstooltip {
  -webkit-box-sizing: content-box;
  -moz-box-sizing: content-box;
  box-sizing: content-box;
}
.mName {
  position: relative;
}
.mName:hover {
  cursor: pointer;
}
.mName .tk-table {
  margin-top: 15px;
}
CSS;
        $this->getRenderer()->getTemplate()->appendCss($css);

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
        $list = array();
        try {
            if (!$tool) $tool = $this->getTool('');
            $filter = array_merge($this->getFilterValues(), $filter);
            $list = \Skill\Db\ReportingMap::create()->findCompanyTotalAverage($filter, $tool);
        } catch (\Tk\Db\Exception $e) {
            if (strstr($e->getMessage(), 'Column not found')) {
                \Tk\Log::error($e->__toString());
                $this->resetSessionTool();
                \Tk\Alert::addWarning('Table order reset due to error!');
                \Tk\Uri::create()->redirect();
            }
            throw $e;
        }
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