<?php
namespace Skill\Controller\Collection;

use Dom\Template;
use Tk\Request;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Report extends \App\Controller\AdminManagerIface
{

    /**
     * @var \Skill\Db\Collection
     */
    protected $collection = null;

    /**
     * @var null|\Tk\Uri
     */
    protected $editUrl = null;


    /**
     * Iface constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Collection Report');
    }

    /**
     * @param Request $request
     * @throws \Exception
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     * @throws \Tk\Form\Exception
     */
    public function doDefault(Request $request)
    {
        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));
        $this->setPageTitle( $this->collection->name . ' Report');

        if (!$this->collection->gradable) {
            throw new \Tk\Exception('A report is not available for this collection.');
        }
        if ($this->editUrl === null)
            $this->editUrl = \Uni\Uri::createSubjectUrl('/entryResults.html')->set('collectionId', $this->collection->getId());

        $this->table = \App\Config::getInstance()->createTable(\App\Config::getInstance()->getUrlName());
        $this->table->setRenderer(\App\Config::getInstance()->createTableRenderer($this->table));

        $this->table->addCell(new \Tk\Table\Cell\Text('uid'))->setLabel('studentNumber');
        $this->table->addCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl(clone $this->editUrl)
            ->setOnPropertyValue(function ($cell, $obj, $value) {
                /** @var \Tk\Table\Cell\Text $cell */
                $cell->setUrl($cell->getUrl()->set('userId', $obj->getId()));
                return $value;
            });

        // Student Results
        $filter = array();
        $filter['collectionId'] = $this->collection->getId();
        $filter['subjectId'] = $this->getSubject()->getId();
        $filter['userId'] = 0;
        $results = \Skill\Db\ReportingMap::create()->findStudentResults($filter, \Tk\Db\Tool::create('', 0));

        $domains = \Skill\Db\DomainMap::create()->findFiltered(array('collectionId'=>$this->collection->getId(), 'active' => true));
        foreach ($domains as $domain) {
            $this->table->addCell(new \Tk\Table\Cell\Text($domain->label))->setLabel($domain->label)->setOnPropertyValue(function ($cell, $obj, $value) use ($results) {
                /** @var \Tk\Table\Cell\Text $cell */
                $res = array();
                if (!empty($results[$obj->getId()]))
                    $res = $results[$obj->getId()];

                if (array_key_exists($cell->getProperty(), $res)) {
                    return sprintf('%.2f', $res[$cell->getProperty()]);
                }
                return '0.00';
            });
        }
        foreach ($domains as $domain) {
            $this->table->addCell(new \Tk\Table\Cell\Text($domain->label.'Grade'))->setLabel($domain->label.' Grade')->setOnPropertyValue(function ($cell, $obj, $value) use ($domain, $results) {
                /** @var \Tk\Table\Cell\Text $cell */
                $prop = $domain->label.'_grade';
                $res = array();
                if (!empty($results[$obj->getId()]))
                    $res = $results[$obj->getId()];
                if (array_key_exists($prop, $res)) {
                    return sprintf('%.2f', round($res[$prop], 2));
                }
                return '0.00';
            });
        }


        // Filters
        $this->table->addFilter(new \Tk\Form\Field\Input('uid'))->setAttr('placeholder', 'Student Number');

        // Actions
        //$this->table->addAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name')));
        $this->table->addAction(\Tk\Table\Action\Csv::create());

        $this->table->setList($this->getList());


        //$list = \Skill\Db\ReportingMap::create()->findDomainAverages($this->collection->getId(), $this->getSubject()->getId(), 1494);

        //  TODO:
        //  TODO: We have to figure this one out, disable it for now.
        //  TODO:
        //  TODO:
        //  TODO:

    }


    /**
     * @return \Skill\Db\Collection[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    protected function getList()
    {
        $filter = $this->table->getFilterValues();
        $this->table->resetSessionTool();

        //$filter['collectionId'] = $this->collection->getId();
        $filter['subjectId'] = $this->getSubject()->getId();
        //$filter['userId'] = 0;

        return \App\Db\UserMap::create()->findFiltered($filter, $this->table->getTool('a.name', 0));
        //return \Skill\Db\ReportingMap::create()->findStudentResults($filter, $this->table->getTool('a.name', 0));
    }


    /**
     * @return \Tk\Db\Map\ArrayObject
     * @throws \Exception
     * @throws \Tk\Db\Exception
     */
    public function getListOld()
    {
        $db = $this->getConfig()->getDb();

        // Cells Required:     | companyName | isAcademic | animalName | avgUnits | numPlacements | numAnimals |
        //                     ---------------------------------------------------------------------------------

        $tool = $this->table->getTool('d.name, a.name');
        $filter = $this->table->getFilterValues();
        $filter['profileId'] = $this->getSubject()->profileId;
        $filter['subjectId'] = $this->getSubject()->getId();


        $where = '';

        if (!empty($filter['companyId'])) {
            $where .= sprintf('c.company_id = %d AND ', (int)$filter['companyId']);
        }
        if (!empty($filter['subjectId'])) {
            $where .= sprintf('c.subject_id = %d AND ', (int)$filter['subjectId']);
        } else {
            if (!empty($filter['profileId'])) {
                $where .= sprintf('b.profile_id = %d AND ', (int)$filter['profileId']);
            }
        }

        if (!empty($filter['dateStart']) && !empty($filter['dateEnd'])) {     // Contains
            $start = $filter['dateStart'];
            $end = $filter['dateEnd'];
            $where .= sprintf('c.dateStart >= %s AND ', $db->quote(\Tk\Date::floor($start)->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $where .= sprintf('c.dateStart <= %s AND ', $db->quote(\Tk\Date::ceil($end)->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        }
        if ($where) {
            $where = substr($where, 0, -4);
        }

        // Query
        $toolStr = '';
        if ($tool) {
            $toolStr = $tool->toSql();
        }

        $sql = sprintf('SELECT SQL_CALC_FOUND_ROWS d.name as \'companyName\', a.name as \'species\', ROUND(AVG(c.units), 1) as \'duration\', 
            COUNT(c.id) as \'rotationCount\', 1 as \'studentPerRotation\', SUM(a.value) AS \'animalCount\', e.academic
FROM animal_value a, animal_type b, placement c, company d,
 (
   SELECT a.id, c.academic as \'academic\'
   FROM company a, company_has_supervisor b, supervisor c
   WHERE a.id = b.company_id AND b.supervisor_id = c.id
   GROUP BY a.id
 ) e

WHERE a.type_id = b.id AND a.type_id > 0 AND b.del = 0 AND a.placement_id = c.id AND
      c.company_id = d.id AND d.id = e.id AND c.del = 0 AND d.del = 0 AND %s
GROUP BY c.company_id, a.type_id
%s', $where, $toolStr);


        $res = $db->query($sql);
        return \Tk\Db\Map\ArrayObject::create($res, $tool);
    }




    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $template->replaceTemplate('table', $this->table->getRenderer()->show());

        if ($this->collection->icon) {
            $template->addCss('icon', $this->collection->icon);
        }
        $panelTitle = sprintf('%s Report', $this->collection->name);
        $template->insertText('panel-title', $panelTitle);



        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="skill-report">

  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <i class="fa fa-eye" var="icon"></i>
        <span var="panel-title">Skill Report</span>
      </h4>
    </div>
    <div class="panel-body">
      <div var="table"></div>
    </div>
  </div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}