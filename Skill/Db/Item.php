<?php
namespace Skill\Db;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Item extends \Tk\Db\Map\Model
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
    public $categoryId = 0;

    /**
     * @var int
     */
    public $domainId = 0;

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
    private $category = null;

    /**
     * @var Domain
     */
    private $domain = null;



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
     * @return Category|null|\Tk\Db\Map\Model|\Tk\Db\ModelInterface
     */
    public function getCategory()
    {
        if (!$this->category) {
            $this->category = CategoryMap::create()->find($this->categoryId);
        }
        return $this->category;
    }

    /**
     * @return null|Domain
     */
    public function getDomain()
    {
        if (!$this->domain) {
            $this->domain = DomainMap::create()->find($this->domainId);
        }
        return $this->domain;
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

        if ((int)$this->categoryId <= 0) {
            $errors['categoryId'] = 'Invalid category ID';
        }
        if (!$this->name) {
            $errors['name'] = 'Please enter a valid name';
        }
        
        return $errors;
    }
}