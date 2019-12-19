<?php
namespace Skill\Db;


use Bs\Db\Traits\TimestampTrait;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Scale extends \Tk\Db\Map\Model
{

    use TimestampTrait;
    
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
    private $_collection = null;



    /**
     * constructor.
     */
    public function __construct()
    {
        $this->_TimestampTrait();
    }

    /**
     *
     */
    public function save()
    {
        parent::save();
        try {
            self::recalculateValues($this->getCollectionId());
        } catch (\Exception $e) { \Tk\Log::error($e->getMessage()); }
    }

    /**
     * Get the institution related to this user
     */
    public function getCollection()
    {
        if (!$this->_collection) {
            $this->_collection = CollectionMap::create()->find($this->getCollectionId());
        }
        return $this->_collection;
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
            $scale->setValue($i);
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
        $list = ScaleMap::create()->findFiltered(array('collectionId' => $this->getCollectionId()), \Tk\Db\Tool::create('order_by'));
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
     * @return string
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     */
    public function setUid(string $uid): void
    {
        $this->uid = $uid;
    }

    /**
     * @return int
     */
    public function getCollectionId(): int
    {
        return $this->collectionId;
    }

    /**
     * @param int $collectionId
     */
    public function setCollectionId(int $collectionId): void
    {
        $this->collectionId = $collectionId;
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
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * @param float $value
     */
    public function setValue(float $value): void
    {
        $this->value = $value;
    }

    /**
     *
     */
    public function validate()
    {
        $errors = array();

        if ($this->getCollectionId() <= 0) {
            $errors['collectionId'] = 'Invalid Collection ID';
        }
        if (!$this->getName()) {
            $errors['name'] = 'Please enter a valid name';
        }
        
        return $errors;
    }
}