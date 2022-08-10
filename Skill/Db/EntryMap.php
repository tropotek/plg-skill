<?php
namespace Skill\Db;

use Tk\DataMap\Db;
use Tk\DataMap\Form;
use Tk\Db\Map\ArrayObject;
use Tk\Db\Tool;


/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class EntryMap extends \App\Db\Mapper
{

    /**
     * @return \Tk\DataMap\DataMap
     * @throws \Exception
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->setTable('skill_entry');
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('collectionId', 'collection_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('subjectId', 'subject_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('userId', 'user_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('placementId', 'placement_id'));
            $this->dbMap->addPropertyMap(new Db\Text('title'));
            $this->dbMap->addPropertyMap(new Db\Text('assessor'));
            $this->dbMap->addPropertyMap(new Db\Integer('absent'));
            $this->dbMap->addPropertyMap(new Db\Decimal('average'));
            $this->dbMap->addPropertyMap(new Db\Decimal('weightedAverage', 'weighted_average'));
            $this->dbMap->addPropertyMap(new Db\Text('confirm'));
            $this->dbMap->addPropertyMap(new Db\Text('status'));
            $this->dbMap->addPropertyMap(new Db\Text('notes'));
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
            $this->formMap->addPropertyMap(new Form\Integer('collectionId'));
            $this->formMap->addPropertyMap(new Form\Integer('subjectId'));
            $this->formMap->addPropertyMap(new Form\Integer('userId'));
            $this->formMap->addPropertyMap(new Form\Integer('placementId'));
            $this->formMap->addPropertyMap(new Form\Text('title'));
            $this->formMap->addPropertyMap(new Form\Text('assessor'));
            $this->formMap->addPropertyMap(new Form\Integer('absent'));
//            $this->formMap->addPropertyMap(new Form\Decimal('average'));
//            $this->formMap->addPropertyMap(new Form\Decimal('weightedAverage'));
            $this->formMap->addPropertyMap(new Form\Text('confirm'));
            $this->formMap->addPropertyMap(new Form\Text('status'));
            $this->formMap->addPropertyMap(new Form\Text('notes'));
        }
        return $this->formMap;
    }


    /**
     * @param array|\Tk\Db\Filter $filter
     * @param Tool $tool
     * @return ArrayObject|Entry[]
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
        $filter->appendFrom('%s a ', $this->quoteParameter($this->getTable()));

        if (!empty($filter['keywords'])) {
            $kw = '%' . $this->getDb()->escapeString($filter['keywords']) . '%';
            $w = '';
            $w .= sprintf('a.title LIKE %s OR ', $this->getDb()->quote($kw));
            $w .= sprintf('a.assessor LIKE %s OR ', $this->getDb()->quote($kw));
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

        if (!empty($filter['subjectId'])) {
            $filter->appendWhere('a.subject_id = %s AND ', (int)$filter['subjectId']);
        }

        if (!empty($filter['collectionId'])) {
            $filter->appendWhere('a.collection_id = %s AND ', (int)$filter['collectionId']);
        }

        if (!empty($filter['userId'])) {
            $filter->appendWhere('a.user_id = %s AND ', (int)$filter['userId']);
        }

        if (!empty($filter['placementTypeId'])) {
            $filter->appendFrom(' LEFT JOIN skill_collection_placement_type b ON (a.collection_id = b.collection_id)');
            $filter->appendWhere('b.placement_type_id = %s AND ', (int)$filter['placementTypeId']);
        }

        if (!empty($filter['placementId'])) {
            $filter->appendWhere('a.placement_id = %s AND ', (int)$filter['placementId']);
        }

        $filter->appendFrom(', placement c');
        $filter->appendWhere('a.placement_id = c.id AND ');
        if (!empty($filter['placementStatus'])) {
            $w = $this->makeMultiQuery($filter['placementStatus'], 'c.status');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['companyId'])) {
            $filter->appendWhere('c.company_id = %s AND ', (int)$filter['companyId']);
        }

        if (!empty($filter['title'])) {
            $filter->appendWhere('a.title = %s AND ', $this->quote($filter['title']));
        }

        if (!empty($filter['status'])) {
            $w = $this->makeMultiQuery($filter['status'], 'a.status');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        return $filter;
    }

    /**
     * @param int $entryId
     * @param int $itemId
     * @return array|\stdClass
     * @throws \Exception
     */
    public function findValue($entryId, $itemId = 0)
    {
        $st = null;
        if ($itemId) {
            $st = $this->getDb()->prepare('SELECT * FROM skill_value a WHERE a.entry_id = ? AND a.item_id = ?');
            $st->bindParam(1, $entryId);
            $st->bindParam(2, $itemId);
        } else {
            $st = $this->getDb()->prepare('SELECT * FROM skill_value a WHERE a.entry_id = ?');
            $st->bindParam(1, $entryId);
        }
        $st->execute();
        $arr = $st->fetchAll();
        if($itemId) return current($arr);
        return $arr;
    }

    /**
     * @param int $entryId
     * @param int $itemId
     * @param string $value
     * @throws \Exception
     */
    public function saveValue($entryId, $itemId, $value)
    {
        /** @var Entry $entry */
        $entry = $this->find($entryId);
        if ($entry) {   // Ensure values entered do not exceed the number of scale items minus 1 because we start with 0
            $scale = ($entry->getCollection()->getScaleCount());
            if ($value < 0) $value = 0;
            if ($value > $scale) $value = $scale;
        }

        if ($this->hasValue($entryId, $itemId)) {
            $st = $this->getDb()->prepare('UPDATE skill_value SET value = ? WHERE entry_id = ? AND item_id = ? ');
        } else {
            $st = $this->getDb()->prepare('INSERT INTO skill_value (value, entry_id, item_id) VALUES (?, ?, ?)');
        }
        $st->bindParam(1, $value);
        $st->bindParam(2, $entryId);
        $st->bindParam(3, $itemId);
        $st->execute();
    }

    /**
     * @param int $entryId
     * @param int $itemId
     * @throws \Exception
     */
    public function removeValue($entryId, $itemId = null)
    {
        $st = $this->getDb()->prepare('DELETE FROM skill_value WHERE entry_id = ?');
        $st->bindParam(1, $entryId);
        if ($itemId !== null) {
            $st = $this->getDb()->prepare('DELETE FROM skill_value WHERE entry_id = ? AND item_id = ?');
            $st->bindParam(1, $entryId);
            $st->bindParam(2, $itemId);
        }
        $st->execute();
    }

    /**
     * Does the value record exist
     *
     * @param int $entryId
     * @param int $itemId
     * @return bool
     * @throws \Exception
     */
    public function hasValue($entryId, $itemId)
    {
        $val = $this->findValue($entryId, $itemId);
        return $val != null;
    }


    /**
     * Return a basic average for an entry
     *
     * NOTE: does not include domain weights and should not be used to
     *       calculate the students final grade
     *
     * @param int $entryId
     * @return \stdClass
     * @throws \Tk\Db\Exception
     */
    public function getEntryResultObject($entryId)
    {
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
        $st = $this->getDb()->prepare($sql);
        $st->bindParam(1, $entryId);
        $st->execute();

        $obj = $st->fetch();
        return $obj;
    }


    /**
     * This is not a grade average using the domain weights
     * It is just an average of all the entries item values
     *
     * @param int $entryId
     * @return float
     * @throws \Tk\Db\Exception
     */
    public function getEntryAverage($entryId)
    {
        $obj = $this->getEntryResultObject($entryId);
        return $obj->avg;
    }


    /**
     * Get the students average ratio. Calculated by avg/scale_count
     * Multiply this value by 100 to get a percentage
     *
     * @param int $entryId
     * @return float
     * @throws \Tk\Db\Exception
     */
    public function getEntryRatio($entryId)
    {
        $obj = $this->getEntryResultObject($entryId);
        return $obj->avg_ratio;
    }

}