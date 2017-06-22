<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;
use sample\Plugin;

/**
 * Class StartupHandler
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SetupHandler implements Subscriber
{


    public function onRequest(\Tk\Event\GetResponseEvent $event)
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
        /** @var \Tk\Event\Dispatcher $dispatcher */
        $dispatcher = $config->getEventDispatcher();
        /** @var \App\Db\Institution $institution */
        $institution = $config->getInstitution();
        $plugin = \Skill\Plugin::getInstance();

//        if($institution && $plugin->isZonePluginEnabled(Plugin::ZONE_INSTITUTION, $institution->getId())) {
//            $config->getLog()->debug($plugin->getName() . ': Sample init client plugin stuff: ' . $institution->name);
//            $dispatcher->addSubscriber(new \Skill\Listener\ExampleHandler(Plugin::ZONE_INSTITUTION, $institution->getId()));
//        }
        /** @var \App\Db\Course $course */
        $course = \App\Factory::getCourse();
        if ($course && $plugin->isZonePluginEnabled(Plugin::ZONE_COURSE, $course->getId())) {
            $config->getLog()->debug($plugin->getName() . ': Sample init course plugin stuff: ' . $course->name);
            $dispatcher->addSubscriber(new \Skill\Listener\ExampleHandler(Plugin::ZONE_COURSE, $course->getId()));

//            $profile = $course->getProfile();
//            if ($profile && $plugin->isZonePluginEnabled(Plugin::ZONE_COURSE_PROFILE, $profile->getId())) {
//                $config->getLog()->debug($plugin->getName() . ': Sample init course profile plugin stuff: ' . $profile->name);
//                $dispatcher->addSubscriber(new \Skill\Listener\ExampleHandler(Plugin::ZONE_COURSE_PROFILE, $profile->getId()));
//            }
        }

    }



    public function onInit(\Tk\Event\KernelEvent $event)
    {
        //vd('onInit');
    }

    public function onController(\Tk\Event\ControllerEvent $event)
    {
        //vd('onController');
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
            //\Tk\Kernel\KernelEvents::INIT => array('onInit', 0),
            //\Tk\Kernel\KernelEvents::CONTROLLER => array('onController', 0),
            \Tk\Kernel\KernelEvents::REQUEST => array('onRequest', -10)
        );
    }
    
    
}