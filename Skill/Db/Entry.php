<?php
namespace Skill\Db;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Entry extends \Tk\Db\Map\Model
{

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_NOT_APPROVED = 'not approved';

    const TYPE_SELF_ASSESSMENT = 'Self Assessment';
    const TYPE_BASIC = 'Basic';
    const TYPE_CASE_WORKUP = 'Case Work-up';
    const TYPE_CRITICAL_MOMENT = 'Critical Moment';
    const TYPE_PLACEMENT_PLAN = 'Placement Plan';
    const TYPE_PLACEMENT_REVIEW = 'Placement Review';

    
    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $profileId = 0;

    /**
     * @var int
     */
    public $userId = 0;

    /**
     * @var string
     */
    public $title = '';

    /**
     * @var string
     */
    public $type = self::TYPE_BASIC;

    /**
     * @var string
     */
    public $status = self::STATUS_PENDING;




    /**
     * @var string
     */
    public $location = '';

    /**
     * @var string
     */
    public $praiseComment = '';

    /**
     * @var string
     */
    public $highlightComment = '';

    /**
     * @var string
     */
    public $improveComment = '';

    /**
     * @var string
     */
    public $differentComment = '';


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
     * @var \App\Db\Profile
     */
    private $profile = null;

    /**
     * @var \App\Db\User
     */
    private $user = null;



    /**
     * Course constructor.
     */
    public function __construct()
    {
        $this->modified = \Tk\Date::create();
        $this->created = \Tk\Date::create();
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
     * @return \App\Db\User|null|\Tk\Db\Map\Model|\Tk\Db\ModelInterface
     */
    public function getUser()
    {
        if (!$this->user) {
            $this->user = \App\Db\UserMap::create()->find($this->userId);
        }
        return $this->user;
    }

    /**
     *
     */
    public function validate()
    {
        $errors = array();

        if ((int)$this->profileId <= 0) {
            $errors['profileId'] = 'Invalid Profile ID';
        }
        if ((int)$this->userId <= 0) {
            $errors['userId'] = 'Invalid user ID';
        }
        if (!$this->title) {
            $errors['title'] = 'Please enter a valid title';
        }
        if (!$this->type) {
            $errors['type'] = 'Please enter a valid type';
        }
        if (!$this->status) {
            $errors['status'] = 'Please enter a valid status';
        }
        
        return $errors;
    }
}