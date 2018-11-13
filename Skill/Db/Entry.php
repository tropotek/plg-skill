<?php
namespace Skill\Db;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Entry extends \Tk\Db\Map\Model implements \Tk\ValidInterface
{
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
    private $collection = null;

    /**
     * @var \App\Db\Subject
     */
    private $subject = null;

    /**
     * @var \App\Db\User
     */
    private $user = null;

    /**
     * @var \App\Db\Placement
     */
    private $placement = null;



    /**
     * constructor.
     */
    public function __construct()
    {
        $this->modified = \Tk\Date::create();
        $this->created = \Tk\Date::create();
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

    public function delete()
    {
        \App\Db\StatusMap::create()->deleteByModel(get_class($this), $this->getId());
        return parent::delete();
    }


    /**
     * @return \Skill\Db\Collection|null|\Tk\Db\Map\Model|\Tk\Db\ModelInterface
     * @throws \Exception
     */
    public function getCollection()
    {
        if (!$this->collection) {
            $this->collection = CollectionMap::create()->find($this->collectionId);
        }
        return $this->collection;
    }

    /**
     * @return \App\Db\Subject|null|\Tk\Db\Map\Model|\Tk\Db\ModelInterface
     * @throws \Exception
     */
    public function getSubject()
    {
        if (!$this->subject) {
            $this->subject = \App\Db\SubjectMap::create()->find($this->subjectId);
        }
        return $this->subject;
    }

    /**
     * @return \App\Db\User|null|\Tk\Db\Map\Model|\Tk\Db\ModelInterface
     * @throws \Exception
     */
    public function getUser()
    {
        if (!$this->user) {
            $this->user = \App\Db\UserMap::create()->find($this->userId);
        }
        return $this->user;
    }

    /**
     * @return \App\Db\Placement|null|\Tk\Db\Map\Model|\Tk\Db\ModelInterface
     * @throws \Exception
     */
    public function getPlacement()
    {
        if (!$this->placement) {
            $this->placement = \App\Db\PlacementMap::create()->find($this->placementId);
        }
        return $this->placement;
    }

    /**
     * return the status list for a select field
     * @return array
     * @throws \ReflectionException
     */
    public static function getStatusList()
    {
        return \Tk\Form\Field\Select::arrayToSelectList(\Tk\ObjectUtil::getClassConstants(__CLASS__, 'STATUS'));
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

}