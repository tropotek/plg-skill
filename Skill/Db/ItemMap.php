<?php
namespace Skill\Db;

use Tk\DataMap\Db;
use Tk\DataMap\Form;
use Tk\Db\Map\ArrayObject;
use Tk\Db\Tool;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class ItemMap extends \App\Db\Mapper
{

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->setTable('skill_item');
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('uid'));
            $this->dbMap->addPropertyMap(new Db\Integer('collectionId', 'collection_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('categoryId', 'category_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('domainId', 'domain_id'));
            $this->dbMap->addPropertyMap(new Db\Text('question'));
            $this->dbMap->addPropertyMap(new Db\Text('description'));
            $this->dbMap->addPropertyMap(new Db\Boolean('publish'));
            $this->dbMap->addPropertyMap(new Db\Integer('orderBy', 'order_by'));
            $this->dbMap->addPropertyMap(new Db\Date('modified'));
            $this->dbMap->addPropertyMap(new Db\Date('created'));
        }
        return $this->dbMap;
    }

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getFormMap()
    {
        if (!$this->formMap) {
            $this->formMap = new \Tk\DataMap\DataMap();
            $this->formMap->addPropertyMap(new Form\Integer('id'), 'key');
            $this->formMap->addPropertyMap(new Form\Integer('uid'));
            $this->formMap->addPropertyMap(new Form\Integer('collectionId'));
            $this->formMap->addPropertyMap(new Form\Integer('categoryId'));
            $this->formMap->addPropertyMap(new Form\Integer('domainId'));
            $this->formMap->addPropertyMap(new Form\Text('question'));
            $this->formMap->addPropertyMap(new Form\Text('description'));
            $this->formMap->addPropertyMap(new Form\Boolean('publish'));
        }
        return $this->formMap;
    }

    /**
     * Get an Item average for a user, entries that have a 0 value are ignored in this
     * calculation.
     *
     * @param int $userId
     * @param int $itemId
     * @param string $entryStatus
     * @param array $filter
     * @return float
     * @throws \Tk\Db\Exception
     */
    public function findAverageForUser($userId, $itemId, $entryStatus = 'approved', $filter = array())
    {
        $db = $this->getDb();

        $sql = <<<SQL
SELECT AVG(b.`value`) as 'avg'
FROM  skill_entry a LEFT JOIN skill_value b ON (a.id = b.entry_id)
WHERE a.user_id = ? AND b.item_id = ? AND b.value > 0 AND a.`status` = ?
SQL;

        if (!empty($filter['notCompanyId'])) {
            $w = $this->makeMultiQuery($filter['notCompanyId'], 'c.company_id', 'AND', '!=');
            if ($w) {
                $sql .= ' AND ('. $w . ')';
            }
        }
        $stmt = $db->prepare($sql);
        $stmt->execute(array((int)$userId, (int)$itemId, $entryStatus));
        $avg = (float)round($stmt->fetchColumn(), 2);
        return $avg;
    }


    /**
     * Get an Item average for a user, entries that have a 0 value are ignored in this
     * calculation.
     *
     * @param int $companyId
     * @param int $itemId
     * @param string $entryStatus
     * @param array $filter
     * @return float
     * @throws \Tk\Db\Exception
     */
    public function findAverageForCompany($companyId, $itemId, $entryStatus = 'approved', $filter = array())
    {
        $db = $this->getDb();

        $sql = <<<SQL
SELECT a.entry_id
    ,SUM(a.value) as 'value_total'
    ,COUNT(a.item_id) as 'item_count'
    ,AVG(NULLIF(a.value, 0)) as 'avg'
    ,AVG(NULLIF(a.value, 0))/d.scale_count as 'avg_ratio'
FROM skill_value a, skill_entry b, skill_collection c,
     (
     SELECT a.collection_id, COUNT(a.id) - 1 AS 'scale_count'
     FROM skill_scale a
     GROUP BY a.collection_id
     ) d
WHERE a.entry_id=? AND a.entry_id = b.id AND b.collection_id = c.id AND b.collection_id = d.collection_id
SQL;

        if (!empty($filter['notCompanyId'])) {
            $w = $this->makeMultiQuery($filter['notCompanyId'], 'c.company_id', 'AND', '!=');
            if ($w) {
                $sql .= ' AND ('. $w . ')';
            }
        }
        $stmt = $db->prepare($sql);
        $stmt->execute(array((int)$companyId, (int)$itemId, $entryStatus));
        $avg = (float)round($stmt->fetchColumn(), 2);
        return $avg;
    }

    /**
     * @param array|\Tk\Db\Filter $filter
     * @param Tool $tool
     * @return ArrayObject|Item[]
     * @throws \Exception
     */
    public function findFiltered($filter, $tool = null)
    {
        return $this->selectFromFilter($this->makeQuery(\Tk\Db\Filter::create($filter)), $tool);
    }

    /**
     * @param \Tk\Db\Filter $filter
     * @return \Tk\Db\Filter
     */
    public function makeQuery(\Tk\Db\Filter $filter)
    {
        $filter->appendSelect('a.*, cat.order_by as \'cat_order\', ');
        $filter->appendFrom('%s a , `skill_category` cat', $this->quoteParameter($this->getTable()));
        $filter->appendWhere('a.category_id = cat.id AND ');

        if (!empty($filter['keywords'])) {
            $kw = '%' . $this->getDb()->escapeString($filter['keywords']) . '%';
            $w = '';
            $w .= sprintf('a.question LIKE %s OR ', $this->getDb()->quote($kw));
            $w .= sprintf('a.description LIKE %s OR ', $this->getDb()->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $w = $this->makeMultiQuery($filter['id'], 'a.id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['uid'])) {
            $filter->appendWhere('a.uid = %s AND ', $this->quote($filter['uid']));
        }
        if (!empty($filter['collectionId'])) {
            $filter->appendWhere('a.collection_id = %s AND ', (int)$filter['collectionId']);
        }

        if (!empty($filter['categoryId'])) {
            $filter->appendWhere('a.category_id = %s AND ', (int)$filter['categoryId']);
        }

        if (!empty($filter['typeId'])) {
            if (!is_array($filter['typeId'])) $filter['typeId'] = array($filter['typeId']);
            foreach ($filter['typeId'] as $i => $tid) {
                $a = 'b'.$i;
                $filter->appendFrom("\n    " . sprintf('INNER JOIN %s %s ON (a.id = %s.item_id AND %s.type_id = %s ) ',
                        $this->quoteParameter('item_has_type'), $a, $a, $a, (int)$tid));
            }
        }

        if (!empty($filter['type']) && is_array($filter['type'])) {
            $i = 0;
            foreach ($filter['type'] as $typeGroup => $typeId) {
                $a = 'd' . $i;
                $w = $this->makeMultiQuery($typeId, $a.'.type_id', 'OR');
                $filter->appendFrom("\n    " . sprintf('INNER JOIN %s %s ON (a.id = %s.item_id AND (%s)) ',
                        $this->quoteParameter('item_has_type'), $a, $a, $w));
                $i++;
            }
        }

        if (!empty($filter['domainId'])) {
            $w = $this->makeMultiQuery($filter['domainId'], 'a.domain_id', 'OR');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['entryId'])) {
            $from .= sprintf(', %s c', $this->quoteParameter('skill_selected'));
            $filter->appendWhere('a.id = c.item_id AND c.entry_id = %s AND ', (int)$filter['entryId']);
        }

        if (!empty($filter['question'])) {
            $filter->appendWhere('a.question = %s AND ', $this->quote($filter['question']));
        }

        if (!empty($filter['publish'])) {
            $filter->appendWhere('a.publish = %s AND ', (int)$filter['publish']);
        }




        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        return $filter;
    }

}