<?php
namespace Skill;

use Tk\Event\Dispatcher;


/**
 * Class Plugin
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Plugin extends \Tk\Plugin\Iface
{

    const ZONE_INSTITUTION = 'institution';
    const ZONE_COURSE_PROFILE = 'profile';
    const ZONE_COURSE = 'course';

    /**
     * A helper method to get the Plugin instance globally
     *
     * @return \Tk\Plugin\Iface
     */
    static function getInstance()
    {
        return \Tk\Config::getInstance()->getPluginFactory()->getPlugin('ems-skill');
    }

    /**
     * @return \App\PluginApi
     */
    public static function getPluginApi()
    {
        return \Tk\Config::getInstance()->getPluginApi();
    }


    // ---- \Tk\Plugin\Iface Interface Methods ----
    
    
    /**
     * Init the plugin
     *
     * This is called when the session first registers the plugin to the queue
     * So it is the first called method after the constructor.....
     *
     */
    function doInit()
    {
        include dirname(__FILE__) . '/config.php';

        // Register the plugin for the different client areas if they are to be enabled/disabled/configured by those roles.
        //$this->getPluginFactory()->registerZonePlugin($this, self::ZONE_INSTITUTION);
        //$this->getPluginFactory()->registerZonePlugin($this, self::ZONE_COURSE_PROFILE);
        $this->getPluginFactory()->registerZonePlugin($this, self::ZONE_COURSE);

        /** @var Dispatcher $dispatcher */
        $dispatcher = \Tk\Config::getInstance()->getEventDispatcher();
        $dispatcher->addSubscriber(new \Skill\Listener\SetupHandler());
    }
    
    /**
     * Activate the plugin, essentially
     * installing any DB and settings required to run
     * Will only be called when activating the plugin in the
     * plugin control panel
     *
     */
    function doActivate()
    {
        // Init Plugin Settings
        $config = \Tk\Config::getInstance();
        $db = \App\Factory::getDb();

        $migrate = new \Tk\Util\SqlMigrate($db);
        $migrate->setTempPath($config->getTempPath());
        $migrate->migrate(dirname(__FILE__) . '/sql');
        
        // TODO: Implement doActivate() method.

        // Init Settings
//        $data = \Tk\Db\Data::create($this->getName());
//        $data->set('plugin.title', 'Day One Skills');
//        $data->set('plugin.email', 'fvas-elearning@unimelb.edu.au');
//        $data->save();
    }

    /**
     * Deactivate the plugin removing any DB data and settings
     * Will only be called when deactivating the plugin in the
     * plugin control panel
     *
     */
    function doDeactivate()
    {
        // TODO: Implement doDeactivate() method.
        $db = \App\Factory::getDb();

        // Clear the data table of all plugin data
        $sql = sprintf('DELETE FROM %s WHERE %s LIKE %s', $db->quoteParameter(\Tk\Db\Data::$DB_TABLE), $db->quoteParameter('fkey'),
            $db->quote($this->getName().'%'));
        $db->query($sql);

        // Delete all tables.
        $tables = array('skill', 'skill_bundle', 'skill_bundle_has_placement', 'skill_group', 'skill_score', 'skill_set');
        foreach ($tables as $name) {
            $db->dropTable($name);
        }

        // Remove migration track
        $sql = sprintf('DELETE FROM %s WHERE %s LIKE %s', $db->quoteParameter(\Tk\Util\SqlMigrate::$DB_TABLE), $db->quoteParameter('path'),
            $db->quote('/plugin/' . $this->getName().'/%'));
        $db->query($sql);
        
        // Delete any setting in the DB
//        $data = \Tk\Db\Data::create($this->getName());
//        $data->clear();
//        $data->save();
    }

    /**
     * Get the course settings URL, if null then there is none
     *
     * @return string|\Tk\Uri|null
     */
    public function getZoneSettingsUrl($zoneName)
    {
        switch ($zoneName) {
            case self::ZONE_INSTITUTION:
                return \Tk\Uri::create('/skill/institutionSettings.html');
            case self::ZONE_COURSE_PROFILE:
                return \Tk\Uri::create('/skill/courseProfileSettings.html');
            case self::ZONE_COURSE:
                return \Tk\Uri::create('/skill/courseSettings.html');
        }
        return null;
    }

    /**
     * @return \Tk\Uri
     */
    public function getSettingsUrl()
    {
        return null;
        //return \Tk\Uri::create('/skill/adminSettings.html');
    }

}