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
class CollectionReport extends \App\TableIface
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
        $this->appendCell(new \Tk\Table\Cell\Text('uid'))->setLabel('studentNumber');
        $this->appendCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl($this->getEditUrl())
            ->addOnPropertyValue(function ($cell, $obj, $value) {
                /** @var \Tk\Table\Cell\Text $cell */
                $cell->setUrl($cell->getUrl()->set('userId', $obj->getId()));
                return $value;
            });

        // Student Results
        $filter = array();
        $filter['collectionId'] = $this->getCollectionObj()->getId();
        $filter['subjectId'] = $this->getConfig()->getSubjectId();
        $filter['userId'] = 0;


        //\Tk\Log::alert('\Skill\Db\ReportingMap::create()->findStudentResult(..START..)');

        $calc = new \Skill\Util\GradeCalculator($this->getCollectionObj());
        //$calc->setCacheEnabled(false);
        $filter = array();
        if($this->getFilterSession()->has('exclude')) {
            $filter['notCompanyId'] = explode(',', str_replace(' ', '', $this->getFilterSession()->get('exclude')));
        }
        $results = $calc->getSubjectGrades($filter);

        //\Tk\Log::alert('\Skill\Db\ReportingMap::create()->findStudentResult(..END..)');

        $gradeList = $results->gradeList;

        $domains = \Skill\Db\DomainMap::create()->findFiltered(array('collectionId'=>$this->getCollectionObj()->getId(), 'active' => true));
        //$domains = \Skill\Db\DomainMap::create()->findFiltered(array('collectionId'=>$this->collection->getId()));
        foreach ($domains as $domain) {
            $this->appendCell(new \Tk\Table\Cell\Text($domain->label))->setLabel($domain->label)->setOrderProperty('')
                ->addOnPropertyValue(function ($cell, $obj, $value) use ($domain, $gradeList) {
                    /** @var \Tk\Table\Cell\Text $cell */
                    /** @var \Uni\Db\User $obj */
                    if (isset($gradeList[$obj->getId()])) {
                        /** @var \Skill\Util\Grade $grade */
                        $grade = $gradeList[$obj->getId()];
                        $list = $grade->getDomainAvgList();
                        if (!empty($list[$domain->getId()])) {
                            return sprintf('%.2f', round($list[$domain->getId()]['avg'], 2));
                            //return sprintf('%.2f', round($list[$domain->getId()]['weightedAvg'], 2));
                        }
                    }
                    return '0.00';
                });
        }

        $this->appendCell(new \Tk\Table\Cell\Text('total'))->setOrderProperty('')->setLabel('Total Avg.')
            ->addOnPropertyValue(function ($cell, $obj, $value) use ($gradeList) {
                /** @var \Tk\Table\Cell\Text $cell */
                /** @var \Uni\Db\User $obj */
                $cell->addCss('total');

                if (isset($gradeList[$obj->getId()])) {
                    /** @var \Skill\Util\Grade $grade */
                    $grade = $gradeList[$obj->getId()];
                    return sprintf('%.2f', round($grade->getAverage(), 2) );
                    //return sprintf('%.2f', round($grade->getWeightedAverage(), 2) );
                }
                return '0.00';
            });

        foreach ($domains as $domain) {
            $this->appendCell(new \Tk\Table\Cell\Text($domain->label.'Grade'))->setOrderProperty('')->setLabel($domain->label.' Grade')
                ->addOnPropertyValue(function ($cell, $obj, $value) use ($domain, $gradeList) {
                    /** @var \Tk\Table\Cell\Text $cell */
                    /** @var \Uni\Db\User $obj */
                    if (isset($gradeList[$obj->getId()])) {
                        /** @var \Skill\Util\Grade $grade */
                        $grade = $gradeList[$obj->getId()];
                        $list = $grade->getDomainAvgList();
                        if (!empty($list[$domain->getId()])) {
                            return sprintf('%.2f', round($list[$domain->getId()]['avg']*$grade->getGradeMultiplier(), 2));
                            //return sprintf('%.2f', round($list[$domain->getId()]['weightedAvg']*$grade->getGradeMultiplier(), 2));
                        }
                    }
                    return '0.00';
                });
        };
        $this->appendCell(new \Tk\Table\Cell\Text('totalGrade'))->setOrderProperty('')
            ->addOnPropertyValue(function ($cell, $obj, $value) use ($gradeList) {
                /** @var \Tk\Table\Cell\Text $cell */
                /** @var \Uni\Db\User $obj */
                $cell->addCss('total');

                if (isset($gradeList[$obj->getId()])) {
                    /** @var \Skill\Util\Grade $grade */
                    $grade = $gradeList[$obj->getId()];
                    //return sprintf('%.2f', round($grade->getGrade(), 2));
                    return sprintf('%.2f', round($grade->getWeightedGrade(), 2));
                }
                return '0.00';
            });
        $this->appendCell(new \Tk\Table\Cell\Text('totalPct'))->setOrderProperty('')
            ->addOnPropertyValue(function ($cell, $obj, $value) use ($gradeList) {
                /** @var \Tk\Table\Cell\Text $cell */
                /** @var \Uni\Db\User $obj */

                if (isset($gradeList[$obj->getId()])) {
                    /** @var \Skill\Util\Grade $grade */
                    $grade = $gradeList[$obj->getId()];
                    //return sprintf('%.2f%%', round($grade->getPercent(), 2) );
                    return sprintf('%.2f%%', round($grade->getWeightedPercent(), 2) );
                }
                return '0.00';
            });

        // Filters
        $this->appendFilter(new \Tk\Form\Field\Input('uid'))->setAttr('placeholder', 'Student Number');
        if ($this->getAuthUser()->isCoordinator()) {
            $this->appendFilter(new \Tk\Form\Field\Input('exclude'))->setAttr('style', 'width: 250px;')
                ->setAttr('placeholder', 'Exclude companyId (EG: 123, 412, 231)');
        }

        // Actions
        //$this->appendAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name')));
        $this->appendAction(\Tk\Table\Action\Csv::create());

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
        if (!$tool) $tool = $this->getTool('a.name_first', 0);
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = $this->getConfig()->getUserMapper()->findFiltered($filter, $tool);
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
    public function setCollectionObj(\Skill\Db\Collection $collection): CollectionReport
    {
        $this->_collection = $collection;
        return $this;
    }

}