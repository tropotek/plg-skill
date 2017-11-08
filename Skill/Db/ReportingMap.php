<?php
namespace Skill\Db;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class ReportingMap
{
    /**
     * @var \Tk\Db\Pdo
     */
    private $db = null;

    
    /**
     * @param \Tk\Db\Pdo $db
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * @param null|\Tk\Db\Pdo $db
     * @return static
     */
    public static function create($db = null) {
        if ($db == null) {
            $db = \App\Factory::getDb();
        }
        $obj = new static($db);
        return $obj;
    }

    /**
     * @return \Tk\Db\Pdo
     */
    public function getDb() 
    {
        return $this->db;
    }

    
    
    
    
    /**
     * 
     * @param $collectionId
     * @param $courseId
     * @param int $userId
     * @param bool $valueOnly
     * @return array
     */
    public function findStudentResult($collectionId, $courseId, $userId = 0, $valueOnly = false)
    {
        $usql = '';
        if ($userId) {
            $usql = ' AND a.user_id = ? ';
        }
        
        $sql = <<<SQL
SELECT a.collection_id, a.user_id, a.course_id, SUM(a.weighted_avg) / a.scale AS 'course_result'
  FROM
    (SELECT a.collection_id, a.user_id, a.course_id, a.domain_id, a.label, c.scale, a.weight,
      SUM(a.average) / b.count AS 'avg', (SUM(a.average) / b.count) * a.weight AS 'weighted_avg'
    FROM
      (
        SELECT a.collection_id, a.course_id, a.user_id, c.id AS 'item_id', a.id AS 'entry_id', d.id AS 'domain_id',
          d.label, c.question, ROUND(AVG(b.value), 2) AS 'average', d.order_by, d.weight
        FROM skill_entry a, skill_value b, skill_item c, skill_domain d
        WHERE
          a.del = 0 AND c.del = 0 AND d.del = 0 AND
              a.id = b.entry_id AND b.value > 0 AND
              a.status = 'approved' AND
              b.item_id = c.id AND
              c.domain_id = d.id
        GROUP BY a.collection_id, a.course_id, a.user_id, b.item_id
        ORDER BY d.order_by, c.order_by
      ) a,
      (
        SELECT a.domain_id, COUNT(a.id) AS 'count'
        FROM skill_item a
        GROUP BY a.domain_id
      ) b,
      (
        SELECT a.collection_id, COUNT(a.id) - 1 AS 'scale'
        FROM skill_scale a
        GROUP BY a.collection_id
      ) c
    WHERE
      a.domain_id = b.domain_id AND
          c.collection_id = a.collection_id

    GROUP BY a.collection_id, a.course_id, a.user_id, a.domain_id
    ORDER BY a.order_by
    ) a
  WHERE
    a.collection_id = ? AND a.course_id = ? $usql
  GROUP BY a.collection_id, a.course_id, a.user_id
SQL;
        
        $stm = $this->getDb()->prepare($sql);
        $stm->bindParam(1, $collectionId);
        $stm->bindParam(2, $courseId);
        if ($usql)
            $stm->bindParam(3, $userId);
        
        $stm->execute();
        $arr = $stm->fetchAll();
        if ($valueOnly) {
            if (!count($arr) && $userId) return 0;
            $arr1 = array();
            foreach ($arr as $obj) {
                if ($userId) return $obj->course_result;
                $arr1[$obj->user_id] = $obj->course_result;
            }
            $arr = $arr1;
        }
        return $arr;
    }
    
    
    
    
    
    
    /**
     * 
     * @param $collectionId
     * @param $courseId
     * @param int $userId
     * @param bool $valueOnly
     * @return array
     */
    public function findDomainAverages($collectionId, $courseId, $userId = 0, $valueOnly = false)
    {
        $usql = '';
        if ($userId) {
            $usql = ' a.user_id = ? AND ';
        }
        
        $sql = <<<SQL
SELECT a.domain_id, a.label, c.scale, a.weight, SUM(a.average)/b.count AS 'avg', a.order_by
FROM
  (
    SELECT a.collection_id, a.course_id, a.user_id, c.id AS 'item_id', a.id AS 'entry_id', d.id AS 'domain_id', d.label, c.question,
      ROUND(AVG(b.value), 2) AS 'average', d.order_by, d.weight
    FROM skill_entry a, skill_value b, skill_item c, skill_domain d
    WHERE
      a.del = 0 AND c.del = 0 AND d.del = 0 AND
          a.id = b.entry_id AND b.value > 0 AND
           a.status = 'approved' AND
          b.item_id = c.id AND
          c.domain_id = d.id
    GROUP BY a.collection_id, a.course_id, a.user_id, b.item_id
    ORDER BY d.order_by, c.order_by
  ) a,
  (
    SELECT a.domain_id, COUNT(a.id) AS 'count'
    FROM skill_item a
    GROUP BY a.domain_id
  ) b,
  (
    SELECT a.collection_id, COUNT(a.id)-1 as 'scale'
    FROM skill_scale a
    GROUP BY a.collection_id
  ) c
WHERE
  a.collection_id = ? AND a.course_id = ? AND $usql 
  a.domain_id = b.domain_id AND
  c.collection_id = a.collection_id

GROUP BY a.domain_id
ORDER BY a.order_by;
SQL;
        
        // Faster query
        $sql = <<<SQL
SELECT a.domain_id, a.label, c.scale, a.weight, SUM(a.average)/b.count as 'avg', (SUM(a.average)/b.count)*a.weight as 'weighted_avg'
FROM
  (
    SELECT c.id as 'item_id', a.collection_id, a.id as 'entry_id', d.id as 'domain_id', d.label, c.question, ROUND(AVG(b.value), 2) as 'average', d.order_by, d.weight
    FROM skill_entry a, skill_value b, skill_item c, skill_domain d
    WHERE a.del = 0 AND c.del = 0 AND d.del = 0 AND
        a.id = b.entry_id AND b.value > 0 AND
        a.collection_id = ? AND a.course_id = ? AND $usql a.status = 'approved' AND
        b.item_id = c.id AND
        c.domain_id = d.id
    GROUP BY b.item_id
    ORDER BY d.order_by, c.order_by
  ) a,
  (
    SELECT a.domain_id, COUNT(a.id) as 'count'
    FROM skill_item a
    GROUP BY a.domain_id
  ) b,
  (
    SELECT a.collection_id, COUNT(a.id)-1 as 'scale'
    FROM skill_scale a
    GROUP BY a.collection_id
  ) c
WHERE
  a.domain_id = b.domain_id AND
  c.collection_id = a.collection_id
GROUP BY a.domain_id
ORDER BY a.order_by
SQL;
        
        $stm = $this->getDb()->prepare($sql);
        $stm->bindParam(1, $collectionId);
        $stm->bindParam(2, $courseId);
        if ($usql)
            $stm->bindParam(3, $userId);
        
        $stm->execute();
        $arr = $stm->fetchAll();
        if ($valueOnly) {
            $arr1 = array();
            foreach ($arr as $obj) {
                $arr1[$obj->domain_id] = $obj->avg;
            }
            $arr = $arr1;
        }
        return $arr;
    }
    
    /**
     *
     * @param $collectionId
     * @param $courseId
     * @param null $userId
     * @param bool $valueOnly  If true then only the itemId and average is return as an array key,value pair
     * @return array
     */
    public function findItemAverages($collectionId, $courseId, $userId = null, $valueOnly = false)
    {
        $usql = '';
        if ($userId) {
            $usql = ' AND a.user_id = ?';
        }
        
        $sql = <<<SQL
SELECT a.id AS 'entry_id', c.id AS 'item_id', c.category_id, d.id AS 'domain_id', d.label, c.question,
  ROUND(AVG(b.value), 2) AS 'avg', c.order_by
FROM skill_entry a, skill_value b, skill_item c, skill_domain d
WHERE
  a.del = 0 AND c.del = 0 AND d.del = 0 AND
      a.id = b.entry_id AND
      a.collection_id = ? AND a.course_id = ? $usql AND a.status = 'approved' AND
      b.item_id = c.id AND b.value > 0 AND
      c.domain_id = d.id
GROUP BY b.item_id
ORDER BY d.order_by, c.order_by
SQL;
        $stm = $this->getDb()->prepare($sql);

        $stm->bindParam(1, $collectionId);
        $stm->bindParam(2, $courseId);
        if ($usql)
            $stm->bindParam(3, $userId);
        $stm->execute();
        $arr = $stm->fetchAll();
        if ($valueOnly) {
            $arr1 = array();
            foreach ($arr as $obj) {
                $arr1[$obj->item_id] = $obj->avg;
            }
            $arr = $arr1;
        }
        return $arr;
    }
    
}