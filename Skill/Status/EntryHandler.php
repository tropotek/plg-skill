<?php
namespace Skill\Status;

use Skill\Db\Entry;
use App\Db\Status;
use App\Status\HandlerInterface;

/**
 * Define when the status event should be triggered
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class EntryHandler extends HandlerInterface
{

    /**
     * @param Status $status
     * @return mixed
     */
    function execute($status)
    {
        $prevStatus = '';
        if ($status->getPrevious()) {
            $prevStatus = $status->getPrevious()->name;
        }

        if ($status->name != $prevStatus) {
            switch($status->name) {
                case Entry::STATUS_PENDING:
                    if (!$prevStatus) {
                        $status->event = \Skill\StatusEvents::ENTRY_CREATE;
                    }
                    break;
                case Entry::STATUS_APPROVED:
                    if (!$prevStatus || Entry::STATUS_PENDING == $prevStatus) {
                        $status->event = \Skill\StatusEvents::ENTRY_APPROVED;
                    }
                    break;
                case Entry::STATUS_NOT_APPROVED:
                    if (!$prevStatus || Entry::STATUS_PENDING == $prevStatus) {
                        $status->event = \Skill\StatusEvents::ENTRY_NOT_APPROVED;
                    }
                    break;
            }

            $status->save();
            $this->dispatchEvent($status);
        }

    }

}