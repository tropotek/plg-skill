<?php
namespace Skill\Controller\Entry;

use App\Controller\AdminEditIface;
use App\Controller\AdminIface;
use Dom\Template;
use Tk\Form\Event;
use Tk\Form\Field;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Results extends AdminIface
{

    /**
     * @var \App\Db\User
     */
    protected $user = null;

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
        $this->setPageTitle('Results');
    }

    /**
     *
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
        $this->user = \App\Db\UserMap::create()->find($request->get('userId'));
        $this->collection = \Skill\Db\CollectionMap::create()->find($request->get('collectionId'));


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
        $panelTitle = sprintf('%s Results for `%s`', $this->collection->name, $this->user->name);
        $template->insertText('panel-title', $panelTitle);


        $entryList = \Skill\Db\EntryMap::create()->findFiltered(array('userId' => $this->user->getId(), 'collectionId' => $this->collection->getId(), 'courseId' => $this->getCourse()->getId()), \Tk\Db\Tool::create('created DESC'));
        $template->insertText('entryCount', count($entryList));



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
<div class="EntryResults">
  
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title"><i class="fa fa-eye" var="icon"></i> <span var="panel-title">Skill Entry Results</span></h4>
    </div>
    <div class="panel-body">
    
      <ul class="data">
        <li>Placements Assessed: <span var="entryCount">0</span></li>
        <li>Total Result: <span var="total">0</span></li>
        
        <li>Course Min: <span var="min">0</span></li>
        <li>Course Max: <span var="max">0</span></li>
        <li>Course Median: <span var="med">0</span></li>
      </ul>
      
      
    </div>
  </div>
  
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}