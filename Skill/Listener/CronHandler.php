<?php
namespace Skill\Listener;

use Symfony\Component\Console\Output\Output;
use Tk\ConfigTrait;
use Tk\Event\Event;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 *
 * @todo See todo.md as this need to be refactored to be a more maintainable codebase
 *
 */
class CronHandler implements Subscriber
{

    use ConfigTrait;

    /**
     * @param Event $event
     * @throws \Exception
     * @deprecated
     */
    public function onCron(Event $event)
    {
        /** @var \App\Console\Cron $cronConsole */
        $cronConsole = $event->get('console');

        // TODO: Remove after Jan 2019
        //$this->fixCollectionIds($cronConsole);

        if (!$this->getConfig()->isDebug())
            $this->refreshGradeCache($cronConsole);

    }


    /**
     * @param \App\Console\Cron $console
     */
    protected function fixCollectionIds($console)
    {
        $console->write(' - Checking and repairing old Entry collection_id and item_id values. (Remove After: Jan 2019)');
        //\Skill\Db\CollectionMap::create()->fixChangeoverEntries();
    }

    /**
     * @param \App\Console\Cron $console
     */
    protected function refreshGradeCache($console)
    {
        $subjects = \App\Db\SubjectMap::create()->findFiltered(array(
            'active' => true
        ), \Tk\Db\Tool::create('id DESC'));

        if ($subjects->count()) {
            $console->write(' - Updating gradable skill results cache:');
        }
        foreach ($subjects as $subject) {
            $collections = \Skill\Db\CollectionMap::create()->findFiltered(array(
                    'gradable' => true,
                    'requirePlacement' => true,
                    'subjectId' => $subject->getId())
            );
            foreach ($collections as $collection) {
                $students = \App\Db\UserMap::create()->findFiltered(array('subjectId' => $subject->getId(), 'type' => \Skill\Db\Collection::TYPE_STUDENT));
                $console->writeComment($subject->name . ' - ' . $collection->name, Output::VERBOSITY_VERY_VERBOSE);
                $console->writeComment('  - Collection ID: ' . $collection->getId(), Output::VERBOSITY_VERY_VERBOSE);
                $console->writeComment('  - Subject ID:    ' . $subject->getId(), Output::VERBOSITY_VERY_VERBOSE);
                $console->writeComment('  - Students:      ' . $students->count(), Output::VERBOSITY_VERY_VERBOSE);

                $calc = new \Skill\Util\GradeCalculator($collection);
                $calc->flushCache();
                //$calc->setCacheEnabled(false);
                $res = $calc->getSubjectGrades();

                if (!$res || !$res->count){
                    $console->writeComment('  - Entry Count:   0', Output::VERBOSITY_VERY_VERBOSE);
                    $console->writeComment('', Output::VERBOSITY_VERY_VERBOSE);
                    continue;
                }
                $console->writeComment('  - Entry Count:   ' . $res->entryCount, Output::VERBOSITY_VERY_VERBOSE);
                $console->writeComment('  - Min:           ' . $res->min, Output::VERBOSITY_VERY_VERBOSE);
                $console->writeComment('  - Median:        ' . $res->median, Output::VERBOSITY_VERY_VERBOSE);
                $console->writeComment('  - Max:           ' . $res->max, Output::VERBOSITY_VERY_VERBOSE);
                $console->writeComment('  - Avg:           ' . $res->avg, Output::VERBOSITY_VERY_VERBOSE);
                $console->writeComment('', Output::VERBOSITY_VERY_VERBOSE);
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