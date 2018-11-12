<?php

namespace Skill\Util;

/**
 * @author Tropotek <info@tropotek.com>
 * @created: 6/08/18
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Tropotek
 */
class GradeCalculator
{

    const CACHE_TIMEOUT = 60*60*2;

    /**
     * @var null|\Tk\Cache\Cache
     */
    protected $cache = null;

    /**
     * @var null|\Skill\Db\Collection
     */
    protected $collection = null;

    /**
     * @var bool
     */
    protected $cacheEnabled = true;


    /**
     * @param \Skill\Db\Collection $collection
     * @param null|string $cachePath
     * @throws \Exception
     */
    public function __construct($collection, $cachePath = null)
    {
        $this->collection = $collection;

        if (!$cachePath) {
            $cachePath = $this->getConfig()->getDataPath() . '/skillResultsCache/' . $collection->getSubject()->getInstitutionId();
        }
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0777, true);
        }
        $this->cache = \Tk\Cache\Cache::create(\Tk\Cache\Adapter\Filesystem::create($cachePath));
    }


    /**
     * @param \Uni\Db\UserIface $user
     * @return mixed|Grade
     * @throws \Exception
     */
    public function getStudentGrade($user)
    {
        $cacheId = sprintf('sg-%s-%s', $this->collection->getId(), $user->getId());
        //$cacheId = cacheId('md5', sprintf('sg-%s-%s', $this->collection->getId(), $user->getId() ));

        $grade = $this->cache->fetch($cacheId);
        if ($grade) \Tk\Log::info('Student Grade Cache Exists: ' . $user->getName());
        if (!$grade || !$this->isCacheEnabled()) {
            \Tk\Log::info(' - Student Grade Calculating');

            $grade = new Grade($this->collection->getId(), $user->getId());
            $iAvgList = $grade->getItemAvg();          // Item Average List
            $dAvgList = $grade->getDomainAvg();        // Domain Average List

            $itemList = \Skill\Db\ItemMap::create()->findFiltered( array('collectionId' => $this->collection->getId()) );
            foreach ($itemList as $item) {
                $domain = $item->getDomain();
                if (!isset($dAvgList[$domain->getId()])) {
                    $dAvgList[$domain->getId()] = array(
                        'domainId' => $domain->getId(),
                        'maxGrade' => $this->collection->maxGrade,
                        'domainCount' => $this->collection->getDomainCount(),
                        'scaleCount' => $this->collection->getScaleCount(),
                        'weight' => $domain->weight,
                        'name' => $domain->name,
                        'label' => $domain->label,
                        'avg' => 0,
                        'itemList' => array()
                    );
                }
                $avg = \Skill\Db\ItemMap::create()->findAverage($user->getId(), $item->getId());
                if ($avg > 0) {
                    $iAvgList[$item->getId()] = $avg;
                }
                $dAvgList[$domain->getId()]['itemList'][$item->getId()] = $avg;
            }

            $gradeTotal = 0;
            foreach ($dAvgList as $did => $dAvg) {
                $dAvgList[$did]['avg'] = self::average($dAvg['itemList']);
                $dAvgList[$did]['grade'] = $dAvgList[$did]['avg'] * ($dAvg['maxGrade']/$dAvg['scaleCount']);
                $gradeTotal += $dAvgList[$did]['avg'] * $dAvg['weight'];
            }
            $grade->setGrade($gradeTotal/$this->collection->getDomainCount());
            $grade->setDomainAvg($dAvgList);
            $grade->setItemAvg($iAvgList);

            // Storing the data in the cache
            $this->cache->store($cacheId, $grade, self::CACHE_TIMEOUT);
        }
        return $grade;
    }


    /**
     * @return mixed
     * @throws \Exception
     */
    public function getSubjectGradeList()
    {
        $cacheId = hash('md5', 'sgl-%s', $this->collection->getId());
        //$cacheId = cacheId('md5', sprintf('sgl-%s', $this->collection->getId()));

        $data = $this->cache->fetch($cacheId);
        if ($data) \Tk\Log::info('Subject Grade Cache Exists: ' . $this->collection->getSubject()->getName());
        if (!$data || !$this->isCacheEnabled()) {
            \Tk\Log::info(' - Subject Grade Calculating');

            $studentList = \App\Db\UserMap::create()->findFiltered(array('subjectId' => $this->collection->subjectId));
            foreach ($studentList as $student) {

            }

            // Storing the data in the cache
            $this->cache->store($cacheId, $data, self::CACHE_TIMEOUT);
        }
        return $data;
    }



    /**
     * @return bool
     */
    public function isCacheEnabled()
    {
        return $this->cacheEnabled;
    }

    /**
     * @param bool $cacheEnabled
     */
    public function setCacheEnabled($cacheEnabled)
    {
        $this->cacheEnabled = $cacheEnabled;
    }

    /**
     * @return \Tk\Config|\Uni\Config
     */
    public function getConfig()
    {
        return \Uni\Config::getInstance();
    }





    /**
     * @param float[]|int[]$arr
     * @return float|int
     */
    public static function median($arr)
    {
        sort($arr);
        $count = count($arr); //total numbers in array
        if (!$count) return 0;
        $midVal = (int)floor(($count-1)/2); // find the middle value, or the lowest middle value
        if($count % 2) { // odd number, middle is the median
            $median = $arr[$midVal];
        } else { // even number, calculate avg of 2 medians
            $low = $arr[$midVal];
            $high = $arr[$midVal+1];
            $median = (($low+$high)/2);
        }
        return $median;
    }

    /**
     * @param float[]|int[]$arr
     * @return float|int
     */
    public static function average($arr)
    {
        $count = count($arr); //total numbers in array
        if (!$count) return 0;
        $total = 0;
        foreach ($arr as $value) {
            $total = $total + $value; // total value of array numbers
        }
        $average = ($total/$count); // get average value
        return $average;
    }



    /**
     * @param \Skill\Db\Collection $collection
     * @param \App\Db\Subject $subject
     * @param bool $force
     * @return object|null
     * @throws \Exception
     */
    public static function findSubjectAverageGrades($collection, $subject, $force = false)
    {
        // Check cache
        $start = microtime(true);
        $config = \Uni\Config::getInstance();

        $cachePath = $config->getDataPath() . '/skillResultsCache/' . $collection->getSubject()->getInstitutionId();
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0777, true);
        }
        $cache = \Tk\Cache\Cache::create(\Tk\Cache\Adapter\Filesystem::create($cachePath));
        $hash = hash('md5', sprintf('%s-%s', $collection->getId(), $subject->getId()));

        $res = $cache->fetch($hash);

        if ($res)
            \Tk\Log::info('Cache Exists: ' . $collection->name);

        if (!$res || $force) {
            \Tk\Log::warning('Caching Skills Results: ' . $collection->name);

            $students = $config->getUserMapper()->findFiltered(array('subjectId' => $subject->getId(), 'type' => \Uni\Db\ROLE::TYPE_STUDENT));
            $subjectCollectionResults = array();
            $subjectEntries = \Skill\Db\EntryMap::create()->findFiltered(array(
                'collectionId' => $collection->getId(),
                'subjectId' => $subject->getId(),
                'status' => 'approved'
            ));
            if ($subjectEntries->count()) {
                foreach ($students as $student) {
                    $entries = \Skill\Db\EntryMap::create()->findFiltered(array(
                        'collectionId' => $collection->getId(),
                        'subjectId' => $subject->getId(),
                        'status' => 'approved',
                        'userId' => $student->getId()
                    ));
                    if (!$entries->count()) {
                        continue;
                    }

                    $studentResult = \Skill\Db\ReportingMap::create()->findStudentResult($collection->getId(), $subject->getId(), $student->getId(), true);
                    if ($studentResult > 0)
                        $subjectCollectionResults[$student->getId()] = $studentResult;
                }
            }

            $res = (object)array(
                'processingTime' => round(microtime(true) - $start, 4),
                'min' => (count($subjectCollectionResults) > 0) ? min($subjectCollectionResults) : 0,
                'median' => self::median($subjectCollectionResults),
                'max' => (count($subjectCollectionResults) > 0) ? max($subjectCollectionResults) : 0,
                'avg' => self::average($subjectCollectionResults),
                'count' => count($subjectCollectionResults),
                'studentResults' => $subjectCollectionResults,
                'subjectEntryCount' => $subjectEntries->count()
            );

            // Storing the data in the cache for 10 minutes
            $cache->store($hash, $res, self::CACHE_TIMEOUT);
        }

        return $res;
    }



}