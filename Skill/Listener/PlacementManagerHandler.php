<?php
namespace Skill\Listener;

use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 * @todo; we still need to implement this
 */
class PlacementManagerHandler implements Subscriber
{


    /**
     * @var \App\Db\Subject|\Uni\Db\SubjectIface
     */
    private $subject = null;

    /**
     * @var null|\App\Controller\Placement\Manager
     */
    protected $controller = null;


    /**
     * PlacementManagerHandler constructor.
     * @param \App\Db\Subject|\Uni\Db\SubjectIface $subject
     */
    public function __construct($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\ControllerEvent $event
     */
    public function onControllerInit($event)
    {
        /** @var \App\Controller\Placement\Edit $controller */
        $controller = \Tk\Event\Event::findControllerObject($event);
        if ($controller instanceof \App\Controller\Placement\Manager) {
            $config = \Uni\Config::getInstance();
            $this->controller = $controller;

//            if (!$config->getAuthUser()->isStudent()) {
//                \Skill\Db\CollectionMap::create()->fixChangeoverEntries();
//            }
        }
    }

    /**
     * Check the user has access to this controller
     *
     * @param \Tk\Event\TableEvent $event
     * @throws \Exception
     */
    public function addActions(\Tk\Event\TableEvent $event)
    {
        if ($this->controller) {
            $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array('subjectId' => $this->subject->getId(),
                'active' => true, 'requirePlacement' => true));

            /** @var \Tk\Table\Cell\ButtonCollection $actionsCell */
            $actionsCell = $event->getTable()->findCell('actions');

            /** @var \Skill\Db\Collection $collection */
            foreach ($collectionList as $collection) {
                $url = \Uni\Uri::createSubjectUrl('/entryEdit.html')->set('collectionId', $collection->getId());

                $actionsCell->append(\Tk\Table\Ui\ActionButton::createBtn($collection->name, $url, $collection->icon))
                    ->setGroup('skills')->addOnShow(function ($cell, $obj, $btn) use ($collection) {
                        /* @var $obj \App\Db\Placement */
                        /* @var $btn \Tk\Table\Cell\ActionButton */
                        $placementCollection = \Skill\Db\CollectionMap::create()->findFiltered(array('subjectId' => $obj->getSubjectId(),
                            'active' => true, 'requirePlacement' => true, 'uid' => $collection->uid))->current();
                        if (!$placementCollection) $placementCollection = $collection;

                        $btn->setUrl(\Uni\Uri::createSubjectUrl('/entryEdit.html', $obj->getSubject())->set('collectionId', $placementCollection->getId()));
                        $btn->getUrl()->set('placementId', $obj->getId());
                        if (!$placementCollection->isAvailable($obj)) {
                            $btn->setVisible(false);
                            return;
                        }

                        $entry = \Skill\Db\EntryMap::create()->findFiltered(array('collectionId' => $placementCollection->getId(),
                            'placementId' => $obj->getId()))->current();
                        if ($entry) {
                            $btn->addCss('btn-default');
                            $btn->setText('Edit ' . $placementCollection->name);
                            //$btn->setTitle('Edit ' . $placementCollection->name);
                        } else {
                            $btn->addCss('btn-success');
                            $btn->setText('Create ' . $placementCollection->name);
                            //$btn->setTitle('Create ' . $placementCollection->name);
                        }
                    });
            }
        }
    }

    /**
     * Check the user has access to this controller
     *
     * @param \Tk\Event\TableEvent $event
     * @throws \Exception
     */
    public function addEntryCell(\Tk\Event\TableEvent $event)
    {
        if ($this->controller) {
            $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array('subjectId' => $this->subject->getId(),
                'active' => true, 'requirePlacement' => true));

            if (!$collectionList->count()) return;

            $table = $event->getTable();
            $table->appendCell(\Tk\Table\Cell\Link::create('feedbackLinks'))->setLabel('Feedback Links')
                ->addOnPropertyValue(function ($cell, $obj, $value) use ($collectionList) {
                    /** @var \App\Db\Placement $obj */
                    $value = '';
                    /** @var \Skill\Db\Collection $collection */
                    foreach ($collectionList as $collection) {
                        $url = \Uni\Uri::createInstitutionUrl('/skillEdit.html', $collection->getSubject()->getInstitution())
                            ->set('h', $obj->getHash())
                            ->set('collectionId', $collection->getId());
                        if ($collection->isAvailable($obj)) {
                            $value .= $url->toString();
                        }
                    }
                    return $value;
                })
                ->addOnCellHtml(function ($cell, $obj, $html) use ($collectionList) {
                    /** @var \Tk\Table\Cell\Link $cell */
                    /** @var \App\Db\Placement $obj */
                    $html = '';

                    /** @var \Skill\Db\Collection $collection */
                    foreach ($collectionList as $collection) {
                        $url = \Uni\Uri::createInstitutionUrl('/skillEdit.html', $collection->getSubject()->getInstitution())
                            ->set('h', $obj->getHash())
                            ->set('collectionId', $collection->getId());
                        if ($collection->isAvailable($obj)) {
                            $html .= sprintf('<a href="%s" class="btn btn-xs btn-default" title="%s" target="_blank"><i class="%s"></i></a>',
                                htmlentities($url->toString()), $collection->name, $collection->icon);
                        }
                    }
                    return '<div class="btn-toolbar" role="toolbar">'.$html.'</div>';
                });
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
            KernelEvents::CONTROLLER => array('onControllerInit', 0),
            //\Tk\PageEvents::CONTROLLER_INIT => array('addActions', 0),
            //\Tk\PageEvents::CONTROLLER_INIT => array(array('onControllerInit', 0), array('addEntryCell', 0)),
            \Tk\Table\TableEvents::TABLE_INIT => array(array('addActions', 0), array('addEntryCell', 0))
        );
    }

}