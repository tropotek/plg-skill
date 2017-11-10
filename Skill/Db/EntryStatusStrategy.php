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
        }

        return $message;
    }


    /**
     * @return string
     */
    public function getPendingIcon()
    {
        /** @var Entry $model */
        $model = $this->getStatus()->getModel();

        // TODO: get the icon from the entry collection
        $collection = $model->getCollection();
        return sprintf('<div class="status-icon bg-secondary" title="Company"><i class="'.$collection->icon.'"></i></div>');
    }

    /**
     * @return string
     */
    public function getPendingHtml()
    {
        /** @var Entry $model */
        $model = $this->getStatus()->getModel();
        $collection = $model->getCollection();

        return sprintf('<em>%s</em> submitted a %s Entry for <em>%s</em>',
            $model->assessor, $collection->name, $model->getPlacement()->getUser()->name);
    }

    /**
     * @return null|\Tk\Uri
     */
    public function getPendingActionLink()
    {
        // TODO: Implement getLinkHtml() method.
    }
}