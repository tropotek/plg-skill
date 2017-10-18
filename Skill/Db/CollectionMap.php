<?php
namespace Skill\Db;

use Tk\Db\Tool;
use Tk\Db\Map\ArrayObject;
use Tk\DataMap\Db;
use Tk\DataMap\Form;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CollectionMap extends \App\Db\Mapper
{

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->setTable('skill_collection');
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('profileId', 'profile_id'));
            $this->dbMap->addPropertyMap(new Db\Text('name'));
            $this->dbMap->addPropertyMap(new Db\Text('role'));
            $this->dbMap->addPropertyMap(new Db\Text('icon'));
            $this->dbMap->addPropertyMap(new Db\Text('color'));
            $this->dbMap->addPropertyMap(new Db\ArrayObject('available'));
            $this->dbMap->addPropertyMap(new Db\Boolean('active'));
            $this->dbMap->addPropertyMap(new Db\Boolean('gradable'));
            $this->dbMap->addPropertyMap(new Db\Boolean('viewGrade', 'view_grade'));
            $this->dbMap->addPropertyMap(new Db\Boolean('includeZero', 'include_zero'));
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
            $this->formMap->addPropertyMap(new Form\Integer('profileId'));
            $this->formMap->addPropertyMap(new Form\Text('name'));
            $this->formMap->addPropertyMap(new Form\Text('role'));
            $this->formMap->addPropertyMap(new Form\Text('icon'));
            $this->formMap->addPropertyMap(new Form\Text('color'));
            $this->formMap->addPropertyMap(new Form\Object('available'));
            $this->formMap->addPropertyMap(new Form\Boolean('active'));
            $this->formMap->addPropertyMap(new Form\Boolean('gradable'));
            $this->formMap->addPropertyMap(new Form\Boolean('viewGrade'));
            $this->formMap->addPropertyMap(new Form\Boolean('includeZero'));
            $this->formMap->addPropertyMap(new Form\Text('confirm'));
            $this->formMap->addPropertyMap(new Form\Text('instructions'));
            $this->formMap->addPropertyMap(new Form\Text('notes'));
        }
        return $this->formMap;
    }

    /**
     * @param string $name
     * @param int $profileId
     * @return null|Category|\Tk\Db\ModelInterface
     */
    public function findByName($name, $profileId)
    {
        return $this->findFiltered(array('name' => $name, 'profileId' => $profileId))->current();
    }

    /**
     * Find filtered records
     *
     * @param array $filter
     * @param Tool $tool
     * @return ArrayObject
     */
    public function findFiltered($filter = array(), $tool = null)
    {
        $from = sprintf('%s a ', $this->getDb()->quoteParameter($this->getTable()));
        $where = '';

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
            if ($w) {
                $where .= '(' . substr($w, 0, -3) . ') AND ';
            }
        }

        if (!empty($filter['profileId'])) {
            $where .= sprintf('a.profile_id = %s AND ', (int)$filter['profileId']);
        }

        if (isset($filter['parentId']) && $filter['parentId'] !== null) {
            $where .= sprintf('a.parent_id = %s AND ', (int)$filter['parentId']);
        }

        if (!empty($filter['name'])) {
            $where .= sprintf('a.name = %s AND ', $this->quote($filter['name']));
        }

        if (!empty($filter['color'])) {
            $where .= sprintf('a.color = %s AND ', $this->quote($filter['color']));
        }

        if (!empty($filter['gradable'])) {
            $where .= sprintf('a.gradable = %s AND ', (int)$filter['gradable']);
        }

        if (!empty($filter['viewGrade'])) {
            $where .= sprintf('a.view_grade = %s AND ', (int)$filter['viewGrade']);
        }

        if (!empty($filter['active'])) {
            $where .= sprintf('a.active = %s AND ', (int)$filter['active']);
        }

        // Find all collections that are enabled for the given placement statuses
        if (!empty($filter['available'])) {
            $w = $this->makeMultiQuery($filter['available'], 'd.available');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if ($where) {
            $where = substr($where, 0, -4);
        }

        $res = $this->selectFrom($from, $where, $tool);
        return $res;
    }


    // Link to placement types

    /**
     * @param int $collectionId
     * @param int $placementTypeId
     * @return boolean
     */
    public function hasPlacementType($collectionId, $placementTypeId)
    {
        $stm = $this->getDb()->prepare('SELECT * FROM skill_collection_placement_type WHERE collection_id = ? AND placement_type_id = ?');
        $stm->bindParam(1, $collectionId);
        $stm->bindParam(2, $placementTypeId);
        $stm->execute();
        return ($stm->rowCount() > 0);
    }

    /**
     * @param int $collectionId
     * @param int $placementTypeId (optional) If null all are to be removed
     */
    public function removePlacementType($collectionId, $placementTypeId = null)
    {
        $stm = $this->getDb()->prepare('DELETE FROM skill_collection_placement_type WHERE collection_id = ?');
        $stm->bindParam(1, $collectionId);
        if ($placementTypeId) {
            $stm = $this->getDb()->prepare('DELETE FROM skill_collection_placement_type WHERE collection_id = ? AND placement_type_id = ?');
            $stm->bindParam(1, $collectionId);
            $stm->bindParam(2, $placementTypeId);
        }
        $stm->execute();
    }

    /**
     * @param int $collectionId
     * @param int $placementTypeId
     */
    public function addPlacementType($collectionId, $placementTypeId)
    {
        $stm = $this->getDb()->prepare('INSERT INTO skill_collection_placement_type (collection_id, placement_type_id)  VALUES (?, ?)');
        $stm->bindParam(1, $collectionId);
        $stm->bindParam(2, $placementTypeId);
        $stm->execute();
    }

    /**
     * @param int $collectionId
     * @return array
     */
    public function findPlacementTypes($collectionId)
    {
        $stm = $this->getDb()->prepare('SELECT placement_type_id FROM skill_collection_placement_type WHERE collection_id = ?');
        $stm->bindParam(1, $collectionId);
        $stm->execute();
        $arr = array();
        foreach($stm as $row) {
            $arr[] = $row->placement_type_id;
        }
        return $arr;
    }






    public function findCourseAverage($collectionId, $courseId)
    {
        $stm = $this->getDb()->prepare('SELECT * 
          FROM skill_value a, skill_entry b LEFT JOIN skill_item c ON (b.item_id = c.id) LEFT JOIN skill_domain d ON (c.domain_id = d.id)   
          WHERE a.id = b.entry_id AND collection_id = ? AND course_id = ? ');
        $stm->bindParam(1, $collectionId);
        $stm->execute();
        $arr = array();
        foreach($stm as $row) {
            $arr[] = $row->placement_type_id;
        }
        return $arr;
    }





}