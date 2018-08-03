<?php
namespace Skill\Controller\Item;

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
     * Manager constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Skill Item Manager');
    }

    /**
     * @param Request $request
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     * @throws \Tk\Form\Exception
     */
    public function doDefault(Request $request)
    {
        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));

        $this->editUrl = \Uni\Uri::createHomeUrl('/skill/itemEdit.html');

        $u = clone $this->editUrl;
        $this->getActionPanel()->add(\Tk\Ui\Button::create('New Item',
            $u->set('collectionId', $this->collection->getId()), 'fa fa-question'));

        $this->table = \App\Config::getInstance()->createTable(\App\Config::getInstance()->getUrlName());
        $this->table->setRenderer(\App\Config::getInstance()->createTableRenderer($this->table));

        $this->table->addCell(new \Tk\Table\Cell\Checkbox('id'));
        //$this->table->addCell(new \Tk\Table\Cell\Text('uid'))->setLabel('UID');
        $this->table->addCell(new \Tk\Table\Cell\Text('question'))->addCss('key')->setUrl(clone $this->editUrl);
        $this->table->addCell(new \Tk\Table\Cell\Text('categoryId'))->setOnPropertyValue(function ($cell, $obj, $value) {
            /** @var \Skill\Db\Item $obj */
            if ($obj->getCategory()) return $obj->getCategory()->name;
            return $value;
        });
        $domains = \Skill\Db\DomainMap::create()->findFiltered(array('collectionId' => $this->collection->getId()));
        if (count($domains)) {
            $this->table->addCell(new \Tk\Table\Cell\Text('domainId'))->setOnPropertyValue(function ($cell, $obj, $value) {
                /** @var \Skill\Db\Item $obj */
                if ($obj->getDomain()) return $obj->getDomain()->name;
                return 'None';
            });
        }
        $this->table->addCell(new \Tk\Table\Cell\Date('modified'));
        $this->table->addCell(new \Tk\Table\Cell\OrderBy('orderBy'));

        // Filters
        $this->table->addFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Keywords');

        // Actions
        $this->table->addAction(\Tk\Table\Action\ColumnSelect::create()->setDisabled(array('id', 'name')));
        $this->table->addAction(\Tk\Table\Action\Csv::create());
        $this->table->addAction(\Tk\Table\Action\Delete::create());

        $this->table->setList($this->getList());

    }

    /**
     * @return \Skill\Db\Item[]|\Tk\Db\Map\ArrayObject
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    protected function getList()
    {
        $filter = $this->table->getFilterValues();
        $filter['collectionId'] = $this->collection->getId();
        return \Skill\Db\ItemMap::create()->findFiltered($filter, $this->table->getTool('orderBy', 100));
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
      <h4 class="panel-title"><i class="fa fa-question"></i> Item Manager</h4>
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

