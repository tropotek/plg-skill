<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;


/**
 * Class StartupHandler
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class StatusMailHandler implements Subscriber
{

    public function onSendAllStatusMessages(\App\Event\StatusEvent $event)
    {
        if (!$event->getStatus()->notify || !$event->getStatus()->getProfile()->notifications) return;   // do not send messages

        /** @var \Tk\Mail\CurlyMessage $message */
        foreach ($event->getMessageList() as $message) {
            $message->set('skill::editLink', '');
            $message->set('placement::goalsUrl', '');   // Deprecated
            if ($message->get('placement::id')) {
                $filter = array(
                    'active' => true,
                    'profileId' => $message->get('placement::profileId'),
                    'role' => \Skill\Db\Collection::ROLE_COMPANY,
                    'available' => $event->getStatus()->name,
                    'placementTypeId' => $message->get('placement::placementTypeId')
                );
                $collections = \Skill\Db\CollectionMap::create()->findFiltered($filter);

                $skillLinkHtml = 'Submit Student Performance Review: ';
                /** @var \Skill\Db\Collection $collection */
                foreach ($collections as $collection) {
                    $url = \App\Uri::createInstitutionUrl(
                        '/'.$collection->getId() . '/' . $message->get('placement::hash').'/skillEdit.html');

                    $skillLinkHtml .= sprintf('<a href="%s" title="%s">%s</a> | ', htmlentities($url->toString()),
                        htmlentities($collection->name), htmlentities($collection->name));
                }
                $message->set('skill::editLink', rtrim($skillLinkHtml, ' | '));
                $message->set('placement::goalsUrl', rtrim($skillLinkHtml, ' | '));
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
            \App\StatusEvents::STATUS_SEND_MESSAGES => array('onSendAllStatusMessages', 10)
        );
    }
    
}