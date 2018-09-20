<?php
namespace Skill\Controller\Entry;

use Tk\Request;


/**
 * @deprecated This is a temporary object to bridge from the old EMSII and ensure the links work.
 * @todo: we can delete this safely after 2018
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
            $url = \App\Uri::createInstitutionUrl('/skillEdit.html', $placement->getSubject()->getInstitution())
                ->set('h', $placement->getHash())
                ->set('collectionId', '1');
            $url->redirect();
        }
    }

}