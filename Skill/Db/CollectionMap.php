<?php
namespace Skill\Db;

use Tk\Db\Exception;
use Tk\Db\Tool;
use Tk\Db\Map\ArrayObject;
use Tk\DataMap\Db;
use Tk\DataMap\Form;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CollectionMap extends \App\Db\Mapper
{

    /**
     * @return \Tk\DataMap\DataMap
     * @throws \Tk\Db\Exception
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->setTable('skill_collection');
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('uid'));
            $this->dbMap->addPropertyMap(new Db\Integer('courseId', 'course_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('subjectId', 'subject_id'));
            $this->dbMap->addPropertyMap(new Db\Text('name'));
            $this->dbMap->addPropertyMap(new Db\Text('role'));
            $this->dbMap->addPropertyMap(new Db\Text('icon'));
            $this->dbMap->addPropertyMap(new Db\Text('color'));
            $this->dbMap->addPropertyMap(new Db\ArrayObject('available'));
            $this->dbMap->addPropertyMap(new Db\Boolean('publish'));
            $this->dbMap->addPropertyMap(new Db\Boolean('active'));
            $this->dbMap->addPropertyMap(new Db\Boolean('gradable'));
            $this->dbMap->addPropertyMap(new Db\Boolean('requirePlacement', 'require_placement'));
            $this->dbMap->addPropertyMap(new Db\Decimal('maxGrade', 'max_grade'));
            //$this->dbMap->addPropertyMap(new Db\Boolean('viewGrade', 'view_grade'));
            //$this->dbMap->addPropertyMap(new Db\Boolean('includeZero', 'include_zero'));
            $this->dbMap->addPropertyMap(new Db\Text('confirm'));
            $this->dbMap->addPropertyMap(new Db\Text('instructions'));
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
            $this->formMap->addPropertyMap(new Form\Integer('uid'));
            //$this->formMap->addPropertyMap(new Form\Integer('courseId'));
            $this->formMap->addPropertyMap(new Form\Integer('subjectId'));
            $this->formMap->addPropertyMap(new Form\Text('name'));
            $this->formMap->addPropertyMap(new Form\Text('role'));
            $this->formMap->addPropertyMap(new Form\Text('icon'));
            $this->formMap->addPropertyMap(new Form\Text('color'));
            $this->formMap->addPropertyMap(new Form\ObjectMap('available'));
            $this->formMap->addPropertyMap(new Form\Boolean('publish'));
            $this->formMap->addPropertyMap(new Form\Boolean('active'));
            $this->formMap->addPropertyMap(new Form\Boolean('gradable'));
            $this->formMap->addPropertyMap(new Form\Boolean('requirePlacement'));
            $this->formMap->addPropertyMap(new Form\Decimal('maxGrade'));
            $this->formMap->addPropertyMap(new Form\Boolean('viewGrade'));
            //$this->formMap->addPropertyMap(new Form\Boolean('includeZero'));
            $this->formMap->addPropertyMap(new Form\Text('confirm'));
            $this->formMap->addPropertyMap(new Form\Text('instructions'));
            $this->formMap->addPropertyMap(new Form\Text('notes'));
        }
        return $this->formMap;
    }

    /**
     * TODO: This can be removed by Jan 2019 as that would be enough time
     * TODO:   for all old Entry links in emails to be no-longer valid...
     *
     */
    public function fixChangeoverEntries()
    {
        \Tk\Log::info('Fixing Skill Entries and item Values... (Remove After: Jan 2019)');
        if ($this->getConfig()->isDebug()) {
            \Tk\Log::info('   - Stopped (debug mode)');
            return;
        }

        // Update Entry collection_id for old Link submissions
        try {
            $this->getDb()->exec('UPDATE skill_entry a, skill_collection b
    SET a.collection_id = b.id
    WHERE a.collection_id = b.org_id AND a.subject_id = b.subject_id');

            $this->getDb()->exec('UPDATE skill_entry c, skill_item b, skill_value a
SET a.item_id = b.id
WHERE c.collection_id = b.collection_id AND a.item_id = b.org_id AND a.entry_id = c.id');

        } catch (\Exception $e) {
            \Tk\Log::error($e->__toString());
        }
    }

    /**
     * @param array|\Tk\Db\Filter $filter
     * @param Tool $tool
     * @return ArrayObject|Collection[]
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
            $w .= sprintf('a.name LIKE %s OR ', $this->getDb()->quote($kw));
            $w .= sprintf('a.instructions LIKE %s OR ', $this->getDb()->quote($kw));
            $w .= sprintf('a.notes LIKE %s OR ', $this->getDb()->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

//        if (!empty($filter['profileId'])) {
//            $filter->appendWhere('a.profile_id = %s AND ', (int)$filter['profileId']);
//        }
        if (!empty($filter['id'])) {
            $w = $this->makeMultiQuery($filter['id'], 'a.id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['uid'])) {
            $filter->appendWhere('a.uid = %s AND ', $this->quote($filter['uid']));
        }

        if (!empty($filter['subjectId'])) {
            $filter->appendWhere('a.subject_id = %s AND ', (int)$filter['subjectId']);
        }

        if (!empty($filter['courseId'])) {
            $filter->appendWhere('a.course_id = %s AND ', (int)$filter['courseId']);
        }

        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = %s AND ', $this->quote($filter['name']));
        }

        if (!empty($filter['color'])) {
            $filter->appendWhere('a.color = %s AND ', $this->quote($filter['color']));
        }

        if (!empty($filter['gradable'])) {
            $filter->appendWhere('a.gradable = %s AND ', (int)$filter['gradable']);
        }

        if (isset($filter['requirePlacement']) && $filter['requirePlacement'] !== null && $filter['requirePlacement'] !== '') {
            $filter->appendWhere('a.require_placement = %s AND ', (int)$filter['requirePlacement']);
        }

        if (!empty($filter['active'])) {
            $filter->appendWhere('a.active = %s AND ', (int)$filter['active']);
        }

        if (!empty($filter['publish'])) {
            $filter->appendWhere('a.publish = %s AND ', (int)$filter['publish']);
        }

//        if (isset($filter['enabledSubjectId'])) {      // Only selects collections that have been enabled in the subject
//            $filter->appendFrom(', %s c', $this->quoteTable('skill_collection_subject'));
//            $filter->appendWhere('a.id = c.collection_id AND c.subject_id = %s AND ', (int)$filter['enabledSubjectId']);
//        }

//        if (!empty($filter['subjectId'])) {
//            $filter->appendFrom(', %s d', $this->quoteTable('subject'));
//            $filter->appendWhere('a.profile_id = d.profile_id AND d.id = %s AND ', (int)$filter['subjectId']);
//        }

        if (!empty($filter['placementTypeId'])) {
            $filter->appendFrom(' LEFT JOIN %s b ON (a.id = b.collection_id) ',
                $this->quoteTable('skill_collection_placement_type'));
            $filter->appendWhere('b.placement_type_id = %s AND ', (int)$filter['placementTypeId']);
        }

        if (!empty($filter['role'])) {
            $w = $this->makeMultiQuery($filter['role'], 'a.role');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        // Find all collections that are enabled for the given placement statuses
        if (!empty($filter['available'])) {
            if (!is_array($filter['available'])) $filter['available'] = array($filter['available']);
            $w = '';
            foreach ($filter['available'] as $r) {
                $w .= sprintf('a.available LIKE %s OR ', $this->getDb()->quote('%'.$r.'%'));
            }
            if ($w) $filter->appendWhere('('. rtrim($w, ' OR ') . ') AND ');
        }



        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        return $filter;
    }


    // Link to placement types

    /**
     * @param int $collectionId
     * @param int $placementTypeId
     * @return boolean
     */
    public function hasPlacementType($collectionId, $placementTypeId)
    {
        try {
            $stm = $this->getDb()->prepare('SELECT * FROM skill_collection_placement_type WHERE collection_id = ? AND placement_type_id = ?');
            $stm->bindParam(1, $collectionId);
            $stm->bindParam(2, $placementTypeId);
            $stm->execute();
            return ($stm->rowCount() > 0);
        } catch (Exception $e) {}
        return false;
    }

    /**
     * @param int $collectionId
     * @param int $placementTypeId (optional) If null all are to be removed
     */
    public function removePlacementType($collectionId, $placementTypeId = null)
    {
        try {
            $stm = $this->getDb()->prepare('DELETE FROM skill_collection_placement_type WHERE collection_id = ?');
            $stm->bindParam(1, $collectionId);
            if ($placementTypeId) {
                $stm = $this->getDb()->prepare('DELETE FROM skill_collection_placement_type WHERE collection_id = ? AND placement_type_id = ?');
                $stm->bindParam(1, $collectionId);
                $stm->bindParam(2, $placementTypeId);
            }
            $stm->execute();
        } catch (Exception $e) {}
    }

    /**
     * @param int $collectionId
     * @param int $placementTypeId
     */
    public function addPlacementType($collectionId, $placementTypeId)
    {
        try {
            if ($this->hasPlacementType($collectionId, $placementTypeId)) return;
            $stm = $this->getDb()->prepare('INSERT INTO skill_collection_placement_type (collection_id, placement_type_id)  VALUES (?, ?)');
            $stm->bindParam(1, $collectionId);
            $stm->bindParam(2, $placementTypeId);
            $stm->execute();
        } catch (Exception $e) {}
    }

    /**
     * @param int $collectionId
     * @return array
     */
    public function findPlacementTypes($collectionId)
    {
        $arr = array();
        try {
            $stm = $this->getDb()->prepare('SELECT placement_type_id FROM skill_collection_placement_type WHERE collection_id = ?');
            $stm->bindParam(1, $collectionId);
            $stm->execute();
            foreach($stm as $row) {
                $arr[] = $row->placement_type_id;
            }
        } catch (Exception $e) {}
        return $arr;
    }


    // Link to subjects

    /**
     * @param int $subjectId
     * @param int $collectionId
     * @return boolean
     */
    public function hasSubject($subjectId, $collectionId)
    {
        try {
            $stm = $this->getDb()->prepare('SELECT * FROM skill_collection_subject WHERE subject_id = ? AND collection_id = ?');
            $stm->bindParam(1, $subjectId);
            $stm->bindParam(2, $collectionId);
            $stm->execute();
            return ($stm->rowCount() > 0);
        } catch (Exception $e) {}
        return false;
    }

    /**
     * @param int $subjectId
     * @param int $collectionId (optional) If null all are to be removed
     */
    public function removeSubject($subjectId, $collectionId = null)
    {
        try {
            $stm = $this->getDb()->prepare('DELETE FROM skill_collection_subject WHERE subject_id = ?');
            $stm->bindParam(1, $subjectId);
            if ($collectionId) {
                $stm = $this->getDb()->prepare('DELETE FROM skill_collection_subject WHERE subject_id = ? AND collection_id = ?');
                $stm->bindParam(1, $subjectId);
                $stm->bindParam(2, $collectionId);
            }
            $stm->execute();
        } catch (Exception $e) {}
    }

    /**
     * @param int $subjectId
     * @param int $collectionId
     */
    public function addSubject($subjectId, $collectionId)
    {
        try {
            if ($this->hasSubject($subjectId, $collectionId)) return;
            $stm = $this->getDb()->prepare('INSERT INTO skill_collection_subject (subject_id, collection_id)  VALUES (?, ?)');
            $stm->bindParam(1, $subjectId);
            $stm->bindParam(2, $collectionId);
            $stm->execute();
        } catch (Exception $e) {}
    }




}