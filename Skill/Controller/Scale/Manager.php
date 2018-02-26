<?php
namespace Skill\Controller\Scale;

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
     * Manager constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Skill Scale Manager');
    }

    /**
     * @param Request $request
     * @throws \Tk\Exception
     */
    public function doDefault(Request $request)
    {
        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));

        $this->editUrl = \App\Uri::create('/skill/scaleEdit.html');

        $u = clone $this->editUrl;
        $this->getActionPanel()->add(\Tk\Ui\Button::create('New Scale',
            $u->set('collectionId', $this->collection->getId()), 'fa fa-balance-scale'));

        $this->table = \App\Config::getInstance()->createTable(\Tk\Object::basename($this).'_scaleList');
        $this->table->setRenderer(\App\Config::getInstance()->createTableRenderer($this->table));

        $this->table->addCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->table->addCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl(clone $this->editUrl);
        $this->table->addCell(new \Tk\Table\Cell\Text('value'));
        //$this->table->addCell(new \Tk\Table\Cell\Date('modified'));
        $this->table->addCell(new \Tk\Table\Cell\OrderBy('orderBy'))->setOnUpdate(function ($orderBy) {
            /** @var \Tk\Table\Cell\OrderBy $orderBy */
            \Skill\Db\Scale::recalculateValues($this->collection->getId());
        });

        // Filters
        $this->table->addFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Keywords');

        // Actions
        $this->table->addAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name')));
        $this->table->addAction(\Tk\Table\Action\Csv::create());
        $this->table->addAction(\Tk\Table\Action\Delete::create());

        $this->table->setList($this->getList());

    }

    protected function getList()
    {
        $filter = $this->table->getFilterValues();
        $filter['collectionId'] = $this->collection->getId();
        return \Skill\Db\ScaleMap::create()->findFiltered($filter, $this->table->makeDbTool());
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
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
      <h4 class="panel-title"><i class="fa fa-balance-scale"></i> Scale Manager</h4>
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

