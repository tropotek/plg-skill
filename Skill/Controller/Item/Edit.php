<?php
namespace Skill\Controller\Item;

use App\Controller\AdminEditIface;
use Dom\Template;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Edit extends AdminEditIface
{

    /**
     * @var \Skill\Db\Item
     */
    protected $item = null;


    /**
     * Iface constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Skill Item Edit');
    }

    /**
     *
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->item = new \Skill\Db\Item();
        $this->item->collectionId = (int)$request->get('collectionId');
        if ($request->get('itemId')) {
            $this->item = \Skill\Db\ItemMap::create()->find($request->get('itemId'));
        }

        $this->setForm(\Skill\Form\Item::create()->setModel($this->item));
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
<div class="tk-panel" data-panel-title="Skill Item Edit" data-panel-icon="fa fa-question" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}