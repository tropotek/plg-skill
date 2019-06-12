<?php
namespace Skill\Controller\Domain;

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
     * @var \Skill\Db\Domain
     */
    protected $domain = null;



    /**
     * Iface constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Skill Domain Edit');
    }

    /**
     *
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->domain = new \Skill\Db\Domain();
        $this->domain->collectionId = (int)$request->get('collectionId');
        if ($request->get('domainId')) {
            $this->domain = \Skill\Db\DomainMap::create()->find($request->get('domainId'));
        }

        $this->setForm(\Skill\Form\Domain::create()->setModel($this->domain));
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
<div class="tk-panel" data-panel-title="Skill Domain Edit" data-panel-icon="fa fa-black-tie" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}