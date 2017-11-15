<?php
namespace Skill\Controller\Entry;

use Dom\Template;
use Tk\Request;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CollectionManager extends \Skill\Controller\Collection\Manager
{

    /**
     * @var \App\Db\Course
     */
    protected $course = null;


    /**
     * Manager constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Skill Entry Collections');
        $this->editUrl = \App\Uri::create('/skill/entryManager.html');
    }

    /**
     * @param Request $request
     * @return null|\Tk\Db\Map\Model|\Tk\Db\ModelInterface
     */
    protected function findProfile(Request $request)
    {
        $this->course = \App\Db\CourseMap::create()->find($request->get('courseId'));
        $this->editUrl->set('courseId', $this->course->getId());
        $profile = $this->course->getProfile();
        return $profile;
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $this->getActionPanel()->removeButton($this->getActionPanel()->findButtonByTitle('New Collection'));

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
      <h4 class="panel-title"><i class="fa fa-graduation-cap"></i> Skill Entry Collections</h4>
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
