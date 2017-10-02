<?php
$config = \Tk\Config::getInstance();

/** @var \Composer\Autoload\ClassLoader $composer */
$composer = $config->getComposer();
if ($composer)
    $composer->add('Skill\\', dirname(__FILE__));

/** @var \Tk\Routing\RouteCollection $routes */
$routes = $config['site.routes'];

$params = array('role' => 'admin');
$routes->add('Skill Admin Settings', new \Tk\Routing\Route('/skill/adminSettings.html', 'Skill\Controller\SystemSettings::doDefault', $params));

$params = array('role' => array('client', 'staff'));
$routes->add('Skill Profile Settings', new \Tk\Routing\Route('/skill/profileSettings.html', 'Skill\Controller\ProfileSettings::doDefault', $params));


