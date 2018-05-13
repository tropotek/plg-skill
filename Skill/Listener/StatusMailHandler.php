<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class StatusMailHandler implements Subscriber
{

    /**
     * @param \App\Event\StatusEvent $event
     * @throws \Tk\Db\Exception
     */
    public function onSendAllStatusMessages(\App\Event\StatusEvent $event)
    {
        if (!$event->getStatus()->notify || !$event->getStatus()->getProfile()->notifications) return;   // do not send messages

        /** @var \Tk\Mail\CurlyMessage $message */
        foreach ($event->getMessageList() as $message) {

            $skillLinkHtml = '';
            $skillLinkText = '';
            $config = \App\Config::getInstance();

            if ($message->get('placement::id')) {
                /** @var \App\Db\Placement $placement */
                $placement = \App\Db\PlacementMap::create()->find($message->get('placement::id'));
                if (!$placement) {
                    $filter = array(
                        'active' => true,
                        'profileId' => $message->get('placement::profileId'),
                        'role' => \Skill\Db\Collection::ROLE_COMPANY,
                        'available' => $event->getStatus()->name,
                        'requirePlacement' => true,
                        'placementTypeId' => $placement->placementTypeId
                    );

                    $collections = \Skill\Db\CollectionMap::create()->findFiltered($filter);
                    /** @var \Skill\Db\Collection $collection */
                    foreach ($collections as $collection) {
                        $url = \App\Uri::createInstitutionUrl('/skillEdit.html', $placement->getSubject()->getInstitution())
                            ->set('h', $placement->getHash())
                            ->set('collectionId', $collection->getId());

//                        $url = \App\Uri::createInstitutionUrl('/skillEdit.html', $collection->getProfile()->getInstitution())
//                            ->set('collectionId', $collection->getId())
//                            ->set('userId', $message->get('student::id'))
//                            ->set('subjectId', $message->get('subject::id'));
//                        if ($message->get('placement::id'))
//                            $url->set('placementId', $message->get('placement::id'));

                        $skillLinkHtml .= sprintf('<a href="%s" title="%s">%s</a> | ', htmlentities($url->toString()),
                            htmlentities($collection->name), htmlentities($collection->name));
                        $skillLinkText .= sprintf('%s: %s | ', htmlentities($collection->name), htmlentities($url->toString()));
                    }
                }

                $message->set('skill::linkHtml', rtrim($skillLinkHtml, ' | '));
                $message->set('skill::linkText', rtrim($skillLinkText, ' | '));
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