<?php
namespace Skill\Db;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Collection extends \Tk\Db\Map\Model
{
    const ROLE_STAFF    = 'staff';
    const ROLE_STUDENT  = 'student';
    const ROLE_COMPANY  = 'company';

    const FIELD_ENABLE_RESULTS = 'skillResults';


    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $profileId = 0;

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
     * enable/disable user submission/editing
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
    public $includeZero = false;

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
    private $scaleLength = 0;


    /**
     * @var \App\Db\Profile
     */
    private $profile = null;




    /**
     * constructor.
     */
    public function __construct()
    {
        $this->modified = \Tk\Date::create();
        $this->created = \Tk\Date::create();
    }

    /**
     *
     */
    public function save()
    {
        parent::save();
    }

    /**
     * @return \App\Db\Profile|null|\Tk\Db\Map\Model|\Tk\Db\ModelInterface
     * @throws \Tk\Db\Exception
     */
    public function getProfile()
    {
        if (!$this->profile) {
            $this->profile = \App\Db\ProfileMap::create()->find($this->profileId);
        }
        return $this->profile;
    }

    /**
     * Use this to test if the public user can submit an entry
     *
     * @param \App\Db\Placement $placement (optional)
     * @return bool
     */
    public function isAvailable($placement = null)
    {
        $b = true;
        $b &= $this->active;
        if ($placement) {
            $b &= in_array($placement->status, $this->available);
            $b &= CollectionMap::create()->hasPlacementType($this->getId(), $placement->placementTypeId);
        }
        return $b;
    }

    /**
     * @param \App\Db\Subject|int $subjectId
     * @return bool
     */
    public function isAvailableToSubject($subjectId)
    {
        if ($subjectId instanceof \App\Db\Subject) $subjectId = $subjectId->getId();
        $b = \Skill\Db\CollectionMap::create()->hasSubject($subjectId, $this->getId());
        return $b;
    }

    /**
     * Get the total number of scale ticks/records for this collection
     *
     * @return int
     * @throws \Tk\Db\Exception
     */
    public function getScaleLength()
    {
        if (!$this->scaleLength)
            $this->scaleLength = ScaleMap::create()->findFiltered(array('collectionId' => $this->getVolatileId()))->count();
        return $this->scaleLength;
    }

    /**
     *
     */
    public function validate()
    {
        $errors = array();

        if ((int)$this->profileId <= 0) {
            $errors['profileId'] = 'Invalid profile ID';
        }
        if (!$this->name) {
            $errors['name'] = 'Please enter a valid name for this collection';
        }
        if (!$this->role) {
            $errors['role'] = 'Please enter a valid role for this collection';
        }

        if($this->requirePlacement) {
            if (!$this->available) {
                $errors['available'] = 'Please select at least one valid status for this collection to be available for.';
            }
            // Also requires a placement type
        }
        
        return $errors;
    }
}