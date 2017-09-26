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
            $this->dbMap->addPropertyMap(new Db\Integer('profileId', 'profile_id'));
            $this->dbMap->addPropertyMap(new Db\Integer('userId', 'user_id'));
            $this->dbMap->addPropertyMap(new Db\Text('title'));
            $this->dbMap->addPropertyMap(new Db\Text('type'));

            $this->dbMap->addPropertyMap(new Db\Text('status'));

            $this->dbMap->addPropertyMap(new Db\Text('location'));
            $this->dbMap->addPropertyMap(new Db\Text('praiseComment', 'praise_comment'));
            $this->dbMap->addPropertyMap(new Db\Text('highlightComment', 'highlight_comment'));
            $this->dbMap->addPropertyMap(new Db\Text('improveComment', 'improve_comment'));
            $this->dbMap->addPropertyMap(new Db\Text('differentComment', 'different_comment'));

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
            $this->formMap->addPropertyMap(new Form\Integer('institutionId'));
            $this->formMap->addPropertyMap(new Form\Integer('userId'));
            $this->formMap->addPropertyMap(new Form\Text('title'));
            $this->formMap->addPropertyMap(new Form\Text('type'));

            $this->formMap->addPropertyMap(new Form\Text('status'));

            $this->formMap->addPropertyMap(new Form\Text('location'));
            $this->formMap->addPropertyMap(new Form\Text('praiseComment'));
            $this->formMap->addPropertyMap(new Form\Text('highlightComment'));
            $this->formMap->addPropertyMap(new Form\Text('improveComment'));
            $this->formMap->addPropertyMap(new Form\Text('differentComment'));

            $this->formMap->addPropertyMap(new Form\Text('notes'));
        }
        return $this->formMap;
    }

    public function findSelfAssessment($userId)
    {
        return $this->findFiltered(array(
            'userId' => $userId,
            'type' => Entry::TYPE_SELF_ASSESSMENT
        ))->current();
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

        if (!empty($filter['profileId'])) {
            $where .= sprintf('a.profile_id = %s AND ', (int)$filter['profileId']);
        }

        if (!empty($filter['userId'])) {
            $where .= sprintf('a.user_id = %s AND ', (int)$filter['userId']);
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

        if (!empty($filter['type'])) {
            $w = $this->makeMultiQuery($filter['type'], 'a.type');
            if ($w) {
                $where .= '('. $w . ') AND ';
            }
        }

        if (!empty($filter['notType'])) {
            $w = $this->makeMultiQuery($filter['notType'], 'a.type', 'AND', '!=');
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
        $where = '';
        if ($itemId) {
            $where .= sprintf(' AND a.item_id = %s ', (int)$itemId);
        }

        $sql = sprintf('SELECT * FROM %s a WHERE a.entry_id = %d %s',
            $this->quoteTable('skill_value'), (int)$entryId, $where);

        $res = $this->getDb()->query($sql);
        $arr = $res->fetchAll();
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
            $sql = sprintf('UPDATE %s SET value = %s WHERE entry_id = %s AND item_id = %s ',
                $this->quoteTable('skill_value'), $this->quote($value), (int)$entryId, (int)$itemId);
        } else {
            $sql = sprintf('INSERT INTO %s (entry_id, item_id, value) VALUES (%s, %s, %s)',
                $this->quoteTable('skill_value'), (int)$entryId, (int)$itemId, $this->quote($value));
        }
        $this->getDb()->query($sql);
    }

    /**
     * @param int $entryId
     * @param int $itemId
     */
    public function removeValue($entryId, $itemId)
    {
        $query = sprintf('DELETE FROM %s WHERE entry_id = %d AND item_id = %d', $this->quoteTable('skill_value'), (int)$entryId, (int)$itemId);
        $this->getDb()->exec($query);
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

    // /////////////////// Selected Items ///////////////////
    // TODO: Should these be renamed to hasSelectedItem(), etc...

    /**
     * @param int $entryId
     * @param int $itemId
     * @return boolean
     */
    public function hasItem($entryId, $itemId)
    {
        $sql = sprintf('SELECT * FROM %s WHERE entry_id = %d AND item_id = %d', $this->quoteTable('skill_selected'), (int)$entryId, (int)$itemId);
        return ($this->getDb()->query($sql)->rowCount() > 0);
    }

    /**
     * @param int $entryId
     * @param int $itemId
     */
    public function removeItem($entryId, $itemId = null)
    {
        if ($itemId !== null) {
            $query = sprintf('DELETE FROM %s WHERE entry_id = %d AND item_id = %d', $this->quoteTable('skill_selected'), (int)$entryId, (int)$itemId);
        } else {
            $query = sprintf('DELETE FROM %s WHERE entry_id = %d ', $this->quoteTable('skill_selected'), (int)$entryId);
        }
        $this->getDb()->exec($query);
    }

    /**
     * @param int $entryId
     * @param int $itemId
     */
    public function addItem($entryId, $itemId)
    {
        if ($this->hasItem($entryId, $itemId)) return;
        $query = sprintf('INSERT INTO %s (entry_id, item_id)  VALUES (%d, %d) ', $this->quoteTable('skill_selected'), (int)$entryId, (int)$itemId);
        $this->getDb()->exec($query);
    }

}