<?php
namespace Skill;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class StatusEvents
{

    /**
     *
     * @event \App\Event\StatusEvent
     */
    const ENTRY_CREATE = 'status.entry.create';

    /**
     *
     * @event \App\Event\StatusEvent
     */
    const ENTRY_APPROVED = 'status.entry.approved';

    /**
     *
     * @event \App\Event\StatusEvent
     */
    const ENTRY_NOT_APPROVED = 'status.entry.notApproved';


}