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
     * @param array $filter
     * @param \Tk\Db\Tool $tool
     * @return \Tk\Db\Map\ArrayObject|Item[]
     * @throws \Exception
     */
    public function findFiltered($filter = array(), $tool = null)
    {
        if (!$tool) $tool = \Tk\Db\Tool::create();

        $select = <<<SQL
sc.id as 'collection_id', sc.name as 'collection_name',

se.id as 'entry_id', se.title as 'entry_title',

si.id as 'item_id', si.uid as 'item_uid', si.question as 'item_question',

cat.id as 'category_id', cat.name as 'category_name',

sd.id as 'domain_id', sd.name as 'domain_name',

s.id as 'subject_id', s.name as 'subject_name',

u.id as 'user_id', u.name as 'user_name', u.uid as 'user_uid', u.email as 'user_email',

p.id as 'placement_id', p.date_start as 'placement_dateStart', p.date_end as 'placement_dateEnd', CONCAT(u.name, '@', c.name) as 'placement_title',

pt.name as 'placement_type_name', 

c.id as 'company_id', c.name as 'company_name',

sup.id as 'supervisor_id', sup.name as 'supervisor_name',

IFNULL(sv.value, '0') as 'item_value'

SQL;

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

        $where = <<<SQL
se.id IS NOT NULL AND 
SQL;



        if (!empty($filter['collectionId'])) {
            $where .= sprintf('sc.uid = %s AND ', (int)$filter['collectionId']);
        }

        if (!empty($filter['itemId'])) {
            $w = $this->makeMultiQuery($filter['itemId'], 'si.uid', 'OR');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['categoryId'])) {
            $w = $this->makeMultiQuery($filter['categoryId'], 'cat.uid', 'OR');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['domainId'])) {
            $w = $this->makeMultiQuery($filter['domainId'], 'sd.uid', 'OR');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['scaleId'])) {
            $w = $this->makeMultiQuery($filter['scaleId'], 'sv.value', 'OR');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['studentNumber'])) {
            $where .= sprintf('u.uid = %s AND ', $this->getDb()->quote($filter['studentNumber']));
        }

        if (!empty($filter['placementId'])) {
            $where .= sprintf('p.id = %s AND ', (int)$filter['placementId']);
        }




        // Include zero values in the results
        if (!empty($filter['excludeZero'])) {
            $where .= sprintf('sv.value IS NOT NULL AND sv.value > 0 ');
        }

        if (!empty($filter['subjectId'])) {
            $w = $this->makeMultiQuery($filter['subjectId'], 's.id', 'OR');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['companyId'])) {
            $w = $this->makeMultiQuery($filter['companyId'], 'c.id', 'OR');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['supervisorId'])) {
            $w = $this->makeMultiQuery($filter['supervisorId'], 'sup.id', 'OR');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }


        if (!empty($filter['dateStart']) && !empty($filter['dateEnd'])) {     // Contains
            /** @var \DateTime $dateStart */
            $dateStart = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateStart']));
            /** @var \DateTime $dateEnd */
            $dateEnd = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateEnd']));

            $where .= sprintf('((p.date_start >= %s AND ', $this->quote($dateStart->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $where .= sprintf('p.date_start <= %s) OR ', $this->quote($dateEnd->format(\Tk\Date::FORMAT_ISO_DATETIME)) );

            $where .= sprintf('(p.date_end <= %s AND ', $this->quote($dateStart->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $where .= sprintf('p.date_end >= %s)) AND ', $this->quote($dateEnd->format(\Tk\Date::FORMAT_ISO_DATETIME)) );

        } else if (!empty($filter['dateStart'])) {
            /** @var \DateTime $date */
            $date = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateStart']));
            $where .= sprintf('p.date_start >= %s AND ', $this->quote($date->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        } else if (!empty($filter['dateEnd'])) {
            /** @var \DateTime $date */
            $date = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateEnd']));
            $where .= sprintf('p.date_end <= %s AND ', $this->quote($date->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        }


        if ($where) {
            $where = substr($where, 0, -4);
        }

        $res = $this->selectFrom($from, $where, $tool, $select);
        //vd($this->getDb()->getLastQuery());
        return $res;
    }





/*


SELECT si.uid, si.question, AVG(sv.value) as 'average', COUNT(sv.value) as 'count', DATE(p.date_start)

FROM `skill_collection` sc
  LEFT JOIN skill_entry se ON (sc.id = se.collection_id)
  LEFT JOIN `subject` s ON (sc.subject_id = s.id)
  LEFT JOIN `placement` p ON (se.placement_id = p.id)

  LEFT JOIN `skill_item` si ON (sc.id = si.collection_id)
--  LEFT JOIN `skill_category` cat ON (si.category_id = cat.id)
--  LEFT JOIN `skill_domain` sd ON (si.domain_id = sd.id)

  LEFT JOIN `skill_value` sv ON (se.id = sv.entry_id AND si.id = sv.item_id)

WHERE
  sc.uid = 1 AND
  sv.value > 0 AND
  si.uid = 1 AND
  p.date_start >= DATE('2016-01-01') AND p.date_end < DATE('2016-12-31')
GROUP BY si.uid, DATE(p.date_start)
ORDER BY si.uid, DATE(p.date_start)
;

     */




    /**
     * Find filtered records
     *
     * @param \DateTime $dateStart
     * @param \DateTime $dateEnd
     * @param string $interval (default `1D`) Options `1D` `1W` `1Y`
     * @param array $filter
     * @param \Tk\Db\Tool $tool
     * @return \Tk\Db\Map\ArrayObject|Item[]
     * @throws \Tk\Db\Exception
     */
    public function findFilteredByDates($dateStart, $dateEnd, $interval = '1D', $filter = array(), $tool = null)
    {
        if (!$tool) $tool = \Tk\Db\Tool::create();
        $mysqlInterval = '1 DAY';
        if ($interval == '1D') $mysqlInterval = '1 DAY';
        if ($interval == '1W') $mysqlInterval = '1 WEEK';
        if ($interval == '1M') $mysqlInterval = '1 MONTH';
        if ($interval == '1Y') $mysqlInterval = '1 YEAR';
        $calTable = 'repCal_'.$interval;

        if (!$this->getDb()->hasTable($calTable)) {
            $this->createDateTable($dateStart, $dateEnd, $calTable);
        }


        $select = <<<SQL
sc.id as 'collection_id', sc.name as 'collection_name',

se.id as 'entry_id', se.title as 'entry_title',

si.id as 'item_id', si.uid as 'item_uid', si.question as 'item_question',

cat.id as 'category_id', cat.name as 'category_name',

sd.id as 'domain_id', sd.name as 'domain_name',

s.id as 'subject_id', s.name as 'subject_name',

u.id as 'user_id', u.name as 'user_name', u.uid as 'user_uid', u.email as 'user_email',

p.id as 'placement_id', p.date_start as 'placement_dateStart', p.date_end as 'placement_dateEnd', CONCAT(u.name, '@', c.name) as 'placement_title',

pt.name as 'placement_type_name', 

c.id as 'company_id', c.name as 'company_name',

sup.id as 'supervisor_id', sup.name as 'supervisor_name',

IFNULL(sv.value, '0') as 'item_value'

SQL;

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

        $where = <<<SQL
se.id IS NOT NULL AND 
SQL;



        if (!empty($filter['collectionId'])) {
            $where .= sprintf('sc.uid = %s AND ', (int)$filter['collectionId']);
        }

        if (!empty($filter['itemId'])) {
            $w = $this->makeMultiQuery($filter['itemId'], 'si.uid', 'OR');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['categoryId'])) {
            $w = $this->makeMultiQuery($filter['categoryId'], 'cat.uid', 'OR');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['domainId'])) {
            $w = $this->makeMultiQuery($filter['domainId'], 'sd.uid', 'OR');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['scaleId'])) {
            $w = $this->makeMultiQuery($filter['scaleId'], 'sv.value', 'OR');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['studentNumber'])) {
            $where .= sprintf('u.uid = %s AND ', $this->getDb()->quote($filter['studentNumber']));
        }

        if (!empty($filter['placementId'])) {
            $where .= sprintf('p.id = %s AND ', (int)$filter['placementId']);
        }




        // Include zero values in the results
        if (!empty($filter['excludeZero'])) {
            $where .= sprintf('sv.value IS NOT NULL AND sv.value > 0 ');
        }

        if (!empty($filter['subjectId'])) {
            $w = $this->makeMultiQuery($filter['subjectId'], 's.id', 'OR');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['companyId'])) {
            $w = $this->makeMultiQuery($filter['companyId'], 'c.id', 'OR');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['supervisorId'])) {
            $w = $this->makeMultiQuery($filter['supervisorId'], 'sup.id', 'OR');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }


        if (!empty($filter['dateStart']) && !empty($filter['dateEnd'])) {     // Contains
            /** @var \DateTime $dateStart */
            $dateStart = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateStart']));
            /** @var \DateTime $dateEnd */
            $dateEnd = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateEnd']));

            $where .= sprintf('((p.date_start >= %s AND ', $this->quote($dateStart->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $where .= sprintf('p.date_start <= %s) OR ', $this->quote($dateEnd->format(\Tk\Date::FORMAT_ISO_DATETIME)) );

            $where .= sprintf('(p.date_end <= %s AND ', $this->quote($dateStart->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
            $where .= sprintf('p.date_end >= %s)) AND ', $this->quote($dateEnd->format(\Tk\Date::FORMAT_ISO_DATETIME)) );

        } else if (!empty($filter['dateStart'])) {
            /** @var \DateTime $date */
            $date = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateStart']));
            $where .= sprintf('p.date_start >= %s AND ', $this->quote($date->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        } else if (!empty($filter['dateEnd'])) {
            /** @var \DateTime $date */
            $date = \Tk\Date::floor(\Tk\Date::createFormDate($filter['dateEnd']));
            $where .= sprintf('p.date_end <= %s AND ', $this->quote($date->format(\Tk\Date::FORMAT_ISO_DATETIME)) );
        }


        if ($where) {
            $where = substr($where, 0, -4);
        }

        $res = $this->selectFrom($from, $where, $tool, $select);
        //vd($this->getDb()->getLastQuery());
        return $res;
    }
}