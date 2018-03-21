<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;
use Tk\Event\Event;
use Skill\Plugin;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SidebarHandler implements Subscriber
{

    /**
     * @var \App\Db\Subject
     */
    private $subject = null;

    /**
     * @var \App\Controller\Iface
     */
    protected $controller = null;



    /**
     *  constructor.
     * @param \App\Db\Subject $subject
     */
    public function __construct($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Check the user has access to this controller
     *
     * @param Event $event
     */
    public function onControllerInit(Event $event)
    {
        /** @var \App\Controller\Staff\SubjectDashboard $controller */
        $this->controller = $event->get('controller');
    }

    /**
     * Check the user has access to this controller
     *
     * @param Event $event
     * @throws \Dom\Exception
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function onSidebarShow(Event $event)
    {
        /** @var \App\Ui\Sidebar\Iface $sidebar */
        $sidebar = $event->get('sidebar');
        $subject = $this->controller->getSubject();
        $user = $this->controller->getUser();
        if (!$user) return;

        if ($this->controller->getUser()->isStudent()) {

            $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(
                array('subjectId' => $subject->getId())
            );

            /** @var \Skill\Db\Collection $collection */
            foreach ($collectionList as $collection) {
                $html = '';
                if ($collection->requirePlacement) {        // Results views
                    if ($collection->gradable) {
                        $html = sprintf('<li><a href="%s" title="View %s Results">%s</a></li>',
                            htmlentities(\App\Uri::createSubjectUrl('/entryResults.html')->set('collectionId', $collection->getId())->toString()),
                            $collection->name, $collection->name);
                    }
                } else if ($collection->role == \Skill\Db\Collection::ROLE_STUDENT) {
                    /** @var \Skill\Db\Entry $e */
                    $e = \Skill\Db\EntryMap::create()->findFiltered(array(
                                    'collectionId' => $collection->getId(),
                                    'subjectId' => $subject->getId(),
                                    'userId' => $user->getId())
                    )->current();
                    if ($e && $e->status == \Skill\Db\Entry::STATUS_APPROVED) {
                        $html = sprintf('<li><a href="%s" title="View %s">%s</a></li>',
                            htmlentities(\App\Uri::createSubjectUrl('/entryView.html')->set('entryId', $e->getId())->toString()),
                            $collection->name, $collection->name);
                    } else {
                        $html = sprintf('<li><a href="%s" title="Create %s">%s</a></li>',
                            htmlentities(\App\Uri::createSubjectUrl('/entryEdit.html')->set('collectionId', $collection->getId())->toString()),
                            $collection->name, $collection->name);
                    }
                }
                if ($html)
                    $sidebar->getTemplate()->appendHtml('menu', $html);
            }
        } else if ($this->controller->getUser()->isStaff()) {
            /** @var \App\Ui\Sidebar\StaffMenu $sidebar */
            $list = \Skill\Db\CollectionMap::create()->findFiltered( array('subjectId' => $this->subject->getId(), 'gradable' => true) );
            foreach ($list as $collection) {
                $sidebar->addReportUrl(\Tk\Ui\Link::create($collection->name . ' Grades',
                    \App\Uri::createSubjectUrl('/gradeReport.html')->set('collectionId', $collection->getId()), $collection->icon));

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