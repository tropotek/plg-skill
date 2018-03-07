<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;
use Skill\Plugin;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SetupHandler implements Subscriber
{

    /**
     * @param \Tk\Event\GetResponseEvent $event
     * @throws \Tk\Db\Exception
     */
    public function onRequest(\Tk\Event\GetResponseEvent $event)
    {
        $this->setup();
    }

    /**
     * @param \Symfony\Component\Console\Event\ConsoleCommandEvent $event
     * @throws \Tk\Db\Exception
     */
    public function onCommand(\Symfony\Component\Console\Event\ConsoleCommandEvent $event)
    {
        vd('----213321321--------');
        $this->setup();
    }

    /**
     * @throws \Tk\Db\Exception
     */
    public function setup()
    {
        /* NOTE:
         *  If you require the Institution object for an event
         *  be sure to subscribe events here.
         *  As any events fired before this event do not have access to
         *  the institution object, unless you manually save the id in the
         *  session on first page load?
         *
         */
        $config = \Tk\Config::getInstance();
        $dispatcher = \App\Config::getInstance()->getEventDispatcher();
        $plugin = Plugin::getInstance();

///        $institution = \Uni\Config::getInstance()->getInstitution()
//        if($institution && $plugin->isZonePluginEnabled(Plugin::ZONE_INSTITUTION, $institution->getId())) {
//            \Tk\Log::debug($plugin->getName() . ': Sample init client plugin stuff: ' . $institution->name);
//            $dispatcher->addSubscriber(new \Skill\Listener\ExampleHandler(Plugin::ZONE_INSTITUTION, $institution->getId()));
//        }

//        $course = \Uni\Config::getInstance()->getCourse();
//        if ($course && $plugin->isZonePluginEnabled(Plugin::ZONE_COURSE, $course->getId())) {
//            \Tk\Log::debug($plugin->getName() . ': Sample init course plugin stuff: ' . $course->name);
//            $dispatcher->addSubscriber(new \Skill\Listener\ExampleHandler(Plugin::ZONE_COURSE, $course->getId()));
//        }

        $profile = \App\Config::getInstance()->getProfile();
        if ($profile && $plugin->isZonePluginEnabled(Plugin::ZONE_COURSE_PROFILE, $profile->getId())) {
            //\Tk\Log::debug($plugin->getName() . ': Sample init course profile plugin stuff: ' . $profile->name);
            $dispatcher->addSubscriber(new \Skill\Listener\ProfileEditHandler($profile->getId()));
            $course = \Uni\Config::getInstance()->getCourse();
            if ($course) {
                $dispatcher->addSubscriber(new \Skill\Listener\CourseEditHandler($course));
                $dispatcher->addSubscriber(new \Skill\Listener\PlacementEditHandler($course));
                $dispatcher->addSubscriber(new \Skill\Listener\CourseDashboardHandler($course));
                $dispatcher->addSubscriber(new \Skill\Listener\PlacementManagerButtonHandler($course));
                $dispatcher->addSubscriber(new \Skill\Listener\SidebarHandler($course));
            }
        }

        $dispatcher->addSubscriber(new \Skill\Listener\StatusMailHandler());

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
            \Tk\Kernel\KernelEvents::REQUEST => array('onRequest', -10),
            \Symfony\Component\Console\ConsoleEvents::COMMAND  => array('onCommand', -10)
        );
    }
    
    
}