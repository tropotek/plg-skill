<?php
namespace Skill\Controller\Entry;

use App\Controller\AdminEditIface;
use Dom\Template;
use Tk\Form\Event;
use Tk\Form\Field;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class View extends AdminEditIface
{

    /**
     * @var \Skill\Db\Collection
     */
    protected $collection = null;

    /**
     * @var \Skill\Db\Entry
     */
    protected $entry = null;

    /**
     * @var \App\Ui\Table\Status
     */
    protected $statusTable = null;



    /**
     * Iface constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Skill Entry View');
    }

    /**
     *
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
        $this->entry = \Skill\Db\EntryMap::create()->find($request->get('entryId'));
        $this->collection = $this->entry->getCollection();

        $this->buildForm();

        $this->form->load(\Skill\Db\EntryMap::create()->unmapForm($this->entry));
        $this->form->execute($request);

    }

    /**
     *
     */
    protected function buildForm() 
    {
        $this->form = \App\Factory::createForm('entryEdit');
        $this->form->setParam('renderer', \App\Factory::createFormRenderer($this->form));

        $this->form->addField(new Field\Html('title'))->setFieldset('Entry Details');
        if($this->getUser()->isStudent() && $this->collection->viewGrade) {
            $this->form->addField(new Field\Html('averageScore', $this->entry->getAverage()))->setFieldset('Entry Details');
        }

        $this->form->addField(new Field\Html('status'))->setFieldset('Entry Details');
        $this->form->addField(new Field\Html('assessor'))->setFieldset('Entry Details');
        $this->form->addField(new Field\Html('absent'))->setFieldset('Entry Details');
        $this->form->addField(new Field\Html('notes'))->setFieldset('Entry Details');

        $items = \Skill\Db\ItemMap::create()->findFiltered(array('collectionId' => $this->collection->getId()),
            \Tk\Db\Tool::create('category_id, order_by'));
        /** @var \Skill\Db\Item $item */
        foreach ($items as $item) {
            $this->form->addField(new \Skill\Form\Field\Item($item))->setLabel(null)->setValue(\Skill\Db\EntryMap::create()->
                findValue($this->entry->getId(), $item->getId())->value)->setDisabled();
        }

//        $radioBtn = new \Tk\Form\Field\RadioButton('confirm', $this->collection->confirm);
//        $radioBtn->appendOption('Yes', '1', 'fa fa-check')->appendOption('No', '0', 'fa fa-ban');
//        $this->form->addField($radioBtn)->setLabel(null)->setFieldset('Confirmation')->setValue(true);

    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        $template->insertTemplate('form', $this->form->getParam('renderer')->show()->getTemplate());

        $template->appendCssUrl(\Tk\Uri::create('/plugin/ems-skill/assets/skill.less'));
        $template->appendJsUrl(\Tk\Uri::create('/plugin/ems-skill/assets/skill.js'));

        //$template->insertHtml('instructions', $this->entry->getCollection()->instructions);

        $css = <<<CSS
.form-group.tk-item:nth-child(odd) .skill-item {
  background-color: {$this->collection->color};
}
CSS;
        $template->appendCss($css);

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
<div class="EntryEdit">
  
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><i class="fa fa-eye"></i> <span var="panel-title">Skill Entry View</span></h4>
    </div>
    <div class="panel-body">
      <div var="instructions"></div>
      <div var="form"></div>
    </div>
  </div>
  
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}