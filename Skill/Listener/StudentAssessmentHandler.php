<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class StudentAssessmentHandler implements Subscriber
{

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function addCheckColumns(\Tk\Event\Event $event)
    {
        /** @var \App\Ui\StudentAssessment $studentAssessment */
        $studentAssessment = $event->get('studentAssessment');
        $subject = $studentAssessment->getSubject();

        /** @var \App\Db\Placement $placement */
        foreach($studentAssessment->getPlacementList() as $placement) {
            $report = $placement->getReport();
            $list = \Skill\Db\CollectionMap::create()->findFiltered(array(
                'subjectId' => $subject->getId(),
                'requirePlacement' => true,
                'active' => true,
                'placementTypeId' => $placement->getPlacementTypeId()
            ), \Tk\Db\Tool::create('FIELD(`name`, \'Self Assessment\') DESC'));
            foreach($list as $collection) {
                /** @var \Skill\Db\Entry $entry */
                $entry = \Skill\Db\EntryMap::create()->findFiltered(array(
                    'assessmentId' => $collection->getId(),
                    'placementId' => $placement->getId(),
                    'status' => array(\Skill\Db\Entry::STATUS_APPROVED, \Skill\Db\Entry::STATUS_PENDING)
                ))->current();
                $css = '';
                if ($entry) {
                    switch ($entry->getStatus()) {
                        case \Skill\Db\Entry::STATUS_PENDING:
                            $css = 'text-default';
                    }
                    $studentAssessment->addCheckColumn($collection->name, $placement->getId(), ($entry != null), $css, $entry->getStatus());
                };
            }
        }

    }

    /**
     * getSubscribedEvents
     * 
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            \App\UiEvents::STUDENT_ASSESSMENT_INIT => array(array('addCheckColumns', 0))
        );
    }

}


