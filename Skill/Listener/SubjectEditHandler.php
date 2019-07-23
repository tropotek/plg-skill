<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;
use Tk\Event\Event;
use Skill\Plugin;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SubjectEditHandler implements Subscriber
{

    /**
     * @var \App\Controller\Subject\Edit
     */
    protected $controller = null;



    /**
     * @param \Symfony\Component\HttpKernel\Event\ControllerEvent $event
     */
    public function onKernelController($event)
    {
        /** @var \App\Controller\Subject\Edit $controller */
        $controller = \Tk\Event\Event::findControllerObject($event);
        if ($controller instanceof \App\Controller\Subject\Edit) {
            $this->controller = $controller;
        }
    }


    /**
     * Check the user has access to this controller
     *
     * @param Event $event
     * @throws \Exception
     */
    public function onControllerInit(Event $event)
    {
        if ($this->controller) {
            if (!$this->controller->getUser()->isStaff() || !$this->controller->getSubject()->getId()) return;
            /** @var \Tk\Ui\Admin\ActionPanel $actionPanel */
            $actionPanel = $this->controller->getActionPanel();
            $actionPanel->append(\Tk\Ui\Link::createBtn('Skill Collections',
                \Uni\Uri::createSubjectUrl('/collectionManager.html'), 'fa fa-graduation-cap'));
        }
    }

    /**
     * @param \Bs\Event\DbEvent $event
     * @throws \Exception
     */
    public function onCreateSubject(\Bs\Event\DbEvent $event)
    {
        /** @var \Uni\Db\Subject $model */
        $model = $event->getModel();
        if (!$model instanceof \Uni\Db\SubjectIface) return;
        $previous = \Uni\Config::getInstance()->getLastCreatedSubject();
        if (!$previous) return;
        if ($model->getId() == $previous->getId()) {
            $previous = \Uni\Config::getInstance()->getLastCreatedSubject(true);
        }
        $subjectId = $previous->getId();
        $collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array('subjectId' => $subjectId), \Tk\Db\Tool::create('id'));
        // Copy Subject Collections
        foreach ($collectionList as $collection) {
            \Tk\Log::debug('Copying Skill Collection: ' . $collection->name);
            /** @var \Skill\Db\Collection $newC */
            $newC = clone $collection;
            //$newC->profileId = $model->profileId;
            $newC->subjectId = $model->getVolatileId();
            $newC->publish = false;
            $newC->active = true;
            $newC->save();
            //$newC = \Skill\Db\CollectionMap::create()->find($newC->getId());

            // Copy placement_type
            \Tk\Log::debug('Copying Skill Placement Types');
            $list = \Skill\Db\CollectionMap::create()->findPlacementTypes($collection->getId());
            foreach ($list as $id) {
                \Skill\Db\CollectionMap::create()->addPlacementType($newC->getId(), $id);
            }
            // Copy Domains
            \Tk\Log::debug('Copying Skill Domains');
            $list = \Skill\Db\DomainMap::create()->findFiltered(array('collectionId' => $collection->getId()));
            foreach ($list as $src) {
                $dst = clone $src;
                $dst->collectionId = $newC->getId();
                $dst->save();
            }
            // Copy Categories
            \Tk\Log::debug('Copying Skill Categories');
            $list = \Skill\Db\CategoryMap::create()->findFiltered(array('collectionId' => $collection->getId()));
            foreach ($list as $src) {
                $dst = clone $src;
                $dst->collectionId = $newC->getId();
                $dst->save();
            }
            // Copy Scale
            \Tk\Log::debug('Copying Skill Scale');
            $list = \Skill\Db\ScaleMap::create()->findFiltered(array('collectionId' => $collection->getId()));
            foreach ($list as $src) {
                $dst = clone $src;
                $dst->collectionId = $newC->getId();
                $dst->save();
            }
            // Copy Items
            \Tk\Log::debug('Copying Skill Items');
            $list = \Skill\Db\ItemMap::create()->findFiltered(array('collectionId' => $collection->getId()));
            /** @var \Skill\Db\Item $src */
            foreach ($list as $src) {
                /** @var \Skill\Db\Item $dst */
                $dst = clone $src;
                $dst->collectionId = $newC->getId();

                $orgDomain = $src->getDomain();
                if ($orgDomain) {
                    $dstDomain = \Skill\Db\DomainMap::create()->findFiltered(array('collectionId' => $newC->getId(), 'uid' => $orgDomain->uid));
                    $dstDomain = $dstDomain->current();
                    if ($dstDomain) {
                        $dst->domainId = $dstDomain->getId();
                    }
                }

                $orgCat = $src->getCategory();
                if ($orgCat) {
                    $dstCat = \Skill\Db\CategoryMap::create()->findFiltered(array('collectionId' => $newC->getId(), 'uid' => $orgCat->uid));
                    $dstCat = $dstCat->current();
                    if ($dstCat) {
                        $dst->categoryId = $dstCat->getId();
                    }
                }

                $dst->save();
            }
        }
        \Tk\Log::debug('Copying Collections Complete');
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
            KernelEvents::CONTROLLER => array('onKernelController', 0),
            \Tk\PageEvents::CONTROLLER_INIT => array('onControllerInit', 0),
            \Bs\DbEvents::MODEL_INSERT => array('onCreateSubject', 0),
            //\Tk\Form\FormEvents::FORM_LOAD => array('onFormInit', 0)
        );
    }
    
}