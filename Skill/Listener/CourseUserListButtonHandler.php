<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;
use Tk\Event\Event;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CourseUserListButtonHandler implements Subscriber
{

    /**
     * @var \App\Db\Course
     */
    private $course = null;


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
        $controller = $event->get('controller');
        if ($controller instanceof \App\Controller\Staff\CourseDashboard) {

            $course = $controller->getCourse();
            $userList = $controller->getCourseUserList();
            $userList->setOnShowUser(function (\Dom\Template $template, \App\Db\User $user) use ($course) {
                $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array('profileId' => $course->profileId,
                    'gradable' => true, 'view_grade' => true));
                /** @var \Skill\Db\Collection $collection */
                foreach ($collectionList as $collection) {
                    $placementTypeIdList = \Skill\Db\CollectionMap::create()->findPlacementTypes($collection->getId());
                    $placementStatusList = $collection->available;
                    // if user has a placement of at least one of the types and statuses used by the collection
                    if (\App\Db\PlacementMap::create()->userHasTypes($user->getId(), $placementTypeIdList, $placementStatusList)) {
                        $btn = \Tk\Ui\Button::create($collection->name . ' Results', \App\Uri::createCourseUrl('/skillEntryResults.html')->
                            set('userId', $user->getId())->set('collectionId', $collection->getId()), $collection->icon);
                        $btn->addCss('btn-success btn-xs');
                        $btn->setAttr('title', 'View Student ' . $collection->name . ' Results');
                        $template->prependTemplate('utr-row2', $btn->show());
                    }
                }
            });
        }
    }


    /**
     * Check the user has access to this controller
     *
     * @param Event $event
     */
    public function onControllerShow(Event $event)
    {
        $plugin = \Skill\Plugin::getInstance();
        $config = $plugin->getConfig();
        //$config->getLog()->info($plugin->getName() . ': onControllerShow(\'profile\', '.$this->profileId.') ');
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
            \Tk\PageEvents::CONTROLLER_INIT => array('onControllerInit', 0),
            //\Tk\PageEvents::CONTROLLER_SHOW => array('onControllerShow', 0)
        );
    }
    
}