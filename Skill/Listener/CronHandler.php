<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;
use Tk\Event\Event;
use Symfony\Component\Console\Output\Output;

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

        $subjects = \App\Db\SubjectMap::create()->findFiltered(array('active' => true), \Tk\Db\Tool::create('id DESC'));
        if ($subjects->count()) {
            $cronConsole->write('');
            $cronConsole->write(' - Updating gradable skill results cache.');
        }
        foreach ($subjects as $subject) {
            $collections = \Skill\Db\CollectionMap::create()->findFiltered(array(
                'gradable' => true,
                'requirePlacement' => true,
                'subjectId' => $subject->getId())
            );
            foreach ($collections as $collection) {
                $students = \App\Db\UserMap::create()->findFiltered(array('subjectId' => $subject->getId(), 'role' => \App\Db\UserGroup::ROLE_STUDENT));

                $cronConsole->writeComment('', Output::VERBOSITY_VERY_VERBOSE);
                $cronConsole->writeComment($subject->name . ' - ' . $collection->name, Output::VERBOSITY_VERY_VERBOSE);
                $cronConsole->writeComment('  - Collection ID: ' . $collection->getId(), Output::VERBOSITY_VERY_VERBOSE);
                $cronConsole->writeComment('  - Subject ID:    ' . $subject->getId(), Output::VERBOSITY_VERY_VERBOSE);
                $cronConsole->writeComment('  - Students:      ' . $students->count(), Output::VERBOSITY_VERY_VERBOSE);

                $res = \Skill\Util\Calculator::findSubjectAverageGrades($collection, $subject, true);  // re-cache results
                if (!$res || !$res->count){
                    $cronConsole->writeComment('  - Entry Count:   0', Output::VERBOSITY_VERY_VERBOSE);
                    continue;
                }

                $cronConsole->writeComment('  - Entry Count:   ' . $res->subjectEntryCount, Output::VERBOSITY_VERY_VERBOSE);
                $cronConsole->writeComment('  - Min:           ' . $res->min, Output::VERBOSITY_VERY_VERBOSE);
                $cronConsole->writeComment('  - Median:        ' . $res->median, Output::VERBOSITY_VERY_VERBOSE);
                $cronConsole->writeComment('  - Max:           ' . $res->max, Output::VERBOSITY_VERY_VERBOSE);
                $cronConsole->writeComment('  - Avg:           ' . $res->avg, Output::VERBOSITY_VERY_VERBOSE);
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