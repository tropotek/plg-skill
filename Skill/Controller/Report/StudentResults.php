<?php
namespace Skill\Controller\Report;

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
     */
    public function __construct()
    {
        $this->setPageTitle('Results');
        if ($this->getAuthUser()->isStudent()) {
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
            $this->user = $this->getAuthUser();
        }

        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));

        if (!$this->collection->isActive() && !$this->getAuthUser()->isStaff()) {
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

        $panelTitle = sprintf('%s Results for `%s`', $this->collection->getName(), $this->user->getName());
        $template->insertText('panel-title', $panelTitle);

        $filter = array();

        $calc = new \Skill\Util\GradeCalculator($this->collection);
        //$calc->setCacheEnabled(false);
        $results = $calc->getSubjectGrades($filter);

        // Get class totals
        $template->insertText('class-min', sprintf('%.2f%%', $results->min));
        $template->insertText('class-median', sprintf('%.2f%%', $results->median));
        $template->insertText('class-max', sprintf('%.2f%%', $results->max));


        /** @var \Skill\Util\Grade $studentGrade */
        $studentGrade = $calc->getStudentGrade($this->user, $filter);

        $template->insertText('avg', sprintf('%.2f / %d', $studentGrade->getAverage(), $this->collection->getScaleCount()));
        $template->insertText('grade', sprintf('%.2f / %d', $studentGrade->getWeightedGrade(), $this->collection->maxGrade));
        $template->insertText('gradePcnt', sprintf('%.2f%%', $studentGrade->getWeightedPercent()));


        $filter = array(
            'userId' => $this->user->getId(),
            'collectionId' => $this->collection->getId(),
            'subjectId' => $this->getSubject()->getId(),
            'status' => \Skill\Db\Entry::STATUS_APPROVED,
            'placementStatus' => \App\Db\Placement::STATUS_COMPLETED
        );
        $entryList = \Skill\Db\EntryMap::create()->findFiltered($filter, \Tk\Db\Tool::create('created DESC'));
        $template->insertText('entryCount', $entryList->count());

        $domainList = \Skill\Db\DomainMap::create()->findFiltered(array('collectionId' => $this->collection->getId(), 'active' => true), \Tk\Db\Tool::create());
        foreach ($domainList as $domain) {
            $domainAvg = (object)$studentGrade->getDomainAvg($domain->getId());
            $row = $template->getRepeat('domain-row');
            $row->insertText('name', $domain->name . ' (' . $domain->label . ')');
            $row->insertText('weight', round($domain->weight * 100) . '%');
            if ($domainAvg && property_exists($domainAvg, 'avg')) {
                $avgPct = ($domainAvg->avg/$domainAvg->scaleCount)*100;
                $row->insertText('avg', sprintf('%.2f%%', round($avgPct, 2)));
            } else {
                $row->insertText('avg', sprintf('%.2f', 0));
            }
            $row->appendRepeat();
        }

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

            $itemAvg = array();
            foreach ($itemList as $item) {
                $row = $catRow->getRepeat('item-row');
                $row->insertText('lineNo', ($i+1).'. ');
                $row->insertText('question', $item->question);

                $avg = $studentGrade->getItemAvg($item);
                $row->insertText('result', sprintf('%.2f', round($avg, 2)));
                if ($avg <= 0) {
                    $row->addCss('result', 'zero');
                } else {
                    $itemAvg[] = $avg;
                }
                $row->appendRepeat();
                $i++;
            }

            $catRow->appendRepeat();
        }

        if ($this->getConfig()->isDebug()) {
            $template->setVisible('debug');
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
      margin: 5px 0;
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
    .EntryResults .category-avg {
      margin-right: 115px;
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
          <tr>
            <td class="kv-key"><i class="fa fa-calculator kv-icon kv-icon-danger"></i> Calculated Grade (Weighted)</td>
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
          
          <tr choice="debug1">
            <td class="kv-key"><i class="fa fa-exchange kv-icon kv-icon-tertiary"></i> Average Response</td>
            <td class="kv-value" var="avg">0</td>
          </tr>
          <tr choice="debug1">
            <td class="kv-key"><i class="fa fa-graduation-cap kv-icon kv-icon-primary"></i> Calculated Grade</td>
            <td class="kv-value" var="grade">0.0</td>
          </tr>
          </tbody>
        </table>
      </div>
      <div class="col-lg-6">
        <table class="table table-bordered">
          <tr>
            <th>Domain</th>
            <th>Weight</th>
            <th title="Standard Avg. (unweighted)">Avg %</th>
          </tr>
          <tr repeat="domain-row" var="domain-row">
            <td var="name"></td>
            <td var="weight"></td>
            <td var="avg"></td>
          </tr>
        </table>
      </div>
      

      <div class="col-xs-12 category-row clearfix" repeat="category-row">
        <div class="col-xs-12">
          <div><span class="badge badge-primary category-avg pull-right" var="category-avg" choice="category-avg">0.00</span><h4 class="category-name" var="name">Category Name</h4></div>
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