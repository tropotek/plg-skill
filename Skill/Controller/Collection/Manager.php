<?php
namespace Skill\Controller\Collection;

use App\Controller\AdminManagerIface;
use Dom\Template;
use Tk\Request;


/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Manager extends AdminManagerIface
{


    /**
     * Manager constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Skill Collection Manager');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {

        $this->setTable(\Skill\Table\Collection::create());
        $this->getTable()->setEditUrl(\Uni\Uri::createSubjectUrl('/collectionEdit.html'));
        $this->getTable()->init();

        $filter = array(
            'subjectId' => $this->getConfig()->getSubjectId()
        );
        $this->getTable()->setList($this->getTable()->findList($filter));

    }

    /**
     *
     */
    public function initActionPanel()
    {
        $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('New Collection',
            $this->getTable()->getEditUrl(), 'fa fa-graduation-cap fa-add-action'));
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $this->initActionPanel();
        $template = parent::show();

        $template->appendTemplate('panel', $this->getTable()->show());

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
<div class="tk-panel" data-panel-title="Skill Collections" data-panel-icon="fa fa-graduation-cap" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}

