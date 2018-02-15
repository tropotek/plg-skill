<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;
use Tk\Event\Event;
use Skill\Plugin;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CourseDashboardHandler implements Subscriber
{

    /**
     * @var \App\Db\Course
     */
    private $course = null;

    /**
     * @var \App\Controller\Iface
     */
    protected $controller = null;



    /**
     * CourseDashboardHandler constructor.
     * @param \App\Db\Course $course
     */
    public function __construct($course)
    {
        $this->course = $course;
    }

    /**
     * Check the user has access to this controller
     *
     * @param Event $event
     */
    public function onControllerInit(Event $event)
    {
        /** @var \App\Controller\Staff\CourseDashboard $controller */
        $this->controller = $event->get('controller');
        $course = $this->controller->getCourse();

        // STAFF Course Dashboard
        if ($this->controller instanceof \App\Controller\Staff\CourseDashboard) {
            $userList = $this->controller->getCourseUserList();
            $userList->setOnShowUser(function (\Dom\Template $template, \App\Db\User $user) use ($course) {
                $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array('profileId' => $course->profileId, 'gradable' => true));
                /** @var \Skill\Db\Collection $collection */
                foreach ($collectionList as $collection) {
                    if (!$collection->active) continue;
                    // if user has a placement of at least one of the types and status
                    $entryList = \Skill\Db\EntryMap::create()->findFiltered(array(
                        'userId' => $user->getId(),
                        'collectionId' => $collection->getId(),
                        'status' => \Skill\Db\Entry::STATUS_APPROVED
                    ));
                    if ($entryList->count()) {
                        $btn = \Tk\Ui\Button::create($collection->name . ' Results', \App\Uri::createCourseUrl('/skillEntryResults.html')->
                            set('userId', $user->getId())->set('collectionId', $collection->getId()), $collection->icon);
                        $btn->addCss('btn-success btn-xs');
                        $btn->setAttr('title', 'View Student ' . $collection->name . ' Results');
                        $template->prependTemplate('utr-row2', $btn->show());
                    }
                }
            });

        }
        // STUDENT Course Dashboard
        if ($this->controller instanceof \App\Controller\Student\CourseDashboard) {
            $placementList = $this->controller->getPlacementList();
            $actionsCell = $placementList->getActionsCell();

            $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array('profileId' => $course->profileId, 'requirePlacement' => true));
            foreach ($collectionList as $collection) {
                $actionsCell->addButton(\Tk\Table\Cell\ActionButton::create($collection->name,
                    \App\Uri::createCourseUrl('/entryView.html'), $collection->icon))
                    ->setShowLabel()
                    ->setOnShow(function ($cell, $obj, $btn) use ($collection) {
                        /** @var \Tk\Table\Cell\Actions $cell */
                        /** @var \App\Db\Placement $obj */
                        /** @var \Tk\Table\Cell\ActionButton $btn */
//                        if (!$collection->isAvailable($obj)) {
//                            $btn->setVisible(false);
//                            return '';
//                        }
                        $entry = \Skill\Db\EntryMap::create()->findFiltered(array(
                            'collectionId' => $collection->getId(),
                            'placementId' => $obj->getId(),
                            'status' => \Skill\Db\Entry::STATUS_APPROVED
                        ))->current();
                        if (!$entry) {
                            $btn->setVisible(false);
                            return;
                        }
                        $btn->getUrl()->set('entryId', $entry->getId());
                    });
            }

        }
    }


    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            \Tk\PageEvents::CONTROLLER_INIT => array('onControllerInit', 0)
        );
    }
    
}