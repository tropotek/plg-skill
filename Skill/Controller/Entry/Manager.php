<?php
namespace Skill\Controller\Entry;

use App\Controller\AdminManagerIface;
use Dom\Template;
use Tk\Form\Field;
use Tk\Request;



/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Manager extends AdminManagerIface
{

    /**
     * @var \Skill\Db\Collection
     */
    private $collection = null;

    /**
     * @var null|\Tk\Uri
     */
    private $editUrl = null;

    /**
     * @var \Tk\Table\Cell\Actions
     */
    protected $actionsCell = null;



    /**
     * Manager constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Skill Entry Manager');
    }

    /**
     * @return \Tk\Table\Cell\Actions
     */
    public function getActionsCell()
    {
        return $this->actionsCell;
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));

        if ($this->editUrl === null)
            $this->editUrl = \Uni\Uri::createSubjectUrl('/entryEdit.html');

        $this->actionsCell = new \Tk\Table\Cell\Actions();
        $this->actionsCell->addButton(\Tk\Table\Cell\ActionButton::create('View Entry',
            \Uni\Uri::createSubjectUrl('/entryView.html'), 'fa fa-eye'))->setAppendQuery();

        $this->table = \App\Config::getInstance()->createTable(\App\Config::getInstance()->getUrlName());
        $this->table->setRenderer(\App\Config::getInstance()->createTableRenderer($this->table));

        $this->table->appendCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->table->appendCell($this->actionsCell);
        $this->table->appendCell(new \Tk\Table\Cell\Text('title'))->addCss('key')->setUrl(clone $this->editUrl);
        $this->table->appendCell(new \Tk\Table\Cell\Text('average'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('status'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('userId'))->setOnPropertyValue(function ($cell, $obj) {
            /** @var \Tk\Table\Cell\Text $cell */
            /** @var \Skill\Db\Entry $obj */
            if ($obj->getUser()) {
                $value = $obj->getUser()->name;
                $url = \Uni\Uri::createSubjectUrl('/entryResults.html')->set('userId', $obj->userId)->set('collectionId', $obj->collectionId);
                $cell->setUrl($url, '');
                $cell->setAttr('title', 'View student results for this subject.');
            }
            return $value;
        });
        $this->table->appendCell(new \Tk\Table\Cell\Text('assessor'));
        $this->table->appendCell(new \Tk\Table\Cell\Text('absent'));
        $this->table->appendCell(new \Tk\Table\Cell\Boolean('confirm'));
        $this->table->appendCell(new \Tk\Table\Cell\Date('created'));

        // Filters
        $this->table->addFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Keywords');

        $list = \App\Db\CompanyMap::create()->findFiltered(array(
            'profileId' => $this->getProfileId(),
            'status' => \App\Db\Placement::STATUS_APPROVED
        ), \Tk\Db\Tool::create('name'));
        $this->table->addFilter(new Field\Select('companyId', $list))->prependOption('-- Company --', '');

        // Actions
        $this->table->addAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'title')));
        $this->table->addAction(\Tk\Table\Action\Csv::create());
        $this->table->addAction(\Tk\Table\Action\Delete::create());

        $this->table->setList($this->getList());

    }

    /**
     * @return \Skill\Db\Entry[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    protected function getList()
    {
        $filter = $this->table->getFilterValues();
        $filter['collectionId'] = $this->collection->getId();
        $filter['subjectId'] = $this->getSubject()->getId();
        return \Skill\Db\EntryMap::create()->findFiltered($filter, $this->table->getTool('created DESC'));
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $this->getActionPanel()->add(\Tk\Ui\Button::create('Edit', \Uni\Uri::createSubjectUrl('/collectionEdit.html')->set('collectionId', $this->collection->getId()), 'fa fa-edit'));
        if ($this->collection->gradable) {
            $this->getActionPanel()->add(\Tk\Ui\Button::create('Grade Report',
                \Uni\Uri::createSubjectUrl('/collectionReport.html')->set('collectionId', $this->collection->getId()), 'fa fa-pie-chart'));
        }
        $template = parent::show();

        $template->replaceTemplate('table', $this->table->getRenderer()->show());

        $template->insertText('title', $this->collection->name . ' entries for ' . $this->getSubject()->name);

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
<div>

  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><i class="fa fa-pencil"></i> <span var="title">Entry Manager</span></h4>
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

