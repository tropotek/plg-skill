<?php
namespace Skill\Listener;

use Tk\Event\Event;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 * @todo; we still need to implement this
 */
class StudentManagerButtonHandler implements Subscriber
{

    /**
     * @var \App\Db\Subject
     */
    private $subject = null;


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
        /** @var \App\Controller\Placement\Edit $controller */
        $controller = $event->get('controller');
        if ($controller instanceof \App\Controller\User\StudentManager) {

            $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array(
                'subjectId' => $this->subject->getId(),
                'active' => true,
                'requirePlacement' => false)
            );
            $actionsCell = $controller->getActionsCell();

            /** @var \Skill\Db\Collection $collection */
            foreach ($collectionList as $collection) {
                $url = \Uni\Uri::createSubjectUrl('/entryEdit.html')->set('collectionId', $collection->getId());

                $actionsCell->addButton(\Tk\Table\Cell\ActionButton::create($collection->name, $url, $collection->icon))
                    ->addOnShow(function ($cell, $obj, $button) use ($collection) {
                        /* @var $obj \App\Db\User */
                        /* @var $button \Tk\Table\Cell\ActionButton */
                        $button->getUrl()->set('userId', $obj->getId());
                        $entry = \Skill\Db\EntryMap::create()->findFiltered(array('collectionId' => $collection->getId(),
                            'userId' => $obj->getId()))->current();

                        if (!$collection->isAvailable()) {
                            $button->setVisible(false);
                            return;
                        }

                        if ($entry) {
                            $button->addCss('btn-default');
                        } else {
                            $button->addCss('btn-success');
                            $button->setTitle('Create ' . $collection->name);
                        }
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