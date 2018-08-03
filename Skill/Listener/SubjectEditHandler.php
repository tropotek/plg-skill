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
class SubjectEditHandler implements Subscriber
{

    /**
     * @var \App\Db\Subject
     */
    private $subject = null;

    /**
     * @var \App\Controller\Subject\Edit
     */
    protected $controller = null;



    /**
     * @param $subject
     */
    public function __construct($subject)
    {
        $this->subject = $subject;
    }


    /**
     * @param \Tk\Event\ControllerEvent $event
     */
    public function onKernelController(\Tk\Event\ControllerEvent $event)
    {
        /** @var \App\Controller\Subject\Edit $controller */
        $controller = $event->getController();
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
            if (!$this->controller->getUser()->isStaff()) return;
            /** @var \Tk\Ui\Admin\ActionPanel $actionPanel */
            $actionPanel = $this->controller->getActionPanel();
            $actionPanel->add(\Tk\Ui\Button::create('Skill Collections',
                \Uni\Uri::createSubjectUrl('/entryCollectionManager.html'), 'fa fa-graduation-cap'));
        }
    }

    /**
     * @param \Tk\Event\FormEvent $event
     * @throws \Tk\Db\Exception
     * @throws \Tk\Form\Exception
     */
    public function onFormInit($event)
    {
        if (!$this->controller) return;

        $form = $event->getForm();

        $list = \Skill\Db\CollectionMap::create()->findFiltered(
            array('profileId' => $this->subject->profileId)
        );
        $field = $form->addField(new \Tk\Form\Field\Select(\Skill\Db\Collection::FIELD_ENABLE_RESULTS.'[]', \Tk\Form\Field\Option\ArrayObjectIterator::create($list)))->addCss('tk-dual-select')
            ->setAttr('data-title', 'Enabled Skill Collections')->setNotes('Enable/Disable the Skill Collections students can access.');
        $selected = \Skill\Db\CollectionMap::create()->findFiltered(
            array('enabledSubjectId' => $this->subject->getId())
        );
        $field->setValue($selected->toArray('id'));

        $form->addEventCallback('update', array($this, 'doSubmit'));
        $form->addEventCallback('save', array($this, 'doSubmit'));
    }



    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        $list = $form->getFieldValue(\Skill\Db\Collection::FIELD_ENABLE_RESULTS);
        if (!is_array($list)) {
            $form->addFieldError(\Skill\Db\Collection::FIELD_ENABLE_RESULTS, 'Invalid collection values given.');
        }

        if ($form->hasErrors()) {
            return;
        }

        // Save collection links
        \Skill\Db\CollectionMap::create()->removeSubject($this->subject->getId());
        foreach ($list as $collectionId) {
            \Skill\Db\CollectionMap::create()->addSubject($this->subject->getId(), $collectionId);
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
            \Tk\Kernel\KernelEvents::CONTROLLER => array('onKernelController', 0),
            \Tk\PageEvents::CONTROLLER_INIT => array('onControllerInit', 0),
            \Tk\Form\FormEvents::FORM_LOAD => array('onFormInit', 0)
        );
    }
    
}