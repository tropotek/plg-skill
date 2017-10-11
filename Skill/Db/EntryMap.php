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
class EntryMap extends \App\Db\Mapper
{

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->setTable('skill_entry');
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('collectionId', 'collection_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('placementId', 'placement_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('userId', 'user_id'));
            $this->dbMap->addPropertyMap(new Db\Text('title'));
            $this->dbMap->addPropertyMap(new Db\Text('assessor'));
            $this->dbMap->addPropertyMap(new Db\Integer('absent'));
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
            $this->formMap->addPropertyMap(new Form\Integer('placementId'));
            $this->formMap->addPropertyMap(new Form\Integer('userId'));
            $this->formMap->addPropertyMap(new Form\Text('title'));
            $this->formMap->addPropertyMap(new Form\Text('assessor'));
            $this->formMap->addPropertyMap(new Form\Text('absent'));
            $this->formMap->addPropertyMap(new Form\Text('status'));
            $this->formMap->addPropertyMap(new Form\Text('notes'));
        }
        return $this->formMap;
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
            $w .= sprintf('a.title LIKE %s OR ', $this->getDb()->quote($kw));
            $w .= sprintf('a.location LIKE %s OR ', $this->getDb()->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) {
                $where .= '(' . substr($w, 0, -3) . ') AND ';
            }
        }


        if (!empty($filter['courseId'])) {
            $where .= sprintf('a.course_id = %s AND ', (int)$filter['courseId']);
        }

        if (!empty($filter['collectionId'])) {
            $where .= sprintf('a.collection_id = %s AND ', (int)$filter['collectionId']);
        }

        if (!empty($filter['userId'])) {
            $where .= sprintf('a.user_id = %s AND ', (int)$filter['userId']);
        }

        if (!empty($filter['placementId'])) {
            $where .= sprintf('a.placement_id = %s AND ', (int)$filter['placementId']);
        }

        if (!empty($filter['title'])) {
            $where .= sprintf('a.title = %s AND ', $this->quote($filter['title']));
        }

        if (!empty($filter['status'])) {
            $w = $this->makeMultiQuery($filter['status'], 'a.status');
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




    /**
     * @param int $entryId
     * @param int $itemId
     * @return array|\stdClass
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
     */
    public function saveValue($entryId, $itemId, $value)
    {
        if ($this->hasValue($entryId, $itemId)) {
            $st = $this->getDb()->prepare('UPDATE skill_value SET value = ? WHERE entry_id = ? AND item_id = ? ');
//            $sql = sprintf('UPDATE %s SET value = %s WHERE entry_id = %s AND item_id = %s ',
//                $this->quoteTable('skill_value'), $this->quote($value), (int)$entryId, (int)$itemId);
//            $this->getDb()->query($sql);
        } else {
            $st = $this->getDb()->prepare('INSERT INTO skill_value (value, entry_id, item_id) VALUES (%s, %s, %s)');
//            $sql = sprintf('INSERT INTO %s (entry_id, item_id, value) VALUES (%s, %s, %s)',
//                $this->quoteTable('skill_value'), (int)$entryId, (int)$itemId, $this->quote($value));
        }
        $st->bindParam(1, $value);
        $st->bindParam(2, $entryId);
        $st->bindParam(3, $itemId);
        $st->execute();
    }

    /**
     * @param int $entryId
     * @param int $itemId
     */
    public function removeValue($entryId, $itemId)
    {
        $st = $this->getDb()->prepare('DELETE FROM skill_value WHERE entry_id = ? AND item_id = ?');
        $st->bindParam(1, $entryId);
        $st->bindParam(2, $itemId);
        $st->execute();

//        $query = sprintf('DELETE FROM %s WHERE entry_id = %d AND item_id = %d', $this->quoteTable('skill_value'), (int)$entryId, (int)$itemId);
//        $this->getDb()->exec($query);
    }

    /**
     * Does the value record exist
     *
     * @param int $entryId
     * @param int $itemId
     * @return bool
     */
    public function hasValue($entryId, $itemId)
    {
        $val = $this->findValue($entryId, $itemId);
        return $val != null;
    }


}