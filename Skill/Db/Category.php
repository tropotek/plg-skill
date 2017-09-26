<?php
namespace Skill\Db;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Category extends \Tk\Db\Map\Model
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
     * @var int
     */
    public $parentId = 0;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var boolean
     */
    public $publish = true;

    /**
     * @var int
     */
    public $orderBy = 0;

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
     * @var Category
     */
    private $parent = null;




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
     * @return null|Category
     */
    public function getParent()
    {
        if (!$this->parent && $this->parentId) {
            $this->parent = CategoryMap::create()->find($this->parentId);
        }
        return $this->parent;
    }
    
    /**
     *
     */
    public function validate()
    {
        $errors = array();

        if ((int)$this->profileId <= 0) {
            $errors['institutionId'] = 'Invalid institution ID';
        }

        if ((int)$this->parentId <= 0) {
            $errors['parentId'] = 'Invalid parent category ID';
        }
        if (!$this->name) {
            $errors['name'] = 'Please enter a valid name';
        }
        
        return $errors;
    }
}