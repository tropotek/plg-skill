<?php
namespace Skill\Listener;

use Tk\Event\Subscriber;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 * @todo; we still need to implement this
 */
class CompanyManagerHandler implements Subscriber
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
        if ($controller instanceof \App\Controller\Company\Manager) {
            $config = \Uni\Config::getInstance();
            $this->controller = $controller;

            $links = array();
            $collections = \Skill\Db\CollectionMap::create()->findFiltered(array(
                'subjectId' => $this->subject->getId(),
                'gradable' => true,
                'requirePlacement' => true,
                'active' => true
            ));
            foreach ($collections as $collection) {
                $links[] = \Tk\Ui\Link::create($collection->name,
                    \Uni\Uri::createSubjectUrl('/companyAverageReport.html', $this->subject)->set('collectionId', $collection->getId()),
                    $collection->icon
                );
            }
            if (count($links)) {
                $controller->getActionPanel()->append(\Tk\Ui\ButtonDropdown::createButtonDropdown(
                    'Skills Average Report',
                    'fa fa-graduation-cap',
                    $links)->setForceList(true));
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
            \Tk\PageEvents::CONTROLLER_SHOW => array('onControllerInit', 10),
            //\Tk\PageEvents::PAGE_SHOW => array('onControllerInit', 0),
            //KernelEvents::CONTROLLER => array('onControllerInit', 0),
            //\Tk\PageEvents::CONTROLLER_INIT => array('addActions', 0),
            //\Tk\PageEvents::CONTROLLER_INIT => array(array('onControllerInit', 0), array('addEntryCell', 0)),
            //\Tk\Table\TableEvents::TABLE_INIT => array(array('addActions', 0), array('addEntryCell', 0))
        );
    }

}