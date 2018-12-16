<?php
namespace Skill\Db;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
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
    public $uid = 0;

    /**
     * @var int
     */
    public $collectionId = 0;

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
    public $question = '';

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
     * @var Collection
     */
    private $collection = null;

    /**
     * @var Category
     */
    private $category = null;

    /**
     * @var Domain
     */
    private $domain = null;



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
     * @return null|Collection|\Tk\Db\Map\Model|\Tk\Db\ModelInterface
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
     * @return Category|null|\Tk\Db\Map\Model|\Tk\Db\ModelInterface
     * @throws \Exception
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
     * @throws \Exception
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

        if ((int)$this->collectionId <= 0) {
            $errors['collectionId'] = 'Invalid Collection ID';
        }
//        if ((int)$this->categoryId <= 0) {
//            $errors['categoryId'] = 'Invalid Category ID';
//        }
//        if ((int)$this->domainId <= 0) {
//            $errors['domainId'] = 'Invalid Domain ID';
//        }
        if (!$this->question) {
            $errors['question'] = 'Please enter a valid name';
        }
        
        return $errors;
    }
}