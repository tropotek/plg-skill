<?php
namespace Skill\Db;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class ReportingMap extends \App\Db\Mapper
{



    /**
     * Mapper constructor.
     *
     * @param \Tk\Db\Pdo|null $db
     * @throws \Exception
     * @throws \Tk\Db\Exception
     */
    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->setMarkDeleted('');           // Default to have a del field (This will only mark the record deleted)
        $this->setAlias('');
    }


    /**
     * Find filtered records
     *
     * @param array|\Tk\Db\Filter $filter
     * @param \Tk\Db\Tool $tool
     * @return \Tk\Db\Map\ArrayObject|Item[]
     * @throws \Exception
     */
    public function findFiltered($filter = array(), $tool = null)
    {
        $filter = \Tk\Db\Filter::create($filter);
        if (!$tool) $tool = \Tk\Db\Tool::create();

        $select = <<<SQL
sc.id as 'collection_id', sc.name as 'collection_name',

se.id as 'entry_id', se.title as 'entry_title',

si.id as 'item_id', si.uid as 'item_uid', si.question as 'item_question',

cat.id as 'category_id', cat.name as 'category_name',

sd.id as 'domain_id', sd.name as 'domain_name',

s.id as 'subject_id', s.name as 'subject_name',

u.id as 'user_id', CONCAT(u.name_first, ' ', u.name_last) as 'user_name', u.uid as 'user_uid', u.email as 'user_email',

p.id as 'placement_id', p.date_start as 'placement_dateStart', p.date_end as 'placement_dateEnd', CONCAT(CONCAT(u.name_first, ' ', u.name_last), '@', c.name) as 'placement_title',

pt.name as 'placement_type_name', 

c.id as 'company_id', c.name as 'company_name',

sup.id as 'supervisor_id', sup.name as 'supervisor_name',

IFNULL(sv.value, '0') as 'item_value',
SQL;
        $filter->setSelect($select);

        $from = <<<SQL
          `skill_collection` sc
LEFT JOIN skill_entry se ON (sc.id = se.collection_id)
LEFT JOIN `subject` s ON (sc.subject_id = s.id)
LEFT JOIN `user` u ON (se.user_id = u.id)
LEFT JOIN `placement` p ON (se.placement_id = p.id)
LEFT JOIN `placement_type` pt ON (p.placement_type_id = pt.id)
LEFT JOIN `company` c ON (p.`company_id` = c.id)
LEFT JOIN `supervisor` sup ON (p.supervisor_id = sup.id)

LEFT JOIN `skill_item` si ON (sc.id = si.collection_id)
LEFT JOIN `skill_category` cat ON (si.category_id = cat.id)
LEFT JOIN `skill_domain` sd ON (si.domain_id = sd.id)

LEFT JOIN `skill_value` sv ON (se.id = sv.entry_id AND si.id = sv.item_id)
SQL;
        $filter->setFrom($from);

        $where = <<<SQL
se.id IS NOT NULL AND 
SQL;
        $filter->setWhere($where);


        if (!empty($filter['id'])) {
            $w = $this->makeMultiQuery($filter['id'], 'a.id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['collectionId'])) {
            $filter->appendWhere('sc.id = %s AND ', (int)$filter['collectionId']);
        }

        if (!empty($filter['collectionUid'])) {
            $filter->appendWhere('sc.uid = %s AND ', (int)$filter['collectionUid']);
        }

        if (!empty($filter['itemId'])) {
            $w = $this->makeMultiQuery($filter['itemId'], 'si.uid', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['categoryId'])) {
            $w = $this->makeMultiQuery($filter['categoryId'], 'cat.uid', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['domainId'])) {
            $w = $this->makeMultiQuery($filter['domainId'], 'sd.uid', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['scaleId'])) {
            $w = $this->makeMultiQuery($filter['scaleId'], 'sv.value', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['studentNumber'])) {
            $filter->appendWhere('u.uid = %s AND ', $this->getDb()->quote($filter['studentNumber']));
        }

        if (!empty($filter['placementId'])) {
            $filter->appendWhere('p.id = %s AND ', (int)$filter['placementId']);
        }

        // Include zero values in the results
        if (!empty($filter['excludeZero'])) {
            $filter->appendWhere('sv.value IS NOT NULL AND sv.value > 0 AND ');
        }

        if (!empty($filter['subjectId'])) {
            $w = $this->makeMultiQuery($filter['subjectId'], 's.id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['companyId'])) {
            $w = $this->makeMultiQuery($filter['companyId'], 'c.id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['supervisorId'])) {
            $w = $this->makeMultiQuery($filter['supervisorId'], 'sup.id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }


        if (!empty($filter['dateStart']) && !empty($filter['dateEnd'])) {     // Contains
            /** @var \DateTime $dateStart */
            $dateStart = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateStart']));
            /** @var \DateTime $dateEnd */
            $dateEnd = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateEnd']));

            $filter->appendWhere('((p.date_start >= %s AND ', $this->quote($dateStart->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $filter->appendWhere('p.date_start <= %s) OR ', $this->quote($dateEnd->format(\Tk\Date::FORMAT_ISO_DATETIME)) );

            $filter->appendWhere('(p.date_end <= %s AND ', $this->quote($dateStart->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $filter->appendWhere('p.date_end >= %s)) AND ', $this->quote($dateEnd->format(\Tk\Date::FORMAT_ISO_DATETIME)) );

        } else if (!empty($filter['dateStart'])) {
            /** @var \DateTime $date */
            $date = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateStart']));
            $filter->appendWhere('p.date_start >= %s AND ', $this->quote($date->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        } else if (!empty($filter['dateEnd'])) {
            /** @var \DateTime $date */
            $date = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateEnd']));
            $filter->appendWhere('p.date_end <= %s AND ', $this->quote($date->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        }

        $res = $this->selectFromFilter($filter, $tool);
        //vd($this->getDb()->getLastQuery());
        return $res;
    }



    /**
     * Find filtered records for graphing calculating the average values
     *
     * @param array $filter
     * @param \Tk\Db\Tool $tool
     * @return \Tk\Db\Map\ArrayObject|Item[]
     * @throws \Exception
     */
    public function findItemAverage($filter = array(), $tool = null)
    {
        $filter = \Tk\Db\Filter::create($filter);
        if (!$tool) $tool = \Tk\Db\Tool::create();

        $tool->setDistinct(false);
        $tool->setGroupBy('si.uid');
        //$tool->setOrderBy('si.uid');

        $select = <<<SQL
sc.id as 'collection_id',
si.uid as 'item_uid', si.question as 'item_question',
cat.id as 'category_id', cat.name as 'category_name',
sd.id as 'domain_id', sd.name as 'domain_name',
c.id as 'company_id', c.name as 'company_name',
sup.id as 'supervisor_id', sup.name as 'supervisor_name',
ROUND(AVG(sv.value), 3) as 'average', COUNT(sv.value) as 'count'
SQL;
        $filter->setSelect($select);

        $from = <<<SQL
`skill_collection` sc
LEFT JOIN skill_entry se ON (sc.id = se.collection_id)
LEFT JOIN `subject` s ON (sc.subject_id = s.id)
LEFT JOIN `user` u ON (se.user_id = u.id)
LEFT JOIN `placement` p ON (se.placement_id = p.id)
LEFT JOIN `company` c ON (p.`company_id` = c.id)
LEFT JOIN `supervisor` sup ON (p.supervisor_id = sup.id)
LEFT JOIN `skill_item` si ON (sc.id = si.collection_id)
LEFT JOIN `skill_category` cat ON (si.category_id = cat.id)
LEFT JOIN `skill_domain` sd ON (si.domain_id = sd.id)
LEFT JOIN `skill_value` sv ON (se.id = sv.entry_id AND si.id = sv.item_id)
SQL;
        $filter->setFrom($from);

        $where = <<<SQL
se.id IS NOT NULL AND 
SQL;
        $filter->setWhere($where);

        
        if (!empty($filter['collectionId'])) {
            $filter->appendWhere('sc.id = %s AND ', (int)$filter['collectionId']);
        }

        if (!empty($filter['collectionUid'])) {
            $filter->appendWhere('sc.uid = %s AND ', (int)$filter['collectionUid']);
        }

        if (!empty($filter['itemId'])) {
            $w = $this->makeMultiQuery($filter['itemId'], 'si.uid', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['categoryId'])) {
            $w = $this->makeMultiQuery($filter['categoryId'], 'cat.uid', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['domainId'])) {
            $w = $this->makeMultiQuery($filter['domainId'], 'sd.uid', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['scaleId'])) {
            $w = $this->makeMultiQuery($filter['scaleId'], 'sv.value', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['studentNumber'])) {
            $filter->appendWhere('u.uid = %s AND ', $this->getDb()->quote($filter['studentNumber']));
        }

        if (!empty($filter['placementId'])) {
            $filter->appendWhere('p.id = %s AND ', (int)$filter['placementId']);
        }

        // Include zero values in the results
        //if (!empty($filter['excludeZero'])) {
        $filter->appendWhere('sv.value IS NOT NULL AND sv.value > 0 AND ');
        //}

        if (!empty($filter['subjectId'])) {
            $w = $this->makeMultiQuery($filter['subjectId'], 's.id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['companyId'])) {
            $w = $this->makeMultiQuery($filter['companyId'], 'c.id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['supervisorId'])) {
            $w = $this->makeMultiQuery($filter['supervisorId'], 'sup.id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['dateStart']) && !empty($filter['dateEnd'])) {     // Contains
            /** @var \DateTime $dateStart */
            $dateStart = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateStart']));
            /** @var \DateTime $dateEnd */
            $dateEnd = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateEnd']));

            $filter->appendWhere('((p.date_start >= %s AND ', $this->quote($dateStart->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $filter->appendWhere('p.date_start <= %s) OR ', $this->quote($dateEnd->format(\Tk\Date::FORMAT_ISO_DATETIME)) );

            $filter->appendWhere('(p.date_end <= %s AND ', $this->quote($dateStart->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $filter->appendWhere('p.date_end >= %s)) AND ', $this->quote($dateEnd->format(\Tk\Date::FORMAT_ISO_DATETIME)) );

        } else if (!empty($filter['dateStart'])) {
            /** @var \DateTime $date */
            $date = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateStart']));
            $filter->appendWhere('p.date_start >= %s AND ', $this->quote($date->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        } else if (!empty($filter['dateEnd'])) {
            /** @var \DateTime $date */
            $date = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateEnd']));
            $filter->appendWhere('p.date_end <= %s AND ', $this->quote($date->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        }

        $res = $this->selectFromFilter($filter, $tool);
        //vd($this->getDb()->getLastQuery());
        return $res;
    }


    /**
     * Find filtered records for graphing calculating the average values
     *
     * @param array $filter
     * @param \Tk\Db\Tool $tool
     * @return \Tk\Db\Map\ArrayObject|Item[]
     * @throws \Exception
     */
    public function findDateAverage($filter = array(), $tool = null)
    {
        $filter = \Tk\Db\Filter::create($filter);
        if (!$tool) $tool = \Tk\Db\Tool::create();

        $interval = "DATE(CONCAT(YEAR(p.date_start), '-', MONTH(p.date_start), '-01'))";        // Default Monthly
        if (isset($filter['interval']) && $filter['interval'] == '1D') $interval = 'DATE(p.date_start)';

        $tool->setDistinct(false);
        $tool->setGroupBy('si.uid, '.$interval);
        $tool->setOrderBy('si.uid, '.$interval);

        $select = <<<SQL
sc.id as 'collection_id',
si.uid as 'item_uid', si.question as 'item_question',
ROUND(AVG(sv.value), 3) as 'average', COUNT(sv.value) as 'count', $interval as 'date'
SQL;
        $filter->setSelect($select);

        $from = <<<SQL
`skill_collection` sc
LEFT JOIN skill_entry se ON (sc.id = se.collection_id)
LEFT JOIN `subject` s ON (sc.subject_id = s.id)
LEFT JOIN `user` u ON (se.user_id = u.id)
LEFT JOIN `placement` p ON (se.placement_id = p.id)
LEFT JOIN `company` c ON (p.`company_id` = c.id)
LEFT JOIN `supervisor` sup ON (p.supervisor_id = sup.id)
LEFT JOIN `skill_item` si ON (sc.id = si.collection_id)
LEFT JOIN `skill_value` sv ON (se.id = sv.entry_id AND si.id = sv.item_id)
SQL;
        $filter->setFrom($from);

        $where = <<<SQL
se.id IS NOT NULL AND 
SQL;
        $filter->setWhere($where);

        if (!empty($filter['collectionId'])) {
            $filter->appendWhere('sc.id = %s AND ', (int)$filter['collectionId']);
        }

        if (!empty($filter['collectionUid'])) {
            $filter->appendWhere('sc.uid = %s AND ', (int)$filter['collectionUid']);
        }

        if (!empty($filter['itemId'])) {
            $w = $this->makeMultiQuery($filter['itemId'], 'si.uid', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['categoryId'])) {
            $w = $this->makeMultiQuery($filter['categoryId'], 'cat.uid', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['domainId'])) {
            $w = $this->makeMultiQuery($filter['domainId'], 'sd.uid', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['scaleId'])) {
            $w = $this->makeMultiQuery($filter['scaleId'], 'sv.value', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['studentNumber'])) {
            $filter->appendWhere('u.uid = %s AND ', $this->getDb()->quote($filter['studentNumber']));
        }

        if (!empty($filter['placementId'])) {
            $filter->appendWhere('p.id = %s AND ', (int)$filter['placementId']);
        }

        // Include zero values in the results
        //if (!empty($filter['excludeZero'])) {
        $filter->appendWhere('sv.value IS NOT NULL AND sv.value > 0 AND ');
        //}

        if (!empty($filter['subjectId'])) {
            $w = $this->makeMultiQuery($filter['subjectId'], 's.id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['companyId'])) {
            $w = $this->makeMultiQuery($filter['companyId'], 'c.id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['supervisorId'])) {
            $w = $this->makeMultiQuery($filter['supervisorId'], 'sup.id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['dateStart']) && !empty($filter['dateEnd'])) {     // Contains
            /** @var \DateTime $dateStart */
            $dateStart = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateStart']));
            /** @var \DateTime $dateEnd */
            $dateEnd = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateEnd']));

            $filter->appendWhere('((p.date_start >= %s AND ', $this->quote($dateStart->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $filter->appendWhere('p.date_start <= %s) OR ', $this->quote($dateEnd->format(\Tk\Date::FORMAT_ISO_DATETIME)) );

            $filter->appendWhere('(p.date_end <= %s AND ', $this->quote($dateStart->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $filter->appendWhere('p.date_end >= %s)) AND ', $this->quote($dateEnd->format(\Tk\Date::FORMAT_ISO_DATETIME)) );

        } else if (!empty($filter['dateStart'])) {
            /** @var \DateTime $date */
            $date = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateStart']));
            $filter->appendWhere('p.date_start >= %s AND ', $this->quote($date->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        } else if (!empty($filter['dateEnd'])) {
            /** @var \DateTime $date */
            $date = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateEnd']));
            $filter->appendWhere('p.date_end <= %s AND ', $this->quote($date->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        }

        $res = $this->selectFromFilter($filter, $tool);
        //vd($this->getDb()->getLastQuery());
        return $res;
    }



    /**
     * Find filtered records for graphing calculating the average values
     *
     * @param array $filter
     * @param \Tk\Db\Tool $tool
     * @return \Tk\Db\Map\ArrayObject|Item[]
     * @throws \Exception
     */
    public function findCompanyTotalAverage($filter = array(), $tool = null)
    {
        $filter = \Tk\Db\Filter::create($filter);
        if (!$tool) $tool = \Tk\Db\Tool::create();

        $tool->setDistinct(false);
        $tool->setGroupBy('a.company_id');

        $select = <<<SQL
a.company_id, b.name, a.subject_id, AVG(a.avg) as 'avg', ROUND((AVG(a.avg) / a.scale) * 100, 3) as 'pct',
       COUNT(a.entry_id) as 'entry_count', MIN(a.pct) as 'min', MAX(a.pct) as 'max', b.created
SQL;
        $filter->setSelect($select);

        $from = <<<SQL
(
       SELECT b.id as 'placement_id', a.collection_id, a1.uid as 'collection_uid',b.subject_id , b.supervisor_id, b.company_id, a.id as 'entry_id', s.scale, 
              ROUND(AVG(c.`value`), 3) as 'avg', ROUND((AVG(c.`value`) / s.scale) * 100, 3) as 'pct', b.date_start, b.date_end
       FROM skill_entry a,
            skill_collection a1,
            placement b,
            skill_value c,
            (
              SELECT a.collection_id, COUNT(a.id) - 1 As 'scale'
              FROM skill_scale a
              GROUP BY a.collection_id
            ) s
       WHERE !a.del AND !b.del AND a.collection_id = a1.id
             AND a.placement_id = b.id AND c.value > 0 AND a.id = c.entry_id
       GROUP BY a.id
     ) a,
     company b
SQL;
        $filter->setFrom($from);

        $where = <<<SQL
!b.del AND a.company_id = b.id AND 
SQL;
        $filter->setWhere($where);

        if (!empty($filter['collectionId'])) {
            $w = $this->makeMultiQuery($filter['collectionId'], 'a.collection_id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['collectionUid'])) {
            $filter->appendWhere('a.collection_uid = %s AND ', (int)$filter['collectionUid']);
        }

        if (!empty($filter['subjectId'])) {
            $w = $this->makeMultiQuery($filter['subjectId'], 'a.subject_id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['companyId'])) {
            $w = $this->makeMultiQuery($filter['companyId'], 'b.id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['minEntries'])) {
            $tool->setHaving(sprintf('COUNT(a.entry_id) >= %s ', (int)$filter['minEntries']));
        }

        if (!empty($filter['dateStart']) && !empty($filter['dateEnd'])) {     // Contains
            /** @var \DateTime $dateStart */
            $dateStart = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateStart']));
            /** @var \DateTime $dateEnd */
            $dateEnd = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateEnd']));

            $filter->appendWhere('((a.date_start >= %s AND ', $this->quote($dateStart->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $filter->appendWhere('a.date_start <= %s) OR ', $this->quote($dateEnd->format(\Tk\Date::FORMAT_ISO_DATETIME)) );

            $filter->appendWhere('(a.date_end <= %s AND ', $this->quote($dateStart->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $filter->appendWhere('a.date_end >= %s)) AND ', $this->quote($dateEnd->format(\Tk\Date::FORMAT_ISO_DATETIME)) );

        } else if (!empty($filter['dateStart'])) {
            /** @var \DateTime $date */
            $date = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateStart']));
            $filter->appendWhere('a.date_start >= %s AND ', $this->quote($date->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        } else if (!empty($filter['dateEnd'])) {
            /** @var \DateTime $date */
            $date = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateEnd']));
            $filter->appendWhere('a.date_end <= %s AND ', $this->quote($date->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        }

        $res = $this->selectFromFilter($filter, $tool);
        //vd($this->getDb()->getLastQuery());
        return $res;
    }



    /**
     * Find filtered records for graphing calculating the average values
     *
     * @param array $filter
     * @param \Tk\Db\Tool $tool
     * @return \Tk\Db\Map\ArrayObject|Item[]
     * @throws \Exception
     */
    public function findCompanyAverage($filter = array(), $tool = null)
    {
        $filter = \Tk\Db\Filter::create($filter);
        if (!$tool) $tool = \Tk\Db\Tool::create();

        $tool->setDistinct(false);
        $tool->setGroupBy('a.id');

        $select = <<<SQL
b.id as 'placement_id', a.collection_id, a1.uid as 'collection_uid', b.supervisor_id, b.company_id, a.id as 'entry_id', s.scale, 
              ROUND(AVG(c.`value`), 3) as 'avg', ROUND((AVG(c.`value`) / s.scale) * 100, 3) as 'pct', a.created, b.date_start, b.date_end
SQL;
        $filter->setSelect($select);

        $from = <<<SQL
skill_entry a,
    skill_collection a1,
    placement b,
    skill_value c,
    (
      SELECT a.collection_id, COUNT(a.id) - 1 As 'scale'
      FROM skill_scale a
      GROUP BY a.collection_id
    ) s
SQL;
        $filter->setFrom($from);

        $where = <<<SQL
!a.del AND !b.del AND a.collection_id = a1.id AND a.placement_id = b.id AND c.value > 0 AND a.id = c.entry_id AND 
SQL;
        $filter->setWhere($where);

        if (!empty($filter['collectionId'])) {
            $w = $this->makeMultiQuery($filter['collectionId'], 'a.collection_id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['collectionUid'])) {
            $filter->appendWhere('a1.uid = %s AND ', (int)$filter['collectionUid']);
        }

        if (!empty($filter['companyId'])) {
            $w = $this->makeMultiQuery($filter['companyId'], 'b.company_id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['subjectId'])) {
            $w = $this->makeMultiQuery($filter['subjectId'], 'b.subject_id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['dateStart']) && !empty($filter['dateEnd'])) {     // Contains
            /** @var \DateTime $dateStart */
            $dateStart = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateStart']));
            /** @var \DateTime $dateEnd */
            $dateEnd = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateEnd']));

            $filter->appendWhere('((b.date_start >= %s AND ', $this->quote($dateStart->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $filter->appendWhere('b.date_start <= %s) OR ', $this->quote($dateEnd->format(\Tk\Date::FORMAT_ISO_DATETIME)) );

            $filter->appendWhere('(b.date_end <= %s AND ', $this->quote($dateStart->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $filter->appendWhere('b.date_end >= %s)) AND ', $this->quote($dateEnd->format(\Tk\Date::FORMAT_ISO_DATETIME)) );

        } else if (!empty($filter['dateStart'])) {
            /** @var \DateTime $date */
            $date = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateStart']));
            $filter->appendWhere('b.date_start >= %s AND ', $this->quote($date->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        } else if (!empty($filter['dateEnd'])) {
            /** @var \DateTime $date */
            $date = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateEnd']));
            $filter->appendWhere('b.date_end <= %s AND ', $this->quote($date->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        }

        $res = $this->selectFromFilter($filter, $tool);
        //vd($this->getDb()->getLastQuery());
        return $res;
    }

}