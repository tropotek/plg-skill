<?php
namespace Skill\Controller;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class ProfileSettings extends \Skill\Controller\Collection\Manager
{

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('Skill Profile Setup');
    }


}