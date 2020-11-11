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
     * @param \Bs\Event\StatusEvent $event
     * @throws \Exception
     */
    public function onSendAllStatusMessages(\Bs\Event\StatusEvent $event)
    {
        $course = \Uni\Util\Status::getCourse($event->getStatus());
        if (!$event->getStatus()->isNotify() || ($course && !$course->getCourseProfile()->isNotifications())) {
            \Tk\Log::debug('Skill::onSendAllStatusMessages: Status Notification Disabled');
            return;
        }
        $subject = \Uni\Util\Status::getSubject($event->getStatus());

        /** @var \Tk\Mail\CurlyMessage $message */
        foreach ($event->getMessageList() as $message) {
            $skillLinkHtml = '';
            $skillLinkText = '';

            if ($message->get('placement::id')) {
                /** @var \App\Db\Placement $placement */
                $placement = \App\Db\PlacementMap::create()->find($message->get('placement::id'));
                if ($placement) {
                    $filter = array(
                        'active' => true,
                        'subjectId' => $message->get('placement::subjectId'),
                        'role' => \Skill\Db\Collection::TYPE_COMPANY,
                        'requirePlacement' => true,
                        'placementTypeId' => $placement->getPlacementTypeId()
                    );
                    $collections = \Skill\Db\CollectionMap::create()->findFiltered($filter);
                    /** @var \Skill\Db\Collection $collection */
                    foreach ($collections as $collection) {
                        $url = \Uni\Uri::createInstitutionUrl('/skillEdit.html', $placement->getSubject()->getInstitution())
                            ->set('h', $placement->getHash())
                            ->set('collectionId', $collection->getId());
                        $avail = '';
                        if (!$collection->isAvailable($placement)) {
                            $avail = ' [Currently Unavailable]';
                        }
                        $skillLinkHtml .= sprintf('<a href="%s" title="%s">%s</a> | ', htmlentities($url->toString()),
                            htmlentities($collection->getName()).$avail, htmlentities($collection->getName()).$avail);
                        $skillLinkText .= sprintf('%s: %s | ', htmlentities($collection->getName()).$avail, htmlentities($url->toString()));
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
            \Bs\StatusEvents::STATUS_SEND_MESSAGES => array('onSendAllStatusMessages', 10)
        );
    }
    
}