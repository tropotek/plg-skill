<?php
namespace Skill\Db;


/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Category extends \Tk\Db\Map\Model
{
    
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
    public $collectionId = 0;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $label = '';

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
     * @return Collection
     */
    public function getCollection()
    {
        if (!$this->collection) {
            try {
                $this->collection = CollectionMap::create()->find($this->collectionId);
            } catch(\Exception $e) { \Tk\Log::warning($e->__toString()); }
        }
        return $this->collection;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        $l = $this->name;
        if ($this->label) {
            $l .= ' - [' . $this->label . ']';
        }
        return $l;
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
        if (!$this->name) {
            $errors['name'] = 'Please enter a valid name';
        }
        
        return $errors;
    }
}