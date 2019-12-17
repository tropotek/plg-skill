<?php
namespace Skill\Db;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class EntryStatusStrategy extends \App\Db\StatusStrategyInterface
{

    /**
     * return true to trigger the status change events
     *
     * @param \App\Db\Status $status
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
     * @param \App\Db\Status $status
     * @param \App\Db\MailTemplate $mailTemplate
     * @return null|\Tk\Mail\CurlyMessage
     * @throws \Exception
     */
    public function makeStatusMessage($status, $mailTemplate)
    {
        /** @var Entry $model */
        $model = $status->getModel();

        $placement = $model->getPlacement();
        if (!$placement->getPlacementType()->notifications) {
            \Tk\Log::warning('PlacementType[' . $placement->getPlacementType()->getName() . '] Notifications Disabled');
            return null;
        }
        $message = \Tk\Mail\CurlyMessage::create($mailTemplate->getTemplate());
        $message->setSubject('[#'.$model->getId().'] ' . $model->getCollection()->getName() . ' Entry ' . ucfirst($status->getName()) . ' for ' . $placement->getTitle(true) . ' ');
        $message->setFrom(\Tk\Mail\Message::joinEmail($status->getCourse()->getEmail(), $status->getSubjectName()));

        // Setup the message vars
        \App\Util\StatusMessage::setStudent($message, $placement->getUser());
        \App\Util\StatusMessage::setSupervisor($message, $placement->getSupervisor());
        \App\Util\StatusMessage::setCompany($message, $placement->getCompany());
        \App\Util\StatusMessage::setPlacement($message, $placement);

        // A`dd entry details
        $message->set('collection::id', $model->getCollection()->getId());
        $message->set('collection::name', $model->getCollection()->getName());
        $message->set('collection::instructions', $model->getCollection()->getInstructions());
        $message->set('entry::id', $model->getId());
        $message->set('entry::title', $model->title);
        $message->set('entry::assessor', $model->assessor);
        $message->set('entry::status', $model->status);
        $message->set('entry::notes', nl2br($model->notes, true));

        switch ($mailTemplate->recipient) {
            case \App\Db\MailTemplate::RECIPIENT_STUDENT:
                if ($placement->getUser()) {
                    $message->addTo(\Tk\Mail\Message::joinEmail($placement->getUser()->getEmail(), $placement->getUser()->getName()));
                }
                break;
            case \App\Db\MailTemplate::RECIPIENT_COMPANY:
                if ($placement->getCompany()) {
                    $message->addTo(\Tk\Mail\Message::joinEmail($placement->getCompany()->getEmail(), $placement->getCompany()->getName()));
                }
                break;
            case \App\Db\MailTemplate::RECIPIENT_SUPERVISOR:
                if ($placement->getSupervisor() && $placement->getSupervisor()->getEmail())
                    $message->addTo(\Tk\Mail\Message::joinEmail($placement->getSupervisor()->getEmail(), $placement->getSupervisor()->getName()));
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

        $userName = $model->getPlacement()->getUser()->getName();
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