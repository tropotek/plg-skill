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
class CategoryMap extends \App\Db\Mapper
{

    /**
     * @return \Tk\DataMap\DataMap
     * @throws \Tk\Db\Exception
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->setTable('skill_category');
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('uid'));
            $this->dbMap->addPropertyMap(new Db\Integer('collectionId', 'collection_id'));
            $this->dbMap->addPropertyMap(new Db\Text('name'));
            $this->dbMap->addPropertyMap(new Db\Text('label'));
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
            $this->formMap->addPropertyMap(new Form\Text('name'));
            $this->formMap->addPropertyMap(new Form\Text('label'));
            $this->formMap->addPropertyMap(new Form\Text('description'));
            $this->formMap->addPropertyMap(new Form\Boolean('publish'));
        }
        return $this->formMap;
    }
    /**
     * @param array|\Tk\Db\Filter $filter
     * @param Tool $tool
     * @return ArrayObject|Category[]
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

        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = %s AND ', $this->quote($filter['name']));
        }

        if (!empty($filter['label'])) {
            $filter->appendWhere('a.label = %s AND ', $this->quote($filter['label']));
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