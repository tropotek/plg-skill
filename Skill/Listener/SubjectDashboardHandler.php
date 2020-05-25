<?php
namespace Skill\Listener;

use Tk\Event\Event;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SubjectDashboardHandler implements Subscriber
{

    /**
     * @var \App\Db\Subject|\Uni\Db\SubjectIface
     */
    private $subject = null;

    /**
     * @var \App\Controller\Iface
     */
    protected $controller = null;



    /**
     * constructor.
     * @param \App\Db\Subject|\Uni\Db\SubjectIface $subject
     */
    public function __construct($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Check the user has access to this controller
     *
     * @param Event $event
     * @throws \Exception
     */
    public function onControllerInit(Event $event)
    {
        /** @var \App\Controller\Staff\SubjectDashboard $controller */
        $this->controller = $event->get('controller');
        $subject = $this->controller->getConfig()->getSubject();

        // STAFF Subject Dashboard
        if ($this->controller instanceof \App\Controller\Staff\SubjectDashboard) {
            $userList = $this->controller->getSubjectUserList();
            $userList->addOnShowUser(function (\Dom\Template $template, \App\Db\User $user) use ($subject) {
                $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(
                    array('subjectId' => $subject->getId(), 'gradable' => true, 'requirePlacement' => true, 'active' => true));
                /** @var \Skill\Db\Collection $collection */
                foreach ($collectionList as $collection) {
                    if (!$collection->isAvailable()) continue;
                    // if user has a placement of at least one of the types and status
                    $entryList = \Skill\Db\EntryMap::create()->findFiltered(array(
                        'userId' => $user->getId(),
                        'collectionId' => $collection->getId(),
                        'status' => \Skill\Db\Entry::STATUS_APPROVED
                    ));
                    if (!$entryList->count()) continue;

                    $btn = \Tk\Ui\Link::createBtn($collection->name . ' Results', \Uni\Uri::createSubjectUrl('/entryResults.html')
                        ->set('userId', $user->getId())->set('collectionId', $collection->getId()), $collection->icon);
                    $btn->addCss('btn-primary btn-xs');
                    $btn->setAttr('title', 'View Student ' . $collection->name . ' Results');
                    $template->prependTemplate('utr-row2', $btn->show());

                }

                $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array('subjectId' => $subject->getId(), 'requirePlacement' => false, 'active' => true));
                /** @var \Skill\Db\Collection $collection */
                foreach ($collectionList as $collection) {
                    if (!$collection->isAvailable()) continue;
                    $btn = \Tk\Ui\Link::createBtn($collection->name, \Uni\Uri::createSubjectUrl('/entryEdit.html')
                        ->set('userId', $user->getId())->set('collectionId', $collection->getId()), $collection->icon);
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
                        continue;
                    }

                    $template->prependTemplate('utr-row2', $btn->show());
                }

            });

        }

        // STUDENT Subject Dashboard
        if ($this->controller instanceof \App\Controller\Student\SubjectDashboard) {
            $placementList = $this->controller->getPlacementList();
            $actionCell = $placementList->getActionCell();

            $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array('subjectId' => $subject->getId(), 'requirePlacement' => true, 'active' => true));
            foreach ($collectionList as $collection) {
                if (!$collection->isAvailable() || !$collection->publish) continue;
                $actionCell->addButton(\Tk\Table\Cell\ActionButton::create($collection->name,
                    \Uni\Uri::createSubjectUrl('/entryView.html'), $collection->icon))
                    ->setShowLabel()
                    ->addOnShow(function ($cell, $obj, $btn) use ($collection) {
                        /** @var \Tk\Table\Cell\Actions $cell */
                        /** @var \App\Db\Placement $obj */
                        /** @var \Tk\Table\Cell\ActionButton $btn */
                        if (!$obj->getPlacementType() || !$obj->getPlacementType()->enableReport || $obj->status != \App\Db\Placement::STATUS_COMPLETED) {
                            $btn->setVisible(false);
                            return;
                        }

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