<?php
namespace Skill\Controller\Entry;

use Dom\Template;
use Tk\Request;


/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 *
 *
 * 
 * @deprecated This is a temporary object to bridge from the old EMSII and ensure the links work.
 *
 *
 */
class Goals extends \App\Controller\Iface
{



    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        if (preg_match('/[0-9a-f]{32}/i', $request->get('h'))) {
            // EG: h=13644394c4d1473f1547513fc21d7934
            // http://ems.vet.unimelb.edu.au/goals.html?h=13644394c4d1473f1547513fc21d7934
            $placement = \App\Db\PlacementMap::create()->findByHash($request->get('h'));
            if (!$placement) {
                \Tk\Alert::addError('Invalid URL. Please contact your course coordinator.');
                $this->getUser()->getHomeUrl()->redirect();
            }

//            $url = \App\Uri::createInstitutionUrl('/skillEdit.html', $placement->getSubject()->getInstitution())
//                ->set('collectionId', '1')
//                ->set('userId', $placement->userId)
//                ->set('subjectId', $placement->subjectId);
//            //if ($message->get('placement::id'))
//                $url->set('placementId', $placement->getId());

            $url = \App\Uri::createInstitutionUrl('/skillEdit.html', $placement->getSubject()->getInstitution())
                ->set('h', $placement->getHash())
                ->set('collectionId', '1');

            $url->redirect();
        }
    }



    /**
     * @return Template
     */
    public function show()
    {
        $template = parent::show();


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
<div class="">

  
  
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}