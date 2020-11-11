<?php
namespace Skill\Db;


use App\Db\MailTemplate;
use Tk\Mail\CurlyMessage;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 * @deprecated
 */
class EntryStatusStrategy extends \Uni\Db\StatusStrategyInterface
{

    /**
     * return true to trigger the status change events
     *
     * @param \Uni\Db\Status $status
     * @return boolean
     * @throws \Exception
     */
    public function triggerStatusChange($status)
    {
        $prevStatusName = $status->getPreviousName();
        switch($status->name) {
            case Entry::STATUS_PENDING:
                if (!$prevStatusName)
                    return true;
                break;
            case Entry::STATUS_APPROVED:
                if (!$prevStatusName || Entry::STATUS_PENDING == $prevStatusName)
                    return true;
                break;
            case Entry::STATUS_NOT_APPROVED:
                if (Entry::STATUS_PENDING == $prevStatusName)
                    return true;
                break;
        }
        return false;
    }

    /**
     * @param \Uni\Db\Status $status
     * @param CurlyMessage $message
     * @return null|\Tk\Mail\CurlyMessage
     * @throws \Exception
     */
    public function formatStatusMessage($status, $message)
    {
        /** @var Entry $model */
        $model = $status->getModel();

        $placement = $model->getPlacement();
        if (!$placement->getPlacementType()->isNotifications()) {
            \Tk\Log::warning('PlacementType[' . $placement->getPlacementType()->getName() . '] Notifications Disabled');
            return null;
        }

        $message->setSubject('[#'.$model->getId().'] ' . $model->getCollection()->getName() . ' Entry ' .
            ucfirst($status->getName()) . ' for ' . $placement->getTitle(true) . ' ');
        $message->setFrom(\Tk\Mail\Message::joinEmail($status->getCourse()->getEmail(), $status->getSubjectName()));

        // Setup the message vars
        \App\Util\StatusMessage::setStudent($message, $placement->getAuthUser());
        \App\Util\StatusMessage::setSupervisor($message, $placement->getSupervisor());
        \App\Util\StatusMessage::setCompany($message, $placement->getCompany());
        \App\Util\StatusMessage::setPlacement($message, $placement);

        // A`dd entry details
        $message->set('collection::id', $model->getCollection()->getId());
        $message->set('collection::name', $model->getCollection()->getName());
        $message->set('collection::instructions', $model->getCollection()->getInstructions());
        $message->set('entry::id', $model->getId());
        $message->set('entry::title', $model->getTitle());
        $message->set('entry::assessor', $model->getAssessor());
        $message->set('entry::status', $model->getStatus());
        $message->set('entry::notes', nl2br($model->getNotes(), true));

        /** @var MailTemplate $mailTemplate */
        $mailTemplate = $message->get('_mailTemplate');

        switch ($mailTemplate->getRecipient()) {
            case \App\Db\MailTemplate::RECIPIENT_STUDENT:
                $student = $placement->getUser();
                if ($student && $student->getEmail()) {
                    $message->addTo(\Tk\Mail\Message::joinEmail($student->getEmail(), $student->getName()));
                    $message->set('recipient::email', $student->getEmail());
                    $message->set('recipient::name', $student->getName());
                }
                break;
            case \App\Db\MailTemplate::RECIPIENT_COMPANY:
                $company = $placement->getCompany();
                if ($company && $company->getEmail()) {
                    $message->addTo(\Tk\Mail\Message::joinEmail($company->getEmail(), $company->getName()));
                    $message->set('recipient::email', $company->getEmail());
                    $message->set('recipient::name', $company->getName());
                }
                break;
            case \App\Db\MailTemplate::RECIPIENT_SUPERVISOR:
                $supervisor = $placement->getSupervisor();
                if ($supervisor && $supervisor->getEmail())
                    $message->addTo(\Tk\Mail\Message::joinEmail($supervisor->getEmail(), $supervisor->getName()));
                    $message->set('recipient::email', $supervisor->getEmail());
                    $message->set('recipient::name', $supervisor->getName());
                break;
            case \App\Db\MailTemplate::RECIPIENT_STAFF:
                $staffList = $status->getSubject()->getCourse()->getUsers();
                if (count($staffList)) {
                    /** @var \App\Db\User $s */
                    foreach ($staffList as $s) {
                        $message->addBcc(\Tk\Mail\Message::joinEmail($s->getEmail(), $s->getName()));
                    }
                    $message->addTo(\Tk\Mail\Message::joinEmail($status->getSubject()->getCourse()->getEmail(), $status->getSubjectName()));
                    $message->set('recipient::email', $status->getSubject()->getCourse()->getEmail());
                    $message->set('recipient::name', $status->getSubjectName());
                }
                break;
        }

        return $message;
    }


    /**
     * @return string
     * @throws \Exception
     */
    public function getPendingIcon()
    {
        /** @var Entry $model */
        $model = $this->getStatus()->getModel();

        $editUrl = \Uni\Uri::createSubjectUrl('/entryEdit.html')->set('entryId', $model->getId());
        if (!$model->getId()) {
            $editUrl = \Uni\Uri::createSubjectUrl('/entryEdit.html')->set('collectionId', $model->collectionId)->
            set('userId', $model->userId)->set('placementId', $model->placementId);
        }

        // TODO: get the icon from the entry collection
        $collection = $model->getCollection();
        return sprintf('<a href="%s"><div class="status-icon bg-secondary"><i class="'.$collection->icon.'"></i></div></a>',
            htmlentities($editUrl));
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getPendingHtml()
    {
        /** @var Entry $model */
        $model = $this->getStatus()->getModel();
        $collection = $model->getCollection();
//        $editUrl = \Uni\Uri::createSubjectUrl('/entryEdit.html')->set('collectionId', $model->collectionId)->
//        set('userId', $model->userId)->set('placementId', $model->placementId);
        $editUrl = \Uni\Uri::createSubjectUrl('/entryEdit.html')->set('entryId', $model->getId());
        $from = '';

        $userName = $model->getPlacement()->getAuthUser()->getName();
//        $userName = 'Unknown';
//        if (!$model->getUser()) {
//            $userName = $model->getPlacement()->getUser()->getName();
//        } else {
//            $userName = $model->getUser()->getName();
//        }

        if ($model->getPlacement()) {
            //$editUrl = \Uni\Uri::createSubjectUrl('/entryEdit.html')->set('placementId', $model->getPlacement()->getId());
            $from = 'from <em>' . htmlentities($model->getPlacement()->getCompany()->getName()) . '</em>';
            //$userName = $model->getPlacement()->getUser()->name;
        }

        $html = sprintf('<div class="status-placement"><div><em>%s</em> %s submitted a %s Entry for <em>%s</em></div>
  <div class="status-actions">
    <a href="%s" class="edit"><i class="fa fa-pencil"></i> Edit</a>
   <!--  |
    <a href="#" class="view"><i class="fa fa-eye"></i> View</a> |
    <a href="#" class="approve"><i class="fa fa-check"></i> Approve</a> |
    <a href="#" class="reject"><i class="fa fa-times"></i> Reject</a> |
    <a href="#" class="email"><i class="fa fa-envelope"></i> Email</a>
    -->
  </div>
</div>',
            htmlentities($model->assessor), $from, htmlentities($collection->getName()), htmlentities($userName), htmlentities($editUrl));

        return $html;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getLabel()
    {
        /** @var Entry $model */
        $model = $this->getStatus()->getModel();
        $collection = $model->getCollection();

        return $collection->getName() . ' ' . \Tk\ObjectUtil::basename($this->getStatus()->getFkey());
    }
}