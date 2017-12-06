<?php
namespace Skill\Db;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class EntryStatusStrategy extends \App\Db\StatusStrategyInterface
{

    /**
     * return true to trigger the status change events
     *
     * @param \App\Db\Status $status
     * @return boolean
     */
    public function triggerStatusChange($status)
    {
        $prevStatusName = $status->getPreviousName();
        switch($status->name) {
            case Entry::STATUS_PENDING:
                if (!$prevStatusName)
                    return true;
            case Entry::STATUS_APPROVED:
                if (!$prevStatusName || Entry::STATUS_PENDING == $prevStatusName)
                    return true;
            case Entry::STATUS_NOT_APPROVED:
                if (Entry::STATUS_PENDING == $prevStatusName)
                    return true;
        }
        return false;
    }

    /**
     * @param \App\Db\Status $status
     * @param \App\Db\MailTemplate $mailTemplate
     * @return null|\Tk\Mail\CurlyMessage
     * @throws \Tk\Exception
     */
    public function makeStatusMessage($status, $mailTemplate)
    {
        /** @var Entry $model */
        $model = $status->getModel();

        $placement = $model->getPlacement();
        if (!$placement->getPlacementType()->notifications) {
            \Tk\Log::warning('PlacementType[' . $placement->getPlacementType()->name . '] Notifications Disabled');
            return null;
        }
        $message = \Tk\Mail\CurlyMessage::create($mailTemplate->template);
        $message->setSubject($model->getCollection()->name . ' Entry ' . ucfirst($status->name) . ' for ' . $placement->getTitle(true) . ' ');
        $message->setFrom(\Tk\Mail\Message::joinEmail($status->getProfile()->email, $status->getCourseName()));

        // Setup the message vars
        \App\Util\StatusMessage::setStudent($message, $placement->getUser());
        \App\Util\StatusMessage::setSupervisor($message, $placement->getSupervisor());
        \App\Util\StatusMessage::setCompany($message, $placement->getCompany());
        \App\Util\StatusMessage::setPlacement($message, $placement);

        // A`dd entry details
        $message->set('collection::id', $model->getCollection()->getId());
        $message->set('collection::name', $model->getCollection()->name);
        $message->set('collection::instructions', $model->getCollection()->instructions);
        $message->set('entry::id', $model->getId());
        $message->set('entry::title', $model->title);
        $message->set('entry::assessor', $model->assessor);
        $message->set('entry::status', $model->status);
        $message->set('entry::notes', nl2br($model->notes, true));

        switch ($mailTemplate->recipient) {
            case \App\Db\MailTemplate::RECIPIENT_STUDENT:
                if ($placement->getUser()) {
                    $message->addTo(\Tk\Mail\Message::joinEmail($placement->getUser()->email, $placement->getUser()->name));
                }
                break;
            case \App\Db\MailTemplate::RECIPIENT_COMPANY:
                if ($placement->getCompany()) {
                    $message->addTo(\Tk\Mail\Message::joinEmail($placement->getCompany()->email, $placement->getCompany()->name));
                }
                break;
            case \App\Db\MailTemplate::RECIPIENT_STAFF:
                $staffList = $status->getCourse()->getStaffList();
                if (count($staffList)) {
                    /** @var \App\Db\User $s */
                    foreach ($staffList as $s) {
                        $message->addBcc(\Tk\Mail\Message::joinEmail($s->email, $s->name));
                    }
                    $message->addTo(\Tk\Mail\Message::joinEmail($status->getCourse()->getProfile()->email, $status->getCourseName()));
                    $message->set('recipient::email', $status->getCourse()->getProfile()->email);
                    $message->set('recipient::name', $status->getCourseName());
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
        $editUrl = \App\Uri::createCourseUrl('/skill/entryEdit.html')->set('collectionId', $model->collectionId)->set('courseId', $model->courseId)->
            set('userId', $model->userId)->set('placementId', $model->placementId);

        // TODO: get the icon from the entry collection
        $collection = $model->getCollection();
        return sprintf('<a href="%s"><div class="status-icon bg-secondary"><i class="'.$collection->icon.'"></i></div></a>', $editUrl);
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
        $editUrl = \App\Uri::createCourseUrl('/skill/entryEdit.html')->set('collectionId', $model->collectionId)->set('courseId', $model->courseId)->
            set('userId', $model->userId)->set('placementId', $model->placementId);
        $from = '';
        if ($model->getPlacement()) {
            $editUrl = \App\Uri::createCourseUrl('/placementEdit.html')->set('placementId', $model->getPlacement()->getId());
            $from = 'from <em>' . $model->getPlacement()->getCompany()->name . '</em>';
        }

        return sprintf('<div class="status-placement"><div><em>%s</em> %s submitted a %s Entry for <em>%s</em></div>
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
            $model->assessor, $from, $collection->name, $model->getPlacement()->getUser()->name, $editUrl);
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        /** @var Entry $model */
        $model = $this->getStatus()->getModel();
        $collection = $model->getCollection();

        return $collection->name . ' ' . \Tk\Object::basename($this->getStatus()->fkey);
    }
}