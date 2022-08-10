<?php
namespace Skill\Controller\Scale;

use App\Controller\AdminEditIface;
use Dom\Template;
use Tk\Request;


/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Edit extends AdminEditIface
{

    /**
     * @var \Skill\Db\Scale
     */
    protected $scale = null;


    /**
     * Iface constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Skill Scale Edit');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->scale = new \Skill\Db\Scale();
        $this->scale->collectionId = (int)$request->get('collectionId');
        if ($request->get('scaleId')) {
            $this->scale = \Skill\Db\ScaleMap::create()->find($request->get('scaleId'));
        }

        $this->setForm(\Skill\Form\Scale::create()->setModel($this->scale));
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
<div class="tk-panel" data-panel-title="Skill Scale Edit" data-panel-icon="fa fa-balance-scale" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}