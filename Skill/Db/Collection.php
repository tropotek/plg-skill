<?php
namespace Skill\Db;


use Bs\Db\Traits\TimestampTrait;
use Uni\Db\Traits\CourseTrait;
use Uni\Db\Traits\SubjectTrait;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Collection extends \Tk\Db\Map\Model
{
    use CourseTrait;
    use SubjectTrait;
    use TimestampTrait;

    const TYPE_STAFF    = 'staff';
    const TYPE_STUDENT  = 'student';
    const TYPE_COMPANY  = 'company';

    const FIELD_ENABLE_RESULTS = 'skillResults';


    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var string
     */
    public $uid = '';

    /**
     * @var int
     */
    public $courseId = 0;

    /**
     * @var int
     */
    public $subjectId = 0;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $role = '';

    /**
     * @var string
     */
    public $icon = '';

    /**
     * A HEX color for this collection
     * @var string
     */
    public $color = '#ffffcc';

    /**
     *  A list of placement status that the collection is available for submission/editing by user
     * @var array
     */
    public $available = array();

    /**
     * enable/disable public user submission/editing/viewing
     * @var boolean
     */
    public $publish = true;

    /**
     * enable/disable collection from all users of the ems
     * @var boolean
     */
    public $active = true;

    /**
     * @var string
     */
    public $confirm = '';

    /**
     * Is this collection gradable
     * @var boolean
     */
    public $gradable = false;

    /**
     * Is this collections Entries linked to a placement
     * @var boolean
     */
    public $requirePlacement = false;

    /**
     * @var float
     */
    public $maxGrade = 10.0;

    /**
     * Enable students to view their final results of all compiled entry grades
     * This is now a data variable in the subject_data table as this is subject
     * @var boolean
     */
    //public $viewGrade = false;

    /**
     * Should the zero values be included in the weighted average calculation
     * @var boolean
     */
    //public $includeZero = false;

    /**
     * @var string
     */
    public $instructions = '';

    /**
     * staff only notes
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
     * @var int
     */
    private $scaleCount = 0;

    /**
     * @var int
     */
    private $domainCount = 0;


    /**
     * constructor.
     */
    public function __construct()
    {
        $this->_TimestampTrait();
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        parent::save();
        if ($this->getSubject()) {
            $calc = new \Skill\Util\GradeCalculator($this);
            $calc->deleteSubjectGradeCache();
        }
    }

    /**
     * Use this to test if the public user or student can submit/view an entry
     *
     * @param \App\Db\Placement $placement (optional)
     * @return bool
     */
    public function isAvailable($placement = null)
    {
        if (!$this->active) return false;
        $b = true;
        if ($placement) {
            $b &= in_array($placement->status, $this->available);
            $b &= CollectionMap::create()->hasPlacementType($this->getId(), $placement->placementTypeId);
        }
        return $b;
    }

    /**
     * Get the total number of domains for this collection
     *
     * @return int
     * @deprecated the actual values should be used not this, as it does not reflect when it changes.
     */
    public function getDomainCount()
    {
        if (!$this->domainCount) {
            try {
                $this->domainCount = DomainMap::create()->findFiltered(array('collectionId' => $this->getVolatileId(), 'active' => true))->count();
            } catch (\Exception $e) { \Tk\Log::warning($e->__toString()); }
        }
        return $this->domainCount;
    }

    /**
     * Get the total number of scale ticks/records for this collection
     *
     * @return int
     */
    public function getScaleCount()
    {
        if (!$this->scaleCount) {
            try {
                $this->scaleCount = ScaleMap::create()->findFiltered(array('collectionId' => $this->getVolatileId()))->count()-1;
            } catch (\Exception $e) {
                \Tk\Log::warning($e->__toString());
            }
        }
        return $this->scaleCount;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Collection
     */
    public function setName(string $name): Collection
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @param string $role
     * @return Collection
     */
    public function setRole(string $role): Collection
    {
        $this->role = $role;
        return $this;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     * @return Collection
     */
    public function setIcon(string $icon): Collection
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @param string $color
     * @return Collection
     */
    public function setColor(string $color): Collection
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return array
     */
    public function getAvailable(): array
    {
        return $this->available;
    }

    /**
     * @param array $available
     * @return Collection
     */
    public function setAvailable(array $available): Collection
    {
        $this->available = $available;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPublish(): bool
    {
        return $this->publish;
    }

    /**
     * @param bool $publish
     * @return Collection
     */
    public function setPublish(bool $publish): Collection
    {
        $this->publish = $publish;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return Collection
     */
    public function setActive(bool $active): Collection
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return string
     */
    public function getConfirm(): string
    {
        return $this->confirm;
    }

    /**
     * @param string $confirm
     * @return Collection
     */
    public function setConfirm(string $confirm): Collection
    {
        $this->confirm = $confirm;
        return $this;
    }

    /**
     * @return bool
     */
    public function isGradable(): bool
    {
        return $this->gradable;
    }

    /**
     * @param bool $gradable
     * @return Collection
     */
    public function setGradable(bool $gradable): Collection
    {
        $this->gradable = $gradable;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRequirePlacement(): bool
    {
        return $this->requirePlacement;
    }

    /**
     * @param bool $requirePlacement
     * @return Collection
     */
    public function setRequirePlacement(bool $requirePlacement): Collection
    {
        $this->requirePlacement = $requirePlacement;
        return $this;
    }

    /**
     * @return float
     */
    public function getMaxGrade(): float
    {
        return $this->maxGrade;
    }

    /**
     * @param float $maxGrade
     * @return Collection
     */
    public function setMaxGrade(float $maxGrade): Collection
    {
        $this->maxGrade = $maxGrade;
        return $this;
    }

    /**
     * @return string
     */
    public function getInstructions(): string
    {
        return $this->instructions;
    }

    /**
     * @param string $instructions
     * @return Collection
     */
    public function setInstructions(string $instructions): Collection
    {
        $this->instructions = $instructions;
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
     * @return Collection
     */
    public function setNotes(string $notes): Collection
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     *
     */
    public function validate()
    {
        $errors = array();
        //$errors = $this->validateCourseId($errors);
        $errors = $this->validateSubjectId($errors);

        if (!$this->getName()) {
            $errors['name'] = 'Please enter a valid name for this collection';
        }
        if (!$this->getRole()) {
            $errors['role'] = 'Please enter a valid role for this collection';
        }

        // Available for status types
        if ($this->isRequirePlacement() && !$this->getAvailable()) {
            $errors['available'] = 'Please select at least one valid status for this collection to be available for.';
        }

        
        return $errors;
    }
}