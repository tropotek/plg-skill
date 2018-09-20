<?php
namespace Skill\Controller\Collection;

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
     * @var null|\Tk\Uri
     */
    protected $editUrl = null;


    /**
     * Manager constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Skill Collection Manager');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        if ($this->editUrl === null)
            $this->editUrl = \Uni\Uri::createHomeUrl('/skill/collectionEdit.html');

        $this->table = \Uni\Config::getInstance()->createTable(\App\Config::getInstance()->getUrlName());
        $this->table->setRenderer(\Uni\Config::getInstance()->createTableRenderer($this->table));

        $this->table->addCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->actionsCell = new \Tk\Table\Cell\Actions();
        $this->actionsCell->addButton(\Tk\Table\Cell\ActionButton::create('Edit Collection',
            \Uni\Uri::createSubjectUrl('/collectionEdit.html'), 'fa fa-edit'))->setAppendQuery();
        $this->actionsCell->addButton(\Tk\Table\Cell\ActionButton::create('View Entries',
            \Uni\Uri::createSubjectUrl('/entryManager.html'), 'fa fa-files-o'))->setAppendQuery();

        $this->table->addCell($this->actionsCell);

        $this->table->addCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl(clone $this->editUrl);
        $this->table->addCell(new \Tk\Table\Cell\Text('role'));
        $this->table->addCell(new \Tk\Table\Cell\ArrayObject('available'))->setLabel('Placement Enabled Status');
        $this->table->addCell(new \Tk\Table\Cell\Boolean('gradable'));
        $this->table->addCell(new \Tk\Table\Cell\Boolean('viewGrade'));
        $this->table->addCell(new \Tk\Table\Cell\Boolean('requirePlacement'));
        $this->table->addCell(new \Tk\Table\Cell\Boolean('publish'));
        $this->table->addCell(new \Tk\Table\Cell\Boolean('active'));
        $this->table->addCell(new \Tk\Table\Cell\Text('entries'))->setOnPropertyValue(function ($cell, $obj) {
            /** @var \Skill\Db\Collection $obj */
            $filter = array('collectionId' => $obj->getId());
            return \Skill\Db\EntryMap::create()->findFiltered($filter)->count();
        });
        $this->table->addCell(new \Tk\Table\Cell\Date('modified'));

        // Filters
        $this->table->addFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Keywords');

        // Actions
        $this->table->addAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name')));
        $this->table->addAction(\Tk\Table\Action\Csv::create());
        $this->table->addAction(\Tk\Table\Action\Delete::create());

        $this->table->setList($this->getList());

    }

    /**
     * @return \Skill\Db\Collection[]|\Tk\Db\Map\ArrayObject
     * @throws \Exception
     */
    protected function getList()
    {
        $filter = $this->table->getFilterValues();
        $filter['subjectId'] = $this->getSubjectId();
        return \Skill\Db\CollectionMap::create()->findFiltered($filter, $this->table->getTool());
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $u = clone $this->editUrl;
        $this->getActionPanel()->add(\Tk\Ui\Button::create('New Collection',
            $u, 'fa fa-graduation-cap fa-add-action'));

        $template = parent::show();

        $template->replaceTemplate('table', $this->table->getRenderer()->show());

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
      <h4 class="panel-title"><i class="fa fa-graduation-cap"></i> Skill Collections</h4>
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

