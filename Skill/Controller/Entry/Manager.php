<?php
namespace Skill\Controller\Entry;

use App\Controller\AdminManagerIface;
use Dom\Template;
use Tk\Form\Field;
use Tk\Request;



/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
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
     * @throws \Tk\Exception
     */
    public function doDefault(Request $request)
    {
        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));

        if ($this->editUrl === null)
            $this->editUrl = \App\Uri::create('/skill/entryEdit.html');


        $this->getActionPanel()->addButton(\Tk\Ui\Button::create('Setup',
            \App\Uri::create('/skill/collectionEdit.html')->set('collectionId', $this->collection->getId()), 'fa fa-gears'));
        $this->getActionPanel()->addButton(\Tk\Ui\Button::create('Grade Report',
            \App\Uri::create('/skill/collectionReport.html')->set('collectionId', $this->collection->getId()), 'fa fa-pie-chart'));


        $this->actionsCell = new \Tk\Table\Cell\Actions();
        $this->actionsCell->addButton(\Tk\Table\Cell\ActionButton::create('View Entry',
            \App\Uri::create('/skill/entryView.html'), 'fa fa-eye'))->setAppendQuery();

        $this->table = \App\Factory::createTable(\Tk\Object::basename($this).'_entryList'.$this->collection->name);
        $this->table->setParam('renderer', \App\Factory::createTableRenderer($this->table));

        $this->table->addCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->table->addCell($this->actionsCell);
        $this->table->addCell(new \Tk\Table\Cell\Text('title'))->addCss('key')->setUrl(clone $this->editUrl);
        $this->table->addCell(new \Tk\Table\Cell\Text('average'));
        $this->table->addCell(new \Tk\Table\Cell\Text('status'));
        $this->table->addCell(new \Tk\Table\Cell\Text('userId'))->setOnPropertyValue(function ($cell, $obj) {
            /** @var \Skill\Db\Entry $obj */
            if ($obj->getUser())
                return $obj->getUser()->name;
            return '';
        });
        $this->table->addCell(new \Tk\Table\Cell\Text('assessor'));
        $this->table->addCell(new \Tk\Table\Cell\Text('absent'));
        $this->table->addCell(new \Tk\Table\Cell\Boolean('confirm'));
        $this->table->addCell(new \Tk\Table\Cell\Date('created'));

        // Filters
        $this->table->addFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Keywords');

        // Actions
        $this->table->addAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'title')));
        $this->table->addAction(\Tk\Table\Action\Csv::create());
        $this->table->addAction(\Tk\Table\Action\Delete::create());

        $this->table->setList($this->getList());

    }

    protected function getList()
    {
        $filter = $this->table->getFilterValues();
        $filter['collectionId'] = $this->collection->getId();
        $filter['courseId'] = $this->getCourse()->getId();
        return \Skill\Db\EntryMap::create()->findFiltered($filter, $this->table->makeDbTool('created DESC'));
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $template->replaceTemplate('table', $this->table->getParam('renderer')->show());

        $template->insertText('title', $this->collection->name . ' entries for ' . $this->getCourse()->name);

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
