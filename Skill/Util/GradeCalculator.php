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

    const CACHE_TIMEOUT = 60*60*24*2;

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
            $cachePath = $this->getConfig()->getDataPath() . '/skillResultsCache/' . $collection->getSubject()->getInstitutionId() . '/' . $collection->getVolatileId();
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
     * @param array $filter
     * @return string
     */
    public function getStudentGradeCacheId($user, $filter = array())
    {
        return sprintf('sg-%s-%s-%s', $this->collection->getId(), $user->getId(), md5(json_encode($filter)));
    }

    /**
     * @param array $filter
     * @return string
     */
    public function getSubjectGradesCacheId($filter = array())
    {
        return sprintf('sgl-%s-%s', $this->collection->getId(), md5(json_encode($filter)));
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
     * @return bool
     */
    public function flushCache()
    {
        return \Tk\File::rmdir($this->cachePath);
    }


    /**
     * @param \Uni\Db\UserIface $user
     * @param array $filter
     * @return mixed|Grade
     * @throws \Exception
     */
    public function getStudentGrade($user, $filter = array())
    {
        $cacheId = $this->getStudentGradeCacheId($user, $filter);
        $grade = null;

        if (!$this->getConfig()->isRefreshCacheRequest() && $this->isCacheEnabled()) {
            $grade = $this->getCache()->fetch($cacheId);
        }

        if (!$grade) {
            \Tk\Log::notice('   - Student: ' . $user->getName());
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
                $avg = \Skill\Db\ItemMap::create()->findAverage($user->getId(), $item->getId(), 'approved', 'completed', $filter);
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
     * @param array $filter
     * @return mixed
     * @throws \Exception
     */
    public function getSubjectGrades($filter = array())
    {
        $start = microtime(true);
        $cacheId = $this->getSubjectGradesCacheId($filter);
        $data = null;
        if (!$this->getConfig()->isRefreshCacheRequest() || !$this->isCacheEnabled()) {
            $data = $this->getCache()->fetch($cacheId);
        }

        if (!$data) {
            \Tk\Log::notice('Refreshing Skills Collection Results Cache: ' . $this->collection->name);

            $gradeList = array();
            $gradeValueList = array();
            $studentList = \App\Db\UserMap::create()->findFiltered(array('subjectId' => $this->collection->subjectId));
            foreach ($studentList as $student) {
                $result = $this->getStudentGrade($student, $filter);
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
                'min' => (count($gradeValueList) > 0) ? round(min($gradeValueList) * $this->collection->maxGrade, 2) : 0,
                'median' => round(\Tk\Math::median($gradeValueList) * $this->collection->maxGrade, 2),
                'max' => (count($gradeValueList) > 0) ? round(max($gradeValueList) * $this->collection->maxGrade, 2) : 0,
                'avg' => round(\Tk\Math::average($gradeValueList), 2),
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
                'min' => (count($gradeValueList) > 0) ? round(min($gradeValueList)*100, 2) : 0,
                'median' => round(\Tk\Math::median($gradeValueList)*100, 2),
                'max' => (count($gradeValueList) > 0) ? round(max($gradeValueList)*100, 2) : 0,
                'avg' => round(\Tk\Math::average($gradeValueList)*100, 2),
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