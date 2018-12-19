<?php
$config = \App\Config::getInstance();

/** @var \Composer\Autoload\ClassLoader $composer */
$composer = $config->getComposer();
if ($composer)
    $composer->add('Skill\\', dirname(__FILE__));

$routes = $config->getRouteCollection();
if (!$routes) return;


$params = array();

//$routes->add('client-collection-manager', new \Tk\Routing\Route('/client/skill/collectionManager.html', 'Skill\Controller\Collection\Manager::doDefault', $params));
//$routes->add('client-collection-edit', new \Tk\Routing\Route('/client/skill/collectionEdit.html', 'Skill\Controller\Collection\Edit::doDefault', $params));
//$routes->add('client-domain-manager', new \Tk\Routing\Route('/client/skill/domainManager.html', 'Skill\Controller\Domain\Manager::doDefault', $params));
//$routes->add('client-domain-edit', new \Tk\Routing\Route('/client/skill/domainEdit.html', 'Skill\Controller\Domain\Edit::doDefault', $params));
//$routes->add('client-category-manager', new \Tk\Routing\Route('/client/skill/categoryManager.html', 'Skill\Controller\Category\Manager::doDefault', $params));
//$routes->add('client-category-edit', new \Tk\Routing\Route('/client/skill/categoryEdit.html', 'Skill\Controller\Category\Edit::doDefault', $params));
//$routes->add('client-scale-manager', new \Tk\Routing\Route('/client/skill/scaleManager.html', 'Skill\Controller\Scale\Manager::doDefault', $params));
//$routes->add('client-scale-edit', new \Tk\Routing\Route('/client/skill/scaleEdit.html', 'Skill\Controller\Scale\Edit::doDefault', $params));
//$routes->add('client-item-manager', new \Tk\Routing\Route('/client/skill/itemManager.html', 'Skill\Controller\Item\Manager::doDefault', $params));
//$routes->add('client-item-edit', new \Tk\Routing\Route('/client/skill/itemEdit.html', 'Skill\Controller\Item\Edit::doDefault', $params));


// Staff Only
$routes->add('skill-collection-manager', new \Tk\Routing\Route('/staff/{subjectCode}/collectionManager.html', 'Skill\Controller\Collection\Manager::doDefault', $params));
$routes->add('staff-collection-edit', new \Tk\Routing\Route('/staff/{subjectCode}/collectionEdit.html', 'Skill\Controller\Collection\Edit::doDefault', $params));

$routes->add('staff-domain-manager', new \Tk\Routing\Route('/staff/{subjectCode}/domainManager.html', 'Skill\Controller\Domain\Manager::doDefault', $params));
$routes->add('staff-domain-edit', new \Tk\Routing\Route('/staff/{subjectCode}/domainEdit.html', 'Skill\Controller\Domain\Edit::doDefault', $params));
$routes->add('staff-category-manager', new \Tk\Routing\Route('/staff/{subjectCode}/categoryManager.html', 'Skill\Controller\Category\Manager::doDefault', $params));
$routes->add('staff-category-edit', new \Tk\Routing\Route('/staff/{subjectCode}/categoryEdit.html', 'Skill\Controller\Category\Edit::doDefault', $params));
$routes->add('staff-scale-manager', new \Tk\Routing\Route('/staff/{subjectCode}/scaleManager.html', 'Skill\Controller\Scale\Manager::doDefault', $params));
$routes->add('staff-scale-edit', new \Tk\Routing\Route('/staff/{subjectCode}/scaleEdit.html', 'Skill\Controller\Scale\Edit::doDefault', $params));
$routes->add('staff-item-manager', new \Tk\Routing\Route('/staff/{subjectCode}/itemManager.html', 'Skill\Controller\Item\Manager::doDefault', $params));
$routes->add('staff-item-edit', new \Tk\Routing\Route('/staff/{subjectCode}/itemEdit.html', 'Skill\Controller\Item\Edit::doDefault', $params));

$routes->add('skill-entry-manager', new \Tk\Routing\Route('/staff/{subjectCode}/entryManager.html', 'Skill\Controller\Entry\Manager::doDefault', $params));
$routes->add('skill-entry-edit', new \Tk\Routing\Route('/staff/{subjectCode}/entryEdit.html', 'Skill\Controller\Entry\Edit::doDefault', $params));
$routes->add('skill-entry-view', new \Tk\Routing\Route('/staff/{subjectCode}/entryView.html', 'Skill\Controller\Entry\View::doDefault', $params));

$routes->add('skill-entry-results-staff', new \Tk\Routing\Route('/staff/{subjectCode}/entryResults.html', 'Skill\Controller\Reports\StudentResults::doDefault', $params));
$routes->add('skill-entry-report', new \Tk\Routing\Route('/staff/{subjectCode}/collectionReport.html', 'Skill\Controller\Reports\CollectionReport::doDefault', $params));
$routes->add('skill-entry-report', new \Tk\Routing\Route('/staff/{subjectCode}/historicReport.html', 'Skill\Controller\Reports\HistoricReport::doDefault', $params));

//$routes->add('staff-collection-manager', new \Tk\Routing\Route('/staff/skill/collectionManager.html', 'Skill\Controller\Collection\Manager::doDefault', $params));
//$routes->add('staff-collection-edit', new \Tk\Routing\Route('/staff/skill/collectionEdit.html', 'Skill\Controller\Collection\Edit::doDefault', $params));
//$routes->add('staff-domain-manager', new \Tk\Routing\Route('/staff/skill/domainManager.html', 'Skill\Controller\Domain\Manager::doDefault', $params));
//$routes->add('staff-domain-edit', new \Tk\Routing\Route('/staff/skill/domainEdit.html', 'Skill\Controller\Domain\Edit::doDefault', $params));
//$routes->add('staff-category-manager', new \Tk\Routing\Route('/staff/skill/categoryManager.html', 'Skill\Controller\Category\Manager::doDefault', $params));
//$routes->add('staff-category-edit', new \Tk\Routing\Route('/staff/skill/categoryEdit.html', 'Skill\Controller\Category\Edit::doDefault', $params));
//$routes->add('staff-scale-manager', new \Tk\Routing\Route('/staff/skill/scaleManager.html', 'Skill\Controller\Scale\Manager::doDefault', $params));
//$routes->add('staff-scale-edit', new \Tk\Routing\Route('/staff/skill/scaleEdit.html', 'Skill\Controller\Scale\Edit::doDefault', $params));
//$routes->add('staff-item-manager', new \Tk\Routing\Route('/staff/skill/itemManager.html', 'Skill\Controller\Item\Manager::doDefault', $params));
//$routes->add('staff-item-edit', new \Tk\Routing\Route('/staff/skill/itemEdit.html', 'Skill\Controller\Item\Edit::doDefault', $params));

// Student Only
$params = array('role' => array('student'));
$routes->add('skill-entry-edit-student', new \Tk\Routing\Route('/student/{subjectCode}/entryEdit.html', 'Skill\Controller\Entry\Edit::doDefault', $params));
$routes->add('skill-entry-view-student', new \Tk\Routing\Route('/student/{subjectCode}/entryView.html', 'Skill\Controller\Entry\View::doDefault', $params));
$routes->add('skill-entry-results-student', new \Tk\Routing\Route('/student/{subjectCode}/entryResults.html', 'Skill\Controller\Reports\StudentResults::doDefault', $params));


// Guest Pages
$routes->add('guest-skill-entry-submit', new \Tk\Routing\Route('/inst/{institutionHash}/skillEdit.html', 'Skill\Controller\Entry\Edit::doPublicSubmission'));
// Temp bridging page, remove after Aug 2018
//$routes->add('guest-goals-redirect', new \Tk\Routing\Route('/goals.html', 'Skill\Controller\Entry\Goals::doDefault'));



