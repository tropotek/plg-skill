<?php
namespace Skill\Controller\Category;

use Dom\Template;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Edit extends \App\Controller\AdminEditIface
{

    /**
     * @var \Skill\Db\Category
     */
    protected $category = null;



    /**
     * Iface constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Skill Category Edit');
    }

    /**
     *
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->category = new \Skill\Db\Category();
        $this->category->collectionId = (int)$request->get('collectionId');
        if ($request->get('categoryId')) {
            $this->category = \Skill\Db\CategoryMap::create()->find($request->get('categoryId'));
        }

        $this->setForm(\Skill\Form\Category::create()->setModel($this->category));
        $this->initForm($request);
        $this->getForm()->execute();
    }


    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        $template->appendTemplate('panel', $this->getForm()->show());

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
<div class="tk-panel" data-panel-title="Skill Category Edit" data-panel-icon="fa fa-folder-o" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}