<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class StaffSideMenuHandler implements Subscriber
{

    /**
     * Check the user has access to this controller
     *
     * @param \Tk\Event\Event $event
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function onControllerInit(\Tk\Event\Event $event)
    {
        /** @var \App\Controller\Iface $controller */
        $controller = $event->get('controller');
        if ($controller->getSubject() && $controller->getUser()->isStaff()) {
            /** @var \App\Page $page */
            $page = $controller->getPage();
            /** @var \App\Ui\Sidebar\StaffMenu $sideBar */
            $sideBar = $page->getSidebar();
            if ($sideBar instanceof \App\Ui\Sidebar\StaffMenu) {
                $sideBar->addReportUrl(\Tk\Ui\Link::create('Student Grades', \App\Uri::createSubjectUrl('/null.html'), 'fa fa-list-alt'));
            }
        }
    }


    /**
     * Check the user has access to this controller
     *
     * @param \Tk\Event\Event $event
     */
    public function onControllerShow(\Tk\Event\Event $event) { }


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