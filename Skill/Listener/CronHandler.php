<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;
use Tk\Event\Event;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CronHandler implements Subscriber
{


    /**
     * @param Event $event
     * @throws \Exception
     */
    public function onCron(Event $event)
    {
        $config = \App\Config::getInstance();
        /** @var \App\Console\Cron $cronConsole */
        $cronConsole = $event->get('console');

        $subjects = \App\Db\SubjectMap::create()->findFiltered(array(), \Tk\Db\Tool::create('id DESC'));
        foreach ($subjects as $subject) {
            $collections = \Skill\Db\CollectionMap::create()->findFiltered(array(
                'gradable' => true,
                'requirePlacement' => true,
                'subjectId' => $subject->getId())
            );
            foreach ($collections as $collection) {
                if ($config->isDebug()) {
                    $students = \App\Db\UserMap::create()->findFiltered(array('subjectId' => $subject->getId(), 'role' => \App\Db\UserGroup::ROLE_STUDENT));
                    $cronConsole->writeComment($subject->name . ' - ' . $collection->name);
                    $cronConsole->writeComment('  - Collection ID: ' . $collection->getId());
                    $cronConsole->writeComment('  - Subject ID:    ' . $subject->getId());
                    $cronConsole->writeComment('  - Students:      ' . $students->count());
                }

                $res = \Skill\Util\Calculator::findSubjectAverageGrades($collection, $subject, true);  // re-cache results
                if (!$res || !$res->count){
                    continue;
                }

                if ($config->isDebug()) {
                    $cronConsole->writeComment('  - Entry Count:   ' . $res->subjectEntryCount);
                    //$cronConsole->writeComment('  - Count:        ' . $res->count);
                    $cronConsole->writeComment('  - Min:           ' . $res->min);
                    $cronConsole->writeComment('  - Median:        ' . $res->median);
                    $cronConsole->writeComment('  - Max:           ' . $res->max);
                    $cronConsole->writeComment('  - Avg:           ' . $res->avg);
                }
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
            \App\AppEvents::CONSOLE_CRON => array('onCron', 0)
        );
    }
    
}