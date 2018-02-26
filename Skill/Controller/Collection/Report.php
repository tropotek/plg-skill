<?php
namespace Skill\Controller\Collection;

use App\Controller\AdminIface;
use Dom\Template;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Report extends AdminIface
{


    /**
     * @var \Skill\Db\Collection
     */
    protected $collection = null;


    /**
     * Iface constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Report');
    }

    /**
     * @param Request $request
     * @throws \Tk\Exception
     */
    public function doDefault(Request $request)
    {
        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));

        if (!$this->collection->gradable) {
            throw new \Tk\Exception('A report is not available for this collection.');
        }
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        if ($this->collection->icon) {
            $template->addCss('icon', $this->collection->icon);
        }
        $panelTitle = sprintf('%s Report', $this->collection->name);
        $template->insertText('panel-title', $panelTitle);



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
<div class="skill-report">

  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <i class="fa fa-eye" var="icon"></i>
        <span var="panel-title">Skill Report</span>
      </h4>
    </div>
    <div class="panel-body">
      
      
      
      
    </div>
  </div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}