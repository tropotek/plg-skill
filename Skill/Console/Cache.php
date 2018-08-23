<?php
namespace Skill\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 * @todo: maybe
 */
class Cache extends \Tk\Console\Console
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('skill-cache')
            ->setDescription('Re-calculate the skill grades and averages and cache the results onto the filesystem');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        // required vars
        $config = \App\Config::getInstance();

        $subjects = \App\Db\SubjectMap::create()->findFiltered(array('active' => true), \Tk\Db\Tool::create('id DESC'));
        if ($subjects->count()) {
            $this->write('');
            $this->write(' - Updating gradable skill results cache.');
        }
        foreach ($subjects as $subject) {
            $collections = \Skill\Db\CollectionMap::create()->findFiltered(array(
                    'gradable' => true,
                    'requirePlacement' => true,
                    'subjectId' => $subject->getId())
            );
            $this->writeComment('', Output::VERBOSITY_VERY_VERBOSE);
            foreach ($collections as $collection) {
                $students = \App\Db\UserMap::create()->findFiltered(array('subjectId' => $subject->getId(), 'role' => \App\Db\UserGroup::ROLE_STUDENT));
                $this->writeComment($subject->name . ' - ' . $collection->name, Output::VERBOSITY_VERY_VERBOSE);
                $this->writeComment('  - Collection ID: ' . $collection->getId(), Output::VERBOSITY_VERY_VERBOSE);
                $this->writeComment('  - Subject ID:    ' . $subject->getId(), Output::VERBOSITY_VERY_VERBOSE);
                $this->writeComment('  - Students:      ' . $students->count(), Output::VERBOSITY_VERY_VERBOSE);

                $res = \Skill\Util\Calculator::findSubjectAverageGrades($collection, $subject, true);  // re-cache results
                if (!$res || !$res->count){
                    $this->writeComment('  - Entry Count:   0', Output::VERBOSITY_VERY_VERBOSE);
                    continue;
                }

                $this->writeComment('  - Entry Count:   ' . $res->subjectEntryCount, Output::VERBOSITY_VERY_VERBOSE);
                $this->writeComment('  - Min:           ' . $res->min, Output::VERBOSITY_VERY_VERBOSE);
                $this->writeComment('  - Median:        ' . $res->median, Output::VERBOSITY_VERY_VERBOSE);
                $this->writeComment('  - Max:           ' . $res->max, Output::VERBOSITY_VERY_VERBOSE);
                $this->writeComment('  - Avg:           ' . $res->avg, Output::VERBOSITY_VERY_VERBOSE);
            }
        }


        $this->writeComment('TODO: Not implemented Yet');

    }



}
