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
     * @var string
     */
    protected $cachePath = '';

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
        $this->cachePath = $cachePath;

    }

    /**
     * @return \Tk\Cache\Cache
     */
    public function getCache()
    {
        if (!$this->cache) {
            if (!is_dir($this->cachePath)) {
                mkdir($this->cachePath, 0777, true);
            }
            $this->cache = \Tk\Cache\Cache::create(\Tk\Cache\Adapter\Filesystem::create($this->cachePath));
        }
        return $this->cache;
    }

    /**
     * @param \Uni\Db\UserIface $user
     * @return string
     */
    public function getStudentGradeCacheId($user)
    {
        return sprintf('sg-%s-%s', $this->collection->getId(), $user->getId());
    }

    /**
     * @return string
     */
    public function getSubjectGradesCacheId()
    {
        return sprintf('sgl-%s', $this->collection->getId());
    }

    /**
     * Delete a single user cache
     * @param \Uni\Db\UserIface $user
     */
    public function deleteStudentGradeCache($user)
    {
        if (is_file($this->cachePath . '/' . $this->getStudentGradeCacheId($user))) {
            unlink($this->cachePath . '/' . $this->getStudentGradeCacheId($user));
        }
        $this->deleteSubjectGradeCache();
    }

    /**
     *  Deletes a collection cache
     */
    public function deleteSubjectGradeCache()
    {
        if (is_file($this->cachePath . '/' . $this->getSubjectGradesCacheId())) {
            unlink($this->cachePath . '/' . $this->getSubjectGradesCacheId());
        }
        if (is_file($this->cachePath . '/' . $this->getSubjectGradesCacheId() . '_opt')) {
            unlink($this->cachePath . '/' . $this->getSubjectGradesCacheId() . '_opt');
        }
    }




    /**
     * @param \Uni\Db\UserIface $user
     * @return mixed|Grade
     * @throws \Exception
     */
    public function getStudentGrade($user)
    {
        $start = microtime(true);
        $cacheId = $this->getStudentGradeCacheId($user);
        $grade = $this->getCache()->fetch($cacheId);

        //if ($grade) \Tk\Log::info('Student Grade Cache Exists: ' . $user->getName());
        if (!$grade || !$this->isCacheEnabled()) {
            //\Tk\Log::info(' - Student Grade Calculating');

            $grade = new Grade($this->collection->getId(), $user->getId());
            $domainAvgList = $grade->getDomainAvgList();        // Domain Average List

            $itemList = \Skill\Db\ItemMap::create()->findFiltered(array('collectionId' => $this->collection->getId()));
            foreach ($itemList as $item) {
                $domain = $item->getDomain();
                if (!$domain) continue;
                if (!isset($domainAvgList[$domain->getId()])) {
                    $domainAvgList[$domain->getId()] = array(
                        'domainId' => $domain->getId(),
                        'maxGrade' => $this->collection->maxGrade,
                        'domainCount' => $this->collection->getDomainCount(),
                        'scaleCount' => $this->collection->getScaleCount(),
                        'weight' => $domain->weight,
                        'name' => $domain->name,
                        'label' => $domain->label,
                        'avg' => 0,
                        'weighted_avg' => 0,
                        'grade' => 0,
                        'itemAvgList' => array()
                    );
                }
                $avg = \Skill\Db\ItemMap::create()->findAverage($user->getId(), $item->getId());
                $domainAvgList[$domain->getId()]['itemAvgList'][$item->getId()] = $avg;
            }

            $gradeTotal = 0;
            foreach ($domainAvgList as $domainId => $domainAverage) {
                $domainAvgList[$domainId]['avg'] = \Tk\Math::average($domainAverage['itemAvgList']);
                $domainAvgList[$domainId]['grade'] = $domainAvgList[$domainId]['avg'] * ($domainAverage['maxGrade'] / $domainAverage['scaleCount']);
                $domainAvgList[$domainId]['weighted_avg'] = $domainAvgList[$domainId]['avg'] * $domainAverage['weight'];
                $gradeTotal += $domainAvgList[$domainId]['weighted_avg'];
            }
            if ($this->collection->getDomainCount()) {
                $grade->setGrade(($gradeTotal / $this->collection->getDomainCount()) * $this->collection->maxGrade);
            } else {
                $grade->setGrade($gradeTotal * $this->collection->maxGrade);
            }

            $grade->setDomainAvgList($domainAvgList);

            // Storing the data in the cache
            $this->getCache()->store($cacheId, $grade, self::CACHE_TIMEOUT);
        }
        return $grade;
    }


    /**
     * @return mixed
     * @throws \Exception
     */
    public function getSubjectGrades()
    {
        $start = microtime(true);
        $cacheId = $this->getSubjectGradesCacheId();
        $data = $this->getCache()->fetch($cacheId);
        //if ($data) \Tk\Log::info('Subject Grade Cache Exists: ' . $this->collection->getSubject()->getName());
        if (!$data || !$this->isCacheEnabled()) {
            //\Tk\Log::info(' - Subject Grade Calculating');

            $gradeList = array();
            $gradeValueList = array();
            $studentList = \App\Db\UserMap::create()->findFiltered(array('subjectId' => $this->collection->subjectId));
            foreach ($studentList as $student) {
                $result = $this->getStudentGrade($student);
                $gradeList[$student->getId()] = $result;
                if ($result->getGrade() > 0)
                    $gradeValueList[$student->getId()] = $result->getGrade();

            }

            $subjectEntries = \Skill\Db\EntryMap::create()->findFiltered(array(
                'collectionId' => $this->collection->getId(),
                'status' => 'approved'
            ));
            $data = (object)array(
                'processingTime' => round(microtime(true) - $start, 4),
                'min' => (count($gradeValueList) > 0) ? min($gradeValueList) : 0,
                'median' => \Tk\Math::median($gradeValueList),
                'max' => (count($gradeValueList) > 0) ? max($gradeValueList) : 0,
                'avg' => \Tk\Math::average($gradeValueList),
                'count' => count($gradeValueList),
                'gradeValueList' => $gradeValueList,
                'gradeList' => $gradeList,
                'entryCount' => $subjectEntries->count()
            );
//vd($data);
            // Storing the data in the cache
            $this->getCache()->store($cacheId, $data, self::CACHE_TIMEOUT);
        }
        return $data;
    }



    /**
     * This is faster for the StudentResults report page
     *
     * @return object|null
     * @throws \Exception
     * @deprecated
     */
    public function findSubjectAverageGrades()
    {
        // Check cache
        $start = microtime(true);
        $cacheId = $this->getSubjectGradesCacheId() . '_opt';
        $data = $this->getCache()->fetch($cacheId);
        //if ($data) \Tk\Log::info('Subject Grade Cache Exists: ' . $this->collection->getSubject()->getName());
        if (!$data || !$this->isCacheEnabled()) {
            //\Tk\Log::info(' - Subject Grade Calculating');

            $students = $this->getConfig()->getUserMapper()->findFiltered(array('subjectId' => $this->collection->subjectId, 'type' => \Uni\Db\ROLE::TYPE_STUDENT));
            $gradeValueList = array();
            $subjectEntries = \Skill\Db\EntryMap::create()->findFiltered(array(
                'collectionId' => $this->collection->getId(),
                'status' => 'approved'
            ));
            if ($subjectEntries->count()) {
                foreach ($students as $student) {
                    $entries = \Skill\Db\EntryMap::create()->findFiltered(array(
                        'collectionId' => $this->collection->getId(),
                        'status' => 'approved',
                        'userId' => $student->getId()
                    ));
                    if (!$entries->count()) {
                        continue;
                    }

                    $studentResult = \Skill\Db\ReportingMap::create()->findStudentResult($this->collection->getId(), $this->collection->subjectId,
                        $student->getId(), true);

                    if ($studentResult > 0)
                        $gradeValueList[$student->getId()] = $studentResult;
                }
            }

            $data = (object)array(
                'processingTime' => round(microtime(true) - $start, 4),
                'min' => (count($gradeValueList) > 0) ? min($gradeValueList) : 0,
                'median' => \Tk\Math::median($gradeValueList),
                'max' => (count($gradeValueList) > 0) ? max($gradeValueList) : 0,
                'avg' => \Tk\Math::average($gradeValueList),
                'count' => count($gradeValueList),
                'gradeValueList' => $gradeValueList,
                'entryCount' => $subjectEntries->count()
            );

            // Storing the data in the cache for 10 minutes
            $this->getCache()->store($cacheId, $data, self::CACHE_TIMEOUT);
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





}