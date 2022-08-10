<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PlacementViewHandler implements Subscriber
{

    /**
     * @var \App\Controller\Placement\ReportEdit
     */
    private $controller = null;


    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onControllerInit(\Tk\Event\Event $event)
    {
        /** @var \App\Controller\Student\Placement\View $controller */
        $controller = $event->get('controller');
        if ($controller instanceof \App\Controller\Student\Placement\View) {
            $this->controller = $controller;
            $template = $controller->getTemplate();
            $placement = $controller->getPlacement();

            $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(
                array('subjectId' => $placement->subjectId, 'requirePlacement' => true, 'publish' => true, 'active' => true)
            );
            foreach ($collectionList as $collection) {
                if (!$placement->getPlacementType() || !$placement->getPlacementType()->enableReport) continue;
                $entry = \Skill\Db\EntryMap::create()->findFiltered(array(
                    'collectionId' => $collection->getId(),
                    'placementId' => $placement->getId(),
                    'status' => \Skill\Db\Entry::STATUS_APPROVED
                ))->current();

                if ($entry) {
                    $url = \Uni\Uri::createSubjectUrl('/entryView.html')->set('entryId', $entry->getId());
                    $btn = \Tk\Ui\Link::createBtn($collection->name, $url, $collection->icon);
                    $btn->setAttr('title', 'View ' . $collection->name)
                    ->addCss('btn-sm');
                    $template->appendTemplate('placement-actions', $btn->show());
                }
            }
        }
    }

    /**
     * Check the user has access to this controller
     *
     * @param \Tk\Event\Event $event
     */
    public function onControllerShow(\Tk\Event\Event $event) {}

    /**
     * @return array The event names to listen to
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            \Tk\PageEvents::CONTROLLER_INIT => array('onControllerInit', 0),
            \Tk\PageEvents::CONTROLLER_SHOW => array('onControllerShow', 0)
        );
    }

}