<?php
namespace Skill\Db;

use Tk\Db\Tool;
use Tk\Db\Map\ArrayObject;
use Tk\DataMap\Db;
use Tk\DataMap\Form;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class ScaleMap extends \App\Db\Mapper
{

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->setTable('skill_scale');
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('uid'));
            $this->dbMap->addPropertyMap(new Db\Integer('collectionId', 'collection_id'));
            $this->dbMap->addPropertyMap(new Db\Text('name'));
            $this->dbMap->addPropertyMap(new Db\Text('description'));
            $this->dbMap->addPropertyMap(new Db\Decimal('value'));
            $this->dbMap->addPropertyMap(new Db\Integer('orderBy', 'order_by'));
            $this->dbMap->addPropertyMap(new Db\Date('modified'));
            $this->dbMap->addPropertyMap(new Db\Date('created'));
        }
        return $this->dbMap;
    }

    /**
     *
     * @return \Tk\DataMap\DataMap
     */
    public function getFormMap()
    {
        if (!$this->formMap) {
            $this->formMap = new \Tk\DataMap\DataMap();
            $this->formMap->addPropertyMap(new Form\Integer('id'), 'key');
            $this->formMap->addPropertyMap(new Form\Integer('uid'));
            $this->formMap->addPropertyMap(new Form\Integer('collectionId'));
            $this->formMap->addPropertyMap(new Form\Text('name'));
            $this->formMap->addPropertyMap(new Form\Text('description'));
            $this->formMap->addPropertyMap(new Form\Decimal('value'));
        }
        return $this->formMap;
    }

    /**
     * Find filtered records
     *
     * @param array $filter
     * @param Tool $tool
     * @return ArrayObject
     * @throws \Exception
     */
    public function findFiltered($filter = array(), $tool = null)
    {
        if (!$tool) $tool = \Tk\Db\Tool::create('orderBy');
        $from = sprintf('%s a ', $this->getDb()->quoteParameter($this->getTable()));
        $where = '';

        if (!empty($filter['keywords'])) {
            $kw = '%' . $this->getDb()->escapeString($filter['keywords']) . '%';
            $w = '';
            $w .= sprintf('a.name LIKE %s OR ', $this->getDb()->quote($kw));
            $w .= sprintf('a.description LIKE %s OR ', $this->getDb()->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) {
                $where .= '(' . substr($w, 0, -3) . ') AND ';
            }
        }

        if (!empty($filter['uid'])) {
            $where .= sprintf('a.uid = %s AND ', $this->quote($filter['uid']));
        }

        if (!empty($filter['collectionId'])) {
            $where .= sprintf('a.collection_id = %s AND ', (int)$filter['collectionId']);
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