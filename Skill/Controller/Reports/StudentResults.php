<?php
namespace Skill\Controller\Reports;

use App\Controller\AdminIface;
use Dom\Template;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class StudentResults extends AdminIface
{

    /**
     * @var \App\Db\User
     */
    protected $user = null;

    /**
     * @var \Skill\Db\Collection
     */
    protected $collection = null;


    /**
     * Results constructor.
     * @throws \Tk\Db\Exception
     */
    public function __construct()
    {
        set_time_limit(0);
        parent::__construct();
        $this->setPageTitle('Results');
        if ($this->getUser()->isStudent()) {
            $this->getActionPanel()->setEnabled(false);
        }
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->user = \App\Db\UserMap::create()->find($request->get('userId'));
        if (!$this->user) {
            $this->user = $this->getUser();
        }

        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));

        if (!$this->collection->active && !$this->getUser()->isStaff()) {
            throw new \Tk\Exception('This page is not available.');
        }
    }

    /**
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        $template = parent::show();

        if ($this->collection->icon) {
            $template->addCss('icon', $this->collection->icon);
        }

        $panelTitle = sprintf('%s Results for `%s`', $this->collection->name, $this->user->name);
        $template->insertText('panel-title', $panelTitle);

        $filter = array(
            'userId' => $this->user->getId(),
            'collectionId' => $this->collection->getId(),
            'subjectId' => $this->getSubject()->getId(),
            'status' => \Skill\Db\Entry::STATUS_APPROVED,
            'placementStatus' => \App\Db\Placement::STATUS_COMPLETED
        );
        $entryList = \Skill\Db\EntryMap::create()->findFiltered($filter, \Tk\Db\Tool::create('created DESC'));

        $template->insertText('entryCount', $entryList->count());
        $studentResult =  \Skill\Db\ReportingMap::create()->findStudentResult($this->collection->getId(), $this->getSubject()->getId(), $this->user->getId(), true);

        $template->setAttr('grade', 'title', $studentResult);
        if ($this->getConfig()->isDebug()) {
            $template->setChoice('debug');
        }
        $template->insertText('avg', sprintf('%.2f / %d', $studentResult*($this->collection->getScaleLength()-1), $this->collection->getScaleLength()-1));
        $template->insertText('grade', sprintf('%.2f / %d', $studentResult*$this->collection->maxGrade, $this->collection->maxGrade));
        $template->insertText('gradePcnt', sprintf('%.2f', $studentResult*100) . '%');

        $domainResults = \Skill\Db\ReportingMap::create()->findDomainAverages($this->collection->getId(), $this->getSubject()->getId(), $this->user->getId());
        $domainList = \Skill\Db\DomainMap::create()->findFiltered(array('collectionId' => $this->collection->getId(), 'active' => true), \Tk\Db\Tool::create());

        // TODO: Could look at using a pie chart for this information
        foreach ($domainList as $domain) {
            $obj = null;
            if (!empty($domainResults[$domain->getId()]))
                $obj = $domainResults[$domain->getId()];

            $row = $template->getRepeat('domain-row');

            $row->insertText('name', $domain->name . ' (' . $domain->label . ')');
            $row->insertText('weight', round($domain->weight * 100) . '%');
            if ($obj) {
                $row->insertText('avg', sprintf('%.2f', $obj->avg));
                $row->insertText('grade', sprintf('%.2f', ($obj->avg / $obj->scale) * $this->collection->maxGrade));
            } else {
                $row->insertText('avg', sprintf('%.2f', 0));
                $row->insertText('grade', sprintf('%.2f', 0));
            }
            $row->appendRepeat();
        }

        $itemResults = \Skill\Db\ReportingMap::create()->findItemAverages($this->collection->getId(), $this->getSubject()->getId(), $this->user->getId());
        $catList = \Skill\Db\CategoryMap::create()->findFiltered(array(
            'collectionId' => $this->collection->getId()
        ));

        $i = 0;
        foreach ($catList as $category) {
            $catRow = $template->getRepeat('category-row');
            $catRow->insertText('name', $category->name . ' (' . $category->label . ')');

            $itemList = \Skill\Db\ItemMap::create()->findFiltered(array(
                'collectionId' => $this->collection->getId(),
                'categoryId' => $category->getId()
            ));
            if (!$itemList->count()) continue;
            foreach ($itemList as $item) {
                $obj = null;
                if (isset($itemResults[$item->getId()]))
                    $obj = $itemResults[$item->getId()];

                $row = $catRow->getRepeat('item-row');
                $row->insertText('lineNo', ($i+1).'. ');
                $row->insertText('question', $item->question);

                $avg = 0;
                if($obj)
                    $avg = $obj->avg;

                $row->insertText('result', sprintf('%.2f', $avg));
                if ($avg <= 0) {
                    $row->addCss('result', 'zero');
                }
                $row->appendRepeat();
                $i++;
            }
            $catRow->appendRepeat();
        }

        
        
        // TODO: plot a graph of all completed entry averages

        // include Flot
        \App\Ui\Js::includeFlot($template);
        // placement activity
        $template->appendJsUrl(\Tk\Uri::create('/html/app/js/flot/monthlyLineGraph.js'));
        $css = <<<CSS
.plot {
  height: 220px;
}
CSS;
        $template->appendCss($css);
        $template->setAttr('line-chart', 'data-ymax', $this->collection->getScaleLength()-1);
        $template->setAttr('line-chart', 'data-type', 'float');

        $dataRow = $template->getRepeat('chart-row');
        $dataRow->insertText('chart-label', 'Calc. Grade');

        $entryList = \Skill\Db\EntryMap::create()->findFiltered(array(
            'userId' => $this->user->getId(), 'subjectId' => $this->getSubject()->getId(), 'status' => \Skill\Db\Entry::STATUS_APPROVED)
        );
        /** @var \Skill\Db\Entry $entry */
        foreach ($entryList as $entry) {
            $th = $template->getRepeat('month');
            $th->insertText('month', $entry->created->format(\Tk\Date::FORMAT_MED_DATE));
            $th->appendRepeat();

            $data = $dataRow->getRepeat('chart-data');
            $data->insertText('chart-data', $entry->weightedAverage);
            $data->setAttr('chart-data', 'data-date', $entry->created->getTimestamp()*1000);
            $data->appendRepeat();
        }
        $dataRow->appendRepeat();

        // Get subject class totals
        $res = \Skill\Util\Calculator::findSubjectAverageGrades($this->collection, $this->getSubject());
        if ($res->count) {
            $template->insertText('class-min', round($res->min*100, 2) . '%');
            $template->insertText('class-median', round($res->median*100, 2) . '%');
            $template->insertText('class-max', round($res->max*100, 2) . '%');
        }




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
<div class="EntryResults">

  <style media="all">
    .EntryResults .category-row {
      border: 1px solid #EEE;
      border-radius: 5px;
      background: #FEFEFE;
      margin: 10px 0px;
      box-shadow: 1px 1px 2px #CCC;
      padding: 10px 0px;
    }
    .EntryResults .item-row {
      border: 1px solid #EEE;
      border-radius: 5px;
      background: #FFF;
      margin: 5px;
      padding: 5px;
      font-size: 1.1em;
    }
    .EntryResults .item-row:nth-child(even) {
      background-color: {$this->collection->color};
    }
    .EntryResults .item-row .question {
      padding-left: 20px;
      margin: 5px 0px;
    }
    .EntryResults .item-row .question .lineNo {
      display: inline-block;
      padding-right: 10px;
    }
    .EntryResults .item-row .answer {
      text-align: right;
    }
    .EntryResults .item-row .result.zero {
      color: #999;
    }
  </style>
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><i class="fa fa-eye" var="icon"></i> <span var="panel-title">Skill Entry Results</span></h4>
      <div class="pull-right"><h6>Placements Assessed: <span var="entryCount"></span></h6></div>
    </div>
    <div class="panel-body">

      <div class="col-lg-6">
        <table class="table keyvalue-table">
          <tbody>
          <!--<tr>-->
            <!--<td class="kv-key"><i class="fa fa-hashtag kv-icon kv-icon-default"></i> Placements Assessed</td>-->
            <!--<td class="kv-value" var="entryCount">0</td>-->
          <!--</tr>-->
          <tr>
            <td class="kv-key"><i class="fa fa-calculator kv-icon kv-icon-danger"></i> Calculated Grade</td>
            <td class="kv-value" var="gradePcnt">0.00%</td>
          </tr>
          
          <tr>
            <td class="kv-key"><i class="fa fa-thermometer-4 kv-icon kv-icon-primary"></i> Class Max.</td>
            <td class="kv-value" var="class-max">0.00%</td>
          </tr>
          <tr>
            <td class="kv-key"><i class="fa fa-thermometer-3 kv-icon kv-icon-secondary"></i> Class Median</td>
            <td class="kv-value" var="class-median">0.00%</td>
          </tr>
          <tr>
            <td class="kv-key"><i class="fa fa-thermometer-1 kv-icon kv-icon-tertiary"></i> Class Min.</td>
            <td class="kv-value" var="class-min">0.00%</td>
          </tr>
          
          <tr choice="debug">
            <td class="kv-key"><i class="fa fa-exchange kv-icon kv-icon-tertiary"></i> Average Response</td>
            <td class="kv-value" var="avg">0</td>
          </tr>
          <tr choice="debug">
            <td class="kv-key"><i class="fa fa-graduation-cap kv-icon kv-icon-primary"></i> Calculated Grade</td>
            <td class="kv-value" var="grade">0.0</td>
          </tr>
          </tbody>
        </table>
      </div>
      <div class="col-lg-4" choice="hide">
        <table class="table line-chart" var="line-chart">
          <tr>
            <th>Entries</th>
            <th repeat="month" var="month"></th>
          </tr>
          <tr repeat="chart-row">
            <th var="chart-label">Average</th>
            <td repeat="chart-data" var="chart-data">0.0</td>
          </tr>
        </table>
      </div>
      <div class="col-lg-6">
        <table class="table table-bordered">
          <tr>
            <th>Domain</th>
            <th>Avg.</th>
            <th>Weight</th>
            <th>Grade</th>
          </tr>
          <tr repeat="domain-row" var="domain-row">
            <td var="name"></td>
            <td var="avg"></td>
            <td var="weight"></td>
            <td var="grade"></td>
          </tr>
        </table>
      </div>
      

      <div class="col-xs-12 category-row clearfix" repeat="category-row">
        <div class="col-xs-12">
          <div><h4 class="category-name" var="name">Category Name</h4></div>
          <div class="row item-row" repeat="item-row" var="item-row">
            <div class="col-xs-10 question"><span class="lineNo" var="lineNo">0.</span> <span var="question"></span>
            </div>
            <div class="col-xs-2 text-center"><span class="result" var="result">0.00</span></div>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}