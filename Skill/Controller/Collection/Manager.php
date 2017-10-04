<?php
namespace Skill\Controller\Collection;

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
     * @var \App\Db\Profile
     */
    private $profile = null;

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
        $this->setPageTitle('Skill Collection Manager');
    }

    /**
     * @param Request $request
     * @throws \Tk\Exception
     */
    public function doDefault(Request $request)
    {
        $this->profile = \App\Db\ProfileMap::create()->find($request->get('zoneId'));
        if ($request->get('profileId')) {
            $this->profile = \App\Db\ProfileMap::create()->find($request->get('profileId'));
        }
        $this->editUrl = \App\Uri::create('/skill/collectionEdit.html');


        $this->table = \App\Factory::createTable(\Tk\Object::basename($this).'_fieldList');
        $this->table->setParam('renderer', \App\Factory::createTableRenderer($this->table));

        $this->table->addCell(new \Tk\Table\Cell\Checkbox('id'));
        $this->table->addCell(new \Tk\Table\Cell\Text('name'))->addCss('key')->setUrl(clone $this->editUrl);
        $this->table->addCell(new \Tk\Table\Cell\Text('role'));
        $this->table->addCell(new \Tk\Table\Cell\ArrayObject('enabled'));
        $this->table->addCell(new \Tk\Table\Cell\Boolean('viewGrade'));
        $this->table->addCell(new \Tk\Table\Cell\Date('modified'));

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
        $filter['profileId'] = $this->profile->getId();
        return \Skill\Db\CollectionMap::create()->findFiltered($filter, $this->table->makeDbTool());
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $u = clone $this->editUrl;
        $this->getActionPanel()->addButton(\Tk\Ui\Button::create('New Collection',
            $u->set('profileId', $this->profile->getId()), 'fa fa-handshake-o'));

        $template->replaceTemplate('table', $this->table->getParam('renderer')->show());

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
      <h4 class="panel-title"><i class="fa fa-handshake-o"></i> Collections</h4>
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

