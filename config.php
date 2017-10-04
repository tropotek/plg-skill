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

$routes->add('skill-collection-manager', new \Tk\Routing\Route('/skill/collectionManager.html', 'Skill\Controller\Collection\Manager::doDefault', $params));
$routes->add('skill-collection-edit', new \Tk\Routing\Route('/skill/collectionEdit.html', 'Skill\Controller\Collection\Edit::doDefault', $params));
$routes->add('skill-domain-manager', new \Tk\Routing\Route('/skill/domainManager.html', 'Skill\Controller\Domain\Manager::doDefault', $params));
$routes->add('skill-domain-edit', new \Tk\Routing\Route('/skill/domainEdit.html', 'Skill\Controller\Domain\Edit::doDefault', $params));
$routes->add('skill-category-manager', new \Tk\Routing\Route('/skill/categoryManager.html', 'Skill\Controller\Category\Manager::doDefault', $params));
$routes->add('skill-category-edit', new \Tk\Routing\Route('/skill/categoryEdit.html', 'Skill\Controller\Category\Edit::doDefault', $params));


