<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;
use Tk\Event\Event;
use Skill\Plugin;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SubjectDashboardHandler implements Subscriber
{

    /**
     * @var \App\Db\Subject
     */
    private $subject = null;

    /**
     * @var \App\Controller\Iface
     */
    protected $controller = null;



    /**
     * constructor.
     * @param \App\Db\Subject $subject
     */
    public function __construct($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Check the user has access to this controller
     *
     * @param Event $event
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function onControllerInit(Event $event)
    {
        /** @var \App\Controller\Staff\SubjectDashboard $controller */
        $this->controller = $event->get('controller');
        $subject = $this->controller->getSubject();

        // STAFF Subject Dashboard
        if ($this->controller instanceof \App\Controller\Staff\SubjectDashboard) {
            $userList = $this->controller->getSubjectUserList();
            $userList->setOnShowUser(function (\Dom\Template $template, \App\Db\User $user) use ($subject) {
                //$collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array('profileId' => $subject->profileId, 'gradable' => true));
                $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array('profileId' => $subject->profileId, 'gradable' => true));
                /** @var \Skill\Db\Collection $collection */
                foreach ($collectionList as $collection) {
                    //if (!$collection->isAvailable() || !$collection->isAvailableToSubject($subject)) continue;
                    if (!$collection->isAvailable()) continue;

                    // if user has a placement of at least one of the types and status
                    $entryList = \Skill\Db\EntryMap::create()->findFiltered(array(
                        'userId' => $user->getId(),
                        'collectionId' => $collection->getId(),
                        'status' => \Skill\Db\Entry::STATUS_APPROVED
                    ));
                    if (!$entryList->count()) continue;

                    if ($entryList->count()) {
                        $btn = \Tk\Ui\Button::create($collection->name . ' Results', \App\Uri::createSubjectUrl('/entryResults.html')->
                            set('userId', $user->getId())->set('collectionId', $collection->getId()), $collection->icon);
                        $btn->addCss('btn-primary btn-xs');
                        $btn->setAttr('title', 'View Student ' . $collection->name . ' Results');
                        $template->prependTemplate('utr-row2', $btn->show());
                    }
                }

                $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array('profileId' => $subject->profileId, 'requirePlacement' => false));
                /** @var \Skill\Db\Collection $collection */
                foreach ($collectionList as $collection) {
                    if (!$collection->isAvailable() || !$collection->isAvailableToSubject($subject)) continue;
                    $btn = \Tk\Ui\Button::create($collection->name, \App\Uri::createSubjectUrl('/entryEdit.html')->
                        set('userId', $user->getId())->set('collectionId', $collection->getId()), $collection->icon);
                    $entry = \Skill\Db\EntryMap::create()->findFiltered(
                        array(
                            'collectionId' => $collection->getId(),
                            'subjectId' => $subject->getId(),
                            'userId' => $user->getId(),
                            'placementId' => 0
                        )
                    )->current();

                    if ($entry) {
                        $btn->addCss('btn-primary btn-xs');
                        $btn->setAttr('title', 'View Student ' . $collection->name);
                    } else {
                        $btn->addCss('btn-success btn-xs');
                        $btn->setAttr('title', 'Create Student ' . $collection->name);
                    }

                    $template->prependTemplate('utr-row2', $btn->show());
                }

            });

        }

        // STUDENT Subject Dashboard
        if ($this->controller instanceof \App\Controller\Student\SubjectDashboard) {
            $placementList = $this->controller->getPlacementList();
            $actionsCell = $placementList->getActionsCell();

            $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array('profileId' => $subject->profileId, 'requirePlacement' => true));
            foreach ($collectionList as $collection) {
                if (!$collection->isAvailable()) continue;
                $actionsCell->addButton(\Tk\Table\Cell\ActionButton::create($collection->name,
                    \App\Uri::createSubjectUrl('/entryView.html'), $collection->icon))
                    ->setShowLabel()
                    ->setOnShow(function ($cell, $obj, $btn) use ($collection) {
                        /** @var \Tk\Table\Cell\Actions $cell */
                        /** @var \App\Db\Placement $obj */
                        /** @var \Tk\Table\Cell\ActionButton $btn */
                        $entry = \Skill\Db\EntryMap::create()->findFiltered(array(
                            'collectionId' => $collection->getId(),
                            'placementId' => $obj->getId(),
                            'status' => \Skill\Db\Entry::STATUS_APPROVED
                        ))->current();
                        if (!$entry) {
                            $btn->setVisible(false);
                            return;
                        }
                        $btn->getUrl()->set('entryId', $entry->getId());
                    });
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
            \Tk\PageEvents::CONTROLLER_INIT => array('onControllerInit', 0)
        );
    }
    
}