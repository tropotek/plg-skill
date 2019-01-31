<?php
namespace Skill\Db;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Scale extends \Tk\Db\Map\Model
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
    public $description = '';

    /**
     * @var float
     */
    public $value = 0;

    /**
     * @var int
     * @deprecated Order by `value` instead
     */
    //public $orderBy = 0;

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
        try {
            self::recalculateValues($this->collectionId);
        } catch (\Exception $e) { \Tk\Log::error($e->getMessage()); }
    }

    /**
     * Get the institution related to this user
     */
    public function getCollection()
    {
        if (!$this->collection) {
            $this->collection = CollectionMap::create()->find($this->collectionId);
        }
        return $this->collection;
    }

    /**
     * recalculate the values for a collection of scales
     *
     * @param $collectionId
     * @throws \Exception
     */
    public static function recalculateValues($collectionId) {
        $list = ScaleMap::create()->findFiltered(array('collectionId' => $collectionId));
        /** @var Scale $scale */
        foreach ($list as $i => $scale) {
            $scale->value = $i;
//            if ($i == 0) {
//                $scale->value = 0;
//            } else {
//                $scale->value = round((100/($list->count()-1))*$i, 2);
//            }
            $scale->update();
        }
    }

    /**
     * Get the number value of this scale item
     * Generally this is a percentage of the scale in the list 0% - 100%
     *
     * @return float|int
     * @throws \Exception
     * @deprecated Not really used
     */
    public function getRatioValue()
    {
        $list = ScaleMap::create()->findFiltered(array('collectionId' => $this->collectionId), \Tk\Db\Tool::create('order_by'));
        $cnt = count($list)-1;
        $pos = 0;
        $val = 0;
        /** @var Scale $s */
        foreach ($list as $i => $s) {
            if ($s->getId() == $this->getId()) {
                $pos = $i;
                break;
            }
        }
        if ($cnt > 0 && $pos > 0) {
            $val = round((100/$cnt)*$pos, 2);
        }
        return $val;
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