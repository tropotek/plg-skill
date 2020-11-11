<?php
namespace Skill\Db;


use App\Db\MailTemplate;
use App\Db\Traits\PlacementTrait;
use Bs\Db\Status;
use Bs\Db\Traits\TimestampTrait;
use Bs\Db\Traits\UserTrait;
use Bs\Db\Traits\StatusTrait;
use Dom\Template;
use Tk\Mail\CurlyMessage;
use Uni\Db\Traits\SubjectTrait;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Entry extends \Tk\Db\Map\Model implements \Tk\ValidInterface
{
    use StatusTrait;
    use PlacementTrait;
    use SubjectTrait;
    use UserTrait;
    use TimestampTrait;


    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_NOT_APPROVED = 'not approved';


    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $collectionId = 0;

    /**
     * @var int
     */
    public $subjectId = 0;

    /**
     * @var int
     */
    public $userId = 0;

    /**
     * @var int
     */
    public $placementId = 0;

    /**
     * @var string
     */
    public $title = '';

    /**
     * @var string
     */
    public $assessor = '';

    /**
     * The number of days the student was absent for this placement
     * @var int
     */
    public $absent = 0;

    /**
     * @var float
     */
    public $average = 0.0;

    /**
     * @var float
     */
    public $weightedAverage = 0.0;

    /**
     * @var int|null
     */
    public $confirm = null;

    /**
     * @var string
     */
    public $status = self::STATUS_PENDING;

    /**
     * @var string
     */
    public $notes = '';

    /**
     * @var \DateTime
     */
    public $modified = null;

    /**
     * @var \DateTime
     */
    public $created = null;


    /**
     * @var Collection
     */
    private $_collection = null;



    /**
     * constructor.
     */
    public function __construct()
    {
        $this->_TimestampTrait();
    }


    /**
     * A legacy call usefull for EMS II functionality
     * Try to avoid using it if possible.
     *
     * @return bool
     * @throws \Exception
     */
    public function isSelfAssessment()
    {
        $collection = $this->getCollection();
        if ($collection && !$collection->gradable && !$collection->requirePlacement &&
            $collection->role == \Skill\Db\Collection::TYPE_STUDENT) {
            return true;
        }
        return false;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function update()
    {
        $calc = new \Skill\Util\GradeCalculator($this->getCollection());
        $calc->deleteStudentGradeCache($this->getUser());
        return parent::update();
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        $this->average = $this->calcAverage();
        $this->weightedAverage = $this->calcDomainAverage(true);

        parent::save();
    }

//    public function delete()
//    {
//        \Bs\Db\StatusMap::create()->deleteByModel(get_class($this), $this->getId());
//        return parent::delete();
//    }


    /**
     * @return \Skill\Db\Collection|null|\Tk\Db\Map\Model|\Tk\Db\ModelInterface
     * @throws \Exception
     */
    public function getCollection()
    {
        if (!$this->_collection) {
            $this->_collection = CollectionMap::create()->find($this->collectionId);
        }
        return $this->_collection;
    }

    /**
     * @param bool $weighted
     * @return float
     * @throws \Exception
     */
    public function calcDomainAverage($weighted = false)
    {
        $grades = array();
        $valueList = EntryMap::create()->findValue($this->getId());
        foreach ($valueList as $value) {
            //if (!$value->value && !$this->getCollection()->includeZero) continue;
            if (!$value->value) continue;
            /** @var \Skill\Db\Item $item */
            $item = \Skill\Db\ItemMap::create()->find($value->item_id);
            //$val = (int)$value->value;
            $val = $value->value;
            $did = 0;
            if ($item->getDomain())
                $did = $item->getDomain()->getId();
            $grades[$did][$value->item_id] = $val;
        }
        $avgs = array();
        foreach ($grades as $domainId => $valArray) {
            /** @var \Skill\Db\Domain $domain */
            $domain = \Skill\Db\DomainMap::create()->find($domainId);
            if ($weighted) {
                $avgs[$domainId] = \Tk\Math::average($valArray) * $domain->weight;
            } else {
                $avgs[$domainId] = \Tk\Math::average($valArray);
            }
        }
        if (!count($grades)) return 0;
        $avg = array_sum($avgs)/count($grades);
        if (!$weighted)
            vd($grades, $avgs, array_sum($avgs) , count($grades), $avg);
        return $avg;
    }

    /**
     * Get the entry values average, this average is not weighted to the Domain.weight values
     *
     * @return float
     * @throws \Exception
     */
    public function calcAverage()
    {
        return EntryMap::create()->getEntryAverage($this->getId());
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Entry
     */
    public function setTitle(string $title): Entry
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getAssessor(): string
    {
        return $this->assessor;
    }

    /**
     * @param string $assessor
     * @return Entry
     */
    public function setAssessor(string $assessor): Entry
    {
        $this->assessor = $assessor;
        return $this;
    }

    /**
     * @return int
     */
    public function getAbsent(): int
    {
        return $this->absent;
    }

    /**
     * @param int $absent
     * @return Entry
     */
    public function setAbsent(int $absent): Entry
    {
        $this->absent = $absent;
        return $this;
    }

    /**
     * @return float
     */
    public function getAverage(): float
    {
        return $this->average;
    }

    /**
     * @param float $average
     * @return Entry
     */
    public function setAverage(float $average): Entry
    {
        $this->average = $average;
        return $this;
    }

    /**
     * @return float
     */
    public function getWeightedAverage(): float
    {
        return $this->weightedAverage;
    }

    /**
     * @param float $weightedAverage
     * @return Entry
     */
    public function setWeightedAverage(float $weightedAverage): Entry
    {
        $this->weightedAverage = $weightedAverage;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getConfirm(): ?int
    {
        return $this->confirm;
    }

    /**
     * @param int|null $confirm
     * @return Entry
     */
    public function setConfirm(?int $confirm): Entry
    {
        $this->confirm = $confirm;
        return $this;
    }

    /**
     * @return string
     */
    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     * @return Entry
     */
    public function setNotes(string $notes): Entry
    {
        $this->notes = $notes;
        return $this;
    }


    /**
     * @return array
     * @throws \Exception
     */
    public function validate()
    {
        $errors = array();
        if ((int)$this->collectionId <= 0) {
            $errors['collectionId'] = 'Invalid Collection ID';
        }
        if ((int)$this->subjectId <= 0) {
            $errors['subjectId'] = 'Invalid Subject ID';
        }
        if ((int)$this->userId <= 0) {
            $errors['userId'] = 'Invalid User ID';
        }
        if (!$this->assessor) {
            $errors['assessor'] = 'Please enter a valid assessors name';
        }
        if ($this->getCollection()->confirm && $this->confirm === null) {
            $errors['confirm'] = 'Please select a valid answer.';
            $errors['form'] = 'Please answer the confirmation question.';
        }
        return $errors;
    }




    /**
     * Must be Called after the status object is saved.
     * Should return true if the status has changed and the statusChange event should be triggered
     *
     * @param Status $status
     * @return boolean
     * @throws \Exception
     */
    public function hasStatusChanged(Status $status)
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
     * @param \Bs\Db\Status $status
     * @param CurlyMessage $message
     * @return null|\Tk\Mail\CurlyMessage
     * @throws \Exception
     */
    public function formatStatusMessage($status, $message)
    {
        $model = $this;
        $placement = $model->getPlacement();
        if (!$placement->getPlacementType()->isNotifications()) {
            \Tk\Log::warning('PlacementType[' . $placement->getPlacementType()->getName() . '] Notifications Disabled');
            return null;
        }

        $message->setSubject('[#'.$model->getId().'] ' . $model->getCollection()->getName() . ' Entry ' .
            ucfirst($status->getName()) . ' for ' . $placement->getTitle(true) . ' ');
        $message->setFrom(\Tk\Mail\Message::joinEmail(\Uni\Util\Status::getCourse($status)->getEmail(), \Uni\Util\Status::getSubjectName($status)));

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
                $subject = \Uni\Util\Status::getSubject($status);
                $staffList = $subject->getCourse()->getUsers();
                if (count($staffList)) {
                    /** @var \App\Db\User $s */
                    foreach ($staffList as $s) {
                        $message->addBcc(\Tk\Mail\Message::joinEmail($s->getEmail(), $s->getName()));
                    }
                    $message->addTo(\Tk\Mail\Message::joinEmail($subject->getCourse()->getEmail(), \Uni\Util\Status::getSubjectName($status)));
                    $message->set('recipient::email', $subject->getCourse()->getEmail());
                    $message->set('recipient::name', \Uni\Util\Status::getSubjectName($status));
                }
                break;
        }

        return $message;
    }

    /**
     * @return string|Template
     */
    public function getPendingIcon()
    {
        $editUrl = \Uni\Uri::createSubjectUrl('/entryEdit.html')->set('entryId', $this->getId());
        if (!$this->getId()) {
            $editUrl = \Uni\Uri::createSubjectUrl('/entryEdit.html')->set('collectionId', $this->collectionId)->
            set('userId', $this->getUserId())->set('placementId', $this->getPlacementId());
        }

        // TODO: get the icon from the entry collection
        $collection = $this->getCollection();
        return sprintf('<a href="%s"><div class="status-icon bg-secondary"><i class="'.$collection->icon.'"></i></div></a>',
            htmlentities($editUrl));
    }

    /**
     * @return string|Template
     * @throws \Exception
     */
    public function getPendingHtml()
    {
        $collection = $this->getCollection();
        $editUrl = \Uni\Uri::createSubjectUrl('/entryEdit.html')->set('entryId', $this->getId());
        $from = '';

        $userName = $this->getPlacement()->getAuthUser()->getName();
        if ($this->getPlacement()) {
            $from = 'from <em>' . htmlentities($this->getPlacement()->getCompany()->getName()) . '</em>';
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
            htmlentities($this->assessor), $from, htmlentities($collection->getName()), htmlentities($userName), htmlentities($editUrl));

        return $html;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getLabel()
    {
        return $this->getCollection()->getName() . ' ' . \Tk\ObjectUtil::basename($this->getCurrentStatus()->getFkey());
    }

}