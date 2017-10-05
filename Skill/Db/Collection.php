<?php
namespace Skill\Db;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Collection extends \Tk\Db\Map\Model
{
    
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
     *  A list of placement statuses that the collection is available for submission/editing by user
     * @var string
     */
    public $available = '';

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
     * Enable students to view their final results of all compiled entry grades
     * @var boolean
     */
    public $viewGrade = false;

    /**
     * @var string
     */
    public $instructions = '';

    /**
     * staff only notes
     *
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
     * @var \App\Db\Profile
     */
    private $profile = null;




    /**
     * Course constructor.
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
     */
    public function getProfile()
    {
        if (!$this->profile) {
            $this->profile = \App\Db\ProfileMap::create()->find($this->profileId);
        }
        return $this->profile;
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
        if (!$this->available) {
            $errors['available'] = 'Please select at least one valid status for this collection to be available for.';
        }
        
        return $errors;
    }
}