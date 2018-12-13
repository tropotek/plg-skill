<?php
namespace Skill\Util;


/**
 * This object saves an individual students results for a collection
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
class Grade implements \Serializable
{

    /**
     * @var int
     */
    protected $collectionId = 0;

    /**
     * @var int
     */
    protected $userId = 0;

    /**
     * This is the number of domains that contain items with values
     * @var int
     */
    protected $domainCount = 0;

    /**
     * This is the calculated average for all the domains with items
     * @var float
     */
    protected $avg = 0.0;

    /**
     * This is the calculated weighted average for all the domains with items
     * @var float
     */
    protected $weightedAvg = 0.0;

    /**
     * This is the calculated weighted average for all the domain averages
     * Includes the maxGrade calc...
     * @var float
     */
    protected $grade = 0.0;

    /**
     * @var array
     */
    protected $domainAvgList = array();


    /**
     * @var null|\Skill\Db\Collection
     */
    private $collection = null;

    /**
     * @var null|\Uni\Db\UserIface
     */
    private $user = null;



    /**
     * @param int $collectionId
     * @param int $userId
     */
    public function __construct($collectionId, $userId)
    {
        $this->collectionId = $collectionId;
        $this->userId = $userId;
    }


    /**
     * String representation of object
     * @link https://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize(get_object_vars($this));
    }

    /**
     * Constructs the object
     * @link https://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        $values = unserialize($serialized);
        foreach ($values as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @return null|\Skill\Db\Collection|\Tk\Db\Map\Model|\Tk\Db\ModelInterface
     */
    public function getCollection()
    {
        if (!$this->collection) {
            try {
                $this->collection = \Skill\Db\CollectionMap::create()->find($this->collectionId);
            } catch (\Exception $e) { \Tk\Log::warning($e->__toString()); }
        }
        return $this->collection;
    }

    /**
     * @return null|\Uni\Db\User|\Tk\Db\Map\Model|\Tk\Db\ModelInterface
     */
    public function getUser()
    {
        if (!$this->user) {
            try {
                $this->user = $this->getConfig()->getUserMapper()->find($this->userId);
            } catch (\Exception $e) { \Tk\Log::warning($e->__toString()); }
        }
        return $this->user;
    }

    /**
     * @return array
     */
    public function getDomainAvgList()
    {
        return $this->domainAvgList;
    }

    /**
     * @param int $domainId
     * @return array|null
     */
    public function getDomainAvg($domainId)
    {
        if (isset($this->domainAvgList[$domainId]))
            return $this->domainAvgList[$domainId];
    }

    /**
     * @param \Skill\Db\Item $item
     * @return float|null
     */
    public function getItemAvg($item)
    {
        $domainAvg = $this->getDomainAvg($item->domainId);
        if (!empty($domainAvg['itemAvgList'][$item->getId()]))
            return $domainAvg['itemAvgList'][$item->getId()];
        return 0;
    }

    /**
     * Array format:
     *   array(
     *     'domainId' => 0,
     *     'weight' => 0,
     *     'name' => '',
     *     'label' => '',
     *     'list' => array('itemId' => {item-avg})      // zero-value items included here
     *   )
     *
     * NOTE: FOR DVM this should be calculated including any 0 value items.
     *
     * @param array $domainAvgList
     * @return Grade
     */
    public function setDomainAvgList($domainAvgList)
    {
        ksort($domainAvgList, \SORT_NATURAL);
        $this->domainAvgList = $domainAvgList;
        return $this;
    }



    /**
     * @param float $grade
     * @return Grade
     * @deprecated
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
        return $this;
    }

    /**
     * @return float
     * @deprecated
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * @return float
     * @deprecated
     */
    public function getAverage()
    {
        return $this->getGrade() / ($this->getCollection()->maxGrade / $this->getCollection()->getScaleCount());
    }

    /**
     * @return float
     * @deprecated
     */
    public function getPercent()
    {
        return $this->grade * (100 / $this->getCollection()->maxGrade);
    }


    /**
     *
     *
     * @return float
     * @todo
     */
    public function getGradeResult()
    {
        $grade = 0;
        if ($this->getDomainCount()) {
            $grade = ($this->weightedAvg / $this->getDomainCount()) * $this->getCollection()->maxGrade;
        } else {
            $grade = $this->weightedAvg * $this->getCollection()->maxGrade;
        }
        //return $grade;
        return $this->weightedAvg;
    }

    /**
     *
     *
     * @return float
     * @todo
     */
    public function getGradePercent()
    {
        return $this->getGradeResult() * (100 / $this->getCollection()->maxGrade);
    }

    /**
     * @return float
     */
    public function getAvg()
    {
        return $this->avg;
    }

    /**
     * @param float $avg
     * @return Grade
     */
    public function setAvg($avg)
    {
        $this->avg = $avg;
        return $this;
    }

    /**
     * @return float
     */
    public function getWeightedAvg()
    {
        return $this->weightedAvg;
    }

    /**
     * @param float $weightedAvg
     * @return Grade
     */
    public function setWeightedAvg($weightedAvg)
    {
        $this->weightedAvg = $weightedAvg;
        return $this;
    }

    /**
     * @return int
     */
    public function getDomainCount()
    {
        return $this->domainCount;
    }

    /**
     * @param int $domainCount
     * @return Grade
     */
    public function setDomainCount($domainCount)
    {
        $this->domainCount = $domainCount;
        return $this;
    }

    /**
     * @return \Tk\Config|\Uni\Config
     */
    public function getConfig()
    {
        return \Uni\Config::getInstance();
    }
}