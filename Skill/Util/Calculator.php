<?php

namespace Skill\Util;

/**
 * @author Tropotek <info@tropotek.com>
 * @created: 6/08/18
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Tropotek
 */
class Calculator
{

    const CACHE_TIMEOUT = 60*60*24*7;


    /**
     * @param \Skill\Db\Collection $collection
     * @param \App\Db\Subject $subject
     * @param bool $force
     * @return object|null
     * @throws \Exception
     */
    public static function findSubjectAverageGrades($collection, $subject, $force = false)
    {
        set_time_limit(0);
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


}