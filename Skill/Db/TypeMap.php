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
class TypeMap extends \App\Db\Mapper
{

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->setTable('skill_type');
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('profileId', 'profile_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('placementTypeId', 'placement_type_id'));
            $this->dbMap->addPropertyMap(new Db\Text('typeGroup', 'type_group'));
            $this->dbMap->addPropertyMap(new Db\Text('name'));
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
            $this->formMap->addPropertyMap(new Form\Integer('profileId'));
            $this->formMap->addPropertyMap(new Form\Integer('placementTypeId'));
            $this->formMap->addPropertyMap(new Form\Text('typeGroup'));
            $this->formMap->addPropertyMap(new Form\Text('name'));
        }
        return $this->formMap;
    }

    /**
     * @param string $name
     * @param string $typeGroup
     * @param int $profileId
     * @return null|Category|\Tk\Db\ModelInterface
     */
    public function findByName($name, $typeGroup, $profileId) {
        return $this->findFiltered(array('typeGroup' => $typeGroup, 'name' => $name, 'profileId' => $profileId))->current();
    }

    /**
     * @param int $profileId
     * @return array
     */
    public function findTypeGroups($profileId)
    {
        $st = $this->getDb()->prepare('SELECT DISTINCT type_group FROM skill_type WHERE profile_id = ? ORDER BY type_group');
        $st->execute(array($profileId));

        //$sql = sprintf('SELECT DISTINCT type_group FROM skill_type WHERE profile_id = %d ORDER BY type_group', (int)$profileId);
        //$st = $this->getDb()->query($sql);
        $arr = $st->fetchAll(\PDO::FETCH_COLUMN, 'type_group');
        return array_combine($arr, $arr);
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

        if (!empty($filter['placementTypeId'])) {
            $where .= sprintf('a.placement_type_id = %s AND ', (int)$filter['placementTypeId']);
        }

        if (!empty($filter['typeGroup'])) {
            $where .= sprintf('a.type_group = %s AND ', $this->quote($filter['typeGroup']));
        }

        if (!empty($filter['name'])) {
            $where .= sprintf('a.name = %s AND ', $this->quote($filter['name']));
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

}