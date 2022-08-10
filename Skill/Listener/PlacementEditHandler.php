<?php
namespace Skill\Listener;

use Tk\Event\Event;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PlacementEditHandler implements Subscriber
{

    /**
     * @var \App\Db\Subject
     */
    private $subject = null;

    /**
     * @var \App\Controller\Placement\Edit
     */
    private $controller = null;

    /**
     * @var \Skill\Db\Collection[]
     */
    private $collectionList = array();


    /**
     * PlacementEditHandler constructor.
     * @param $subject
     */
    public function __construct($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @param Event $event
     * @throws \Exception
     */
    public function onPageInit(Event $event)
    {
        /** @var \App\Controller\Placement\Edit $controller */
        $controller = $event->get('controller');
        if ($controller instanceof \App\Controller\Placement\Edit) {
            $this->controller = $controller;
        }
    }

    /**
     * @param \Tk\Event\FormEvent $event
     * @throws \Exception
     */
    public function onFormLoad(\Tk\Event\FormEvent $event)
    {
        if ($this->controller) {
            if (!$this->controller->getPlacement()) return;
            $placement = $this->controller->getPlacement();
            $form = $event->getForm();

            $this->collectionList = \Skill\Db\CollectionMap::create()->findFiltered(array(
                'active' => true,
                'subjectId' => $this->subject->getId(),
                'available' => $placement->status,
                'placementTypeId' => $placement->placementTypeId,
                'requirePlacement' => true
            ));
            if ($placement->getId()) {
                /** @var \Skill\Db\Collection $collection */
                foreach ($this->collectionList as $collection) {
                    $url = \Uni\Uri::createInstitutionUrl('/skillEdit.html', $placement->getSubject()->getInstitution())
                        ->set('h', $placement->getHash())
                        ->set('collectionId', $collection->getId());

                    $form->appendField(new \App\Form\Field\InputLink('collection-' . $collection->getId()))
                        ->setTabGroup('Details')->setReadonly()->setLabel($collection->name)
                        ->setFieldset('Company Skill Urls')->setValue($url->toString())
                        ->setCopyEnabled()
                        ->setNotes('Copy this URL to send to the ' .
                            \App\Db\Phrase::findValue('company', $placement->getSubject()->getCourseId()) .
                            ' to access this ' . \App\Db\Phrase::findValue('placement', $placement->getSubject()->getCourseId(), true) .
                            ' ' . $collection->name . ' submission form.');
                }
            }
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
            if (!$this->controller->getPlacement() || !$this->controller->getPlacement()->getId()) return;
            $placement = $this->controller->getPlacement();

            /** @var \Tk\Ui\Admin\ActionPanel $actionPanel */
            $actionPanel = $this->controller->getActionPanel();

            /** @var \Skill\Db\Collection $collection */
            foreach ($this->collectionList as $collection) {
                if (!$collection->isAvailable($placement)) continue;
                $entry = \Skill\Db\EntryMap::create()->findFiltered(array('collectionId' => $collection->getId(),
                    'placementId' => $placement->getId()))->current();

                $url = \Uni\Uri::createSubjectUrl('/entryEdit.html');
                if ($entry) {
                    $url->set('entryId', $entry->getId());
                } else {
                    $url->set('collectionId', $collection->getId())
                        ->set('placementId', $placement->getId())->set('subjectId', $this->subject->getId())
                        ->set('userId', $placement->getId());
                }
                $btn = $actionPanel->add(\Tk\Ui\Button::create($collection->name, $url, $collection->icon));

                if ($entry) {
                    $btn->addCss('btn-default');
                    $btn->setAttr('title', 'Edit ' . $collection->name);
                } else {
                    $btn->addCss('btn-success');
                    $btn->setAttr('title', 'Create ' . $collection->name);
                }
            }
        }
    }

    /**
     * Check the user has access to this controller
     *
     * @param Event $event
     * @throws \Exception
     */
    public function onControllerShow(Event $event)
    {
//        $plugin = Plugin::getInstance();
//        $config = $plugin->getConfig();
        //$config->getLog()->info($plugin->getName() . ': onControllerShow(\'profile\', '.$this->profileId.') ');
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
            \Tk\PageEvents::PAGE_INIT => array('onPageInit', 0),
            \Tk\Form\FormEvents::FORM_LOAD => array('onFormLoad', 0),
            \Tk\PageEvents::CONTROLLER_INIT => array('onControllerInit', 0),
            \Tk\PageEvents::CONTROLLER_SHOW => array('onControllerShow', 0)
        );
    }
    
}