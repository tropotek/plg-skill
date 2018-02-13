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
        if ($this->controller instanceof \App\Controller\Staff\CourseDashboard) {
            $course = $this->controller->getCourse();
            $userList = $this->controller->getCourseUserList();
            $userList->setOnShowUser(function (\Dom\Template $template, \App\Db\User $user) use ($course) {
                $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array('profileId' => $course->profileId,
                    'gradable' => true, 'view_grade' => true));
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
    }

    /**
     * Check the user has access to this controller
     *
     * @param Event $event
     */
    public function onSidebarShow(Event $event)
    {
        if ($this->controller->getUser()->isStudent()) {
            /** @var \App\Ui\Sidebar\Iface $sidebar */
            $sidebar = $event->get('sidebar');
            $course = $this->controller->getCourse();
            $user = $this->controller->getUser();
            if (!$user || !$user->isStudent()) return;

            $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(
                array('profileId' => $course->profileId, 'gradable' => true, 'viewGrade' => true)
            );

            /** @var \Skill\Db\Collection $collection */
            foreach ($collectionList as $collection) {
                if (!$collection->isResultsEnabled($course)) continue;
                $html = sprintf('<li><a href="%s" title="View %s Results">%s</a></li>', htmlentities(\App\Uri::createCourseUrl('/skillEntryResults.html')->
                    set('userId', $user->getId())->set('collectionId', $collection->getId())->toString()), $collection->name, $collection->name);
                $sidebar->getTemplate()->appendHtml('menu', $html);
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
            \Tk\PageEvents::CONTROLLER_INIT => array('onControllerInit', 0),
            \App\UiEvents::SIDEBAR_SHOW => array('onSidebarShow', 0)
        );
    }
    
}