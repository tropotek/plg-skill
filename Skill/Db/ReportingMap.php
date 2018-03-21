<?php
namespace Skill\Db;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
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
            $db = \App\Config::getInstance()->getDb();
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
     * @param $subjectId
     * @param int $userId
     * @param bool $valueOnly
     * @return array
     */
    public function findStudentResult($collectionId, $subjectId, $userId = 0, $valueOnly = false)
    {
        $usql = '';
        if ($userId) {
            $usql = ' AND a.user_id = ? ';
        }
        
        $sql = <<<SQL
SELECT a.collection_id, a.user_id, a.subject_id, SUM(a.weighted_avg) / a.scale AS 'subject_result'
  FROM
    (SELECT a.collection_id, a.user_id, a.subject_id, a.domain_id, a.label, c.scale, a.weight,
      SUM(a.average) / b.count AS 'avg', (SUM(a.average) / b.count) * a.weight AS 'weighted_avg'
    FROM
      (
        SELECT a.collection_id, a.subject_id, a.user_id, c.id AS 'item_id', a.id AS 'entry_id', d.id AS 'domain_id',
          d.label, c.question, ROUND(AVG(b.value), 2) AS 'average', d.order_by, d.weight
        FROM skill_entry a, skill_value b, skill_item c, skill_domain d
        WHERE
          a.del = 0 AND c.del = 0 AND d.del = 0 AND
              a.id = b.entry_id AND b.value > 0 AND
              a.status = 'approved' AND
              b.item_id = c.id AND
              c.domain_id = d.id
        GROUP BY a.collection_id, a.subject_id, a.user_id, b.item_id
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

    GROUP BY a.collection_id, a.subject_id, a.user_id, a.domain_id
    ORDER BY a.order_by
    ) a
  WHERE
    a.collection_id = ? AND a.subject_id = ? $usql
  GROUP BY a.collection_id, a.subject_id, a.user_id
SQL;
        
        $stm = $this->getDb()->prepare($sql);
        $stm->bindParam(1, $collectionId);
        $stm->bindParam(2, $subjectId);
        if ($usql)
            $stm->bindParam(3, $userId);
        
        $stm->execute();
        $arr = $stm->fetchAll();
        if ($valueOnly) {
            if (!count($arr) && $userId) return 0;
            $arr1 = array();
            foreach ($arr as $obj) {
                if ($userId) return $obj->subject_result;
                $arr1[$obj->user_id] = $obj->subject_result;
            }
            $arr = $arr1;
        }
        return $arr;
    }


    /**
     *
     * @param $collectionId
     * @param $subjectId
     * @param bool $valueOnly
     * @return array
     */
    public function findSubjectAverages($collectionId, $subjectId, $valueOnly = false)
    {
        // Faster query
        $sql = <<<SQL
SELECT * 
FROM (
    SELECT a.domain_id, a.label, c.scale, a.weight, SUM(a.average)/b.count as 'avg', (SUM(a.average)/b.count)*a.weight as 'weighted_avg'
    FROM
      (
        SELECT c.id as 'item_id', a.collection_id, a.id as 'entry_id', d.id as 'domain_id', 
            d.label, c.question, ROUND(AVG(b.value), 2) as 'average', d.order_by, d.weight
        FROM skill_entry a, skill_value b, skill_item c, skill_domain d
        WHERE a.del = 0 AND c.del = 0 AND d.del = 0 AND
            a.id = b.entry_id AND b.value > 0 AND
            a.collection_id = ? AND a.subject_id = ? AND a.status = 'approved' AND
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
    ORDER BY a.order_by ) a

SQL;

        $stm = $this->getDb()->prepare($sql);
        $stm->bindParam(1, $collectionId);
        $stm->bindParam(2, $subjectId);

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
     * Find the average and total results for the student
     *
     * @param $collectionId
     * @param $subjectId
     * @param int $userId
     * @return array
     */
    public function findStudentResults($collectionId, $subjectId, $userId = 0)
    {
        $collectionId = (int)$collectionId;
        $subjectId = (int)$subjectId;
        $usql = '';
        if ($userId) {
            $usql = ' a.user_id = ' . (int)$userId . ' AND ';
        }

        /*
         * TODO: We need to finish this as a query, may have to use a procedure or similar
         * See: https://stackoverflow.com/questions/17964078/mysql-query-to-dynamically-convert-rows-to-columns-on-the-basis-of-two-columns
         */
        $sql = <<<SQL
SELECT a.collection_id, a.user_id, a.domain_id, a.label, a.label_name, b.weight,
  c.max_grade, ROUND(AVG(a.avg), 2) as 'avg',
  (ROUND(AVG(a.avg), 2)*(c.max_grade/d.scale)) as 'grade', a.name, a.uid
FROM
  (
    SELECT a.collection_id, a.subject_id, a.user_id, d.domain_id, c.item_id, d.question,
      IFNULL(ROUND(AVG(NULLIF(c.value, 0)), 2), 0) AS 'avg', d.order_by, a.label, a.label_name, a.name, a.uid
    FROM
      (
        SELECT a.collection_id, a.subject_id, a.user_id, a.entry_id, b.domain_id, c.id as 'item_id', c.name as 'label_name', c.label, b.question, b.order_by, a.name, a.uid
        FROM
          (
            SELECT a.collection_id, a.subject_id, a.user_id, b.id as 'entry_id', a.name, a.uid
            FROM
              (
                SELECT $collectionId as 'collection_id', b.subject_id, a.id as 'user_id', a.name, a.uid
                FROM user a, subject_has_student b
                WHERE a.id = b.user_id AND  a.del = 0
                      -- AND a.id = 1494           # user id
                      AND b.subject_id = $subjectId      # subject id
                GROUP BY a.id, b.subject_id
                ORDER BY b.subject_id
              ) a,
              skill_entry b
            WHERE
              b.del = 0 AND
              a.user_id = b.user_id AND
              b.status = 'approved' AND
              b.collection_id = a.collection_id AND
              b.subject_id = a.subject_id AND
              b.user_id = a.user_id
          ) a,
          skill_item b, skill_domain c
        WHERE
          b.del = 0 AND c.del = 0 AND
          c.active = 1 AND
          a.collection_id = b.collection_id AND
          b.domain_id = c.id
        ORDER BY a.user_id, b.order_by
      ) a,
      skill_value c, skill_item d
    WHERE
      d.del = 0 AND
      a.entry_id = c.entry_id AND
      c.item_id = d.id AND
      a.domain_id = d.domain_id
    -- AND a.label = 'CS'
    -- AND c.value > 0
    GROUP BY a.user_id, c.item_id
    ORDER BY a.user_id, d.order_by
  ) a,
  skill_domain b,
  skill_collection c,
  (
    SELECT a.collection_id, COUNT(a.id)-1 as 'scale'
    FROM skill_scale a
    GROUP BY a.collection_id
  ) d

WHERE a.domain_id = b.id AND
    c.id = a.collection_id AND
    $usql      -- AND a.user_id = 1494           # user id
    d.collection_id = a.collection_id
    

GROUP BY a.user_id, b.id
ORDER BY a.user_id, b.order_by
SQL;

/* returns:
1	1494	1	PD	Personal And Professional Development	0.05	10.00	4.45	8.9	Aaron Adno	637920
1	1494	3	SB	Scientific Basis Of Clinical Practice	0.2	10.00	4.15	8.3	Aaron Adno	637920
1	1494	4	CS	Clinical Skills	0.5	10.00	2.29	4.58	Aaron Adno	637920
1	1494	6	AW	Ethics And Animal Welfare	0.2	10.00	2.29	4.58	Aaron Adno	637920
1	1494	7	BIOS	Biosecurity And Population Health	0.05	10.00	4	8	Aaron Adno	637920
*/
/* We Want
Student Number	Name	PD	SB	CS	AW	BIOS	PD Grade	SB Grade	CS Grade	AW Grade	BIOS Grade	Total 100%
637920	Aaron Adno	4.45	4.15	2.29	2.29	4.00	8.90	8.30	4.58	4.57	8.00	57.11
*/

//vd($sql);
        $stm = $this->getDb()->prepare($sql);
        $stm->execute();

        $arr = array();
        foreach ($stm as $i => $row) {
            //vd($row);
            if (!array_key_exists($row->user_id, $arr)) {
                $arr[$row->user_id] = array('user_id' => $row->user_id, 'uid' => $row->uid, 'name' => $row->name, 'max_grade' => $row->max_grade);
            }
            $arr[$row->user_id][$row->label] = $row->avg;
            $arr[$row->user_id][$row->label.'_grade'] = $row->grade;
            $arr[$row->user_id][$row->label.'_weight'] = $row->weight;
        }

        //TODO:  Calculate the total grade

        return $arr;
    }



    
    
    /**
     * 
     * @param $collectionId
     * @param $subjectId
     * @param int $userId
     * @param bool $valueOnly
     * @return array
     */
    public function findDomainAverages($collectionId, $subjectId, $userId = 0, $valueOnly = false)
    {
        $usql = '';
        if ($userId) {
            $usql = ' a.user_id = ? AND ';
        }
        
        $sql = <<<SQL
SELECT a.domain_id, a.label, c.scale, a.weight, SUM(a.average)/b.count AS 'avg', a.order_by
FROM
  (
    SELECT a.collection_id, a.subject_id, a.user_id, c.id AS 'item_id', a.id AS 'entry_id', d.id AS 'domain_id', d.label, c.question,
      ROUND(AVG(b.value), 2) AS 'average', d.order_by, d.weight
    FROM skill_entry a, skill_value b, skill_item c, skill_domain d
    WHERE
      a.del = 0 AND c.del = 0 AND d.del = 0 AND
          a.id = b.entry_id AND b.value > 0 AND
           a.status = 'approved' AND
          b.item_id = c.id AND
          c.domain_id = d.id
    GROUP BY a.collection_id, a.subject_id, a.user_id, b.item_id
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
  a.collection_id = ? AND a.subject_id = ? AND $usql 
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
        a.collection_id = ? AND a.subject_id = ? AND $usql a.status = 'approved' AND
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
        $stm->bindParam(2, $subjectId);
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
     * @param $subjectId
     * @param null $userId
     * @param bool $valueOnly  If true then only the itemId and average is return as an array key,value pair
     * @return array
     */
    public function findItemAverages($collectionId, $subjectId, $userId = null, $valueOnly = false)
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
      a.collection_id = ? AND a.subject_id = ? $usql AND a.status = 'approved' AND
      b.item_id = c.id AND b.value > 0 AND
      c.domain_id = d.id
GROUP BY b.item_id
ORDER BY d.order_by, c.order_by
SQL;
        $stm = $this->getDb()->prepare($sql);

        $stm->bindParam(1, $collectionId);
        $stm->bindParam(2, $subjectId);
        if ($usql)
            $stm->bindParam(3, $userId);
        $stm->execute();
        $arr = $stm->fetchAll();

        $arr1 = array();
        foreach ($arr as $obj) {
            if ($valueOnly) {
                $arr1[$obj->item_id] = $obj->avg;
            } else {
                $arr1[$obj->item_id] = $obj;
            }

        }
        return $arr1;
    }
    
}