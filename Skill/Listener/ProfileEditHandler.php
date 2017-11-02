<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;
use Tk\Event\Event;

/**
 * Class StartupHandler
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class ProfileEditHandler implements Subscriber
{

    private $profileId = 0;


    public function __construct($profileId)
    {
        $this->profileId = (int)$profileId;
    }


    /**
     * Check the user has access to this controller
     *
     * @param Event $event
     */
    public function onControllerInit(Event $event)
    {
        $plugin = \Skill\Plugin::getInstance();
        $config = $plugin->getConfig();
        //$config->getLog()->info($plugin->getName() . ': onControllerAccess(\'profile\', '.$this->profileId.') ');

        /** @var \Tk\Controller\Iface $controller */
        $controller = $event->get('controller');
        if ($controller instanceof \App\Controller\Profile\Edit) {
            if ($controller->getUser()->isStaff()) {
                /** @var \Tk\Ui\Admin\ActionPanel $actionPanel */
                $actionPanel = $controller->getActionPanel();
                $actionPanel->addButton(\Tk\Ui\Button::create('Skill Collections',
                    \App\Uri::create('/skill/collectionManager.html')->set('profileId', $this->profileId), 'fa fa-graduation-cap'));
            }
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
            \Tk\PageEvents::CONTROLLER_SHOW => array('onControllerShow', 0)
        );
    }
    
}