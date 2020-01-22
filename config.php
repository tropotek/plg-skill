<?php
$config = \App\Config::getInstance();

/** @var \Composer\Autoload\ClassLoader $composer */
$composer = $config->getComposer();
if ($composer)
    $composer->add('Skill\\', dirname(__FILE__));

$routes = $config->getRouteCollection();
if (!$routes) return;


$params = array();

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

$routes->add('skill-entry-results-staff', new \Tk\Routing\Route('/staff/{subjectCode}/entryResults.html', 'Skill\Controller\Report\StudentResults::doDefault', $params));
$routes->add('skill-entry-report', new \Tk\Routing\Route('/staff/{subjectCode}/collectionReport.html', 'Skill\Controller\Report\CollectionReport::doDefault', $params));
$routes->add('skill-historic-report-all', new \Tk\Routing\Route('/staff/{subjectCode}/historicReportAll.html', 'Skill\Controller\Report\HistoricReportAll::doDefault', $params));
$routes->add('skill-historic-report', new \Tk\Routing\Route('/staff/{subjectCode}/historicReport.html', 'Skill\Controller\Report\HistoricReport::doDefault', $params));
$routes->add('skill-date-average-report', new \Tk\Routing\Route('/staff/{subjectCode}/dateAverageReport.html', 'Skill\Controller\Report\DateAverageReport::doDefault', $params));
$routes->add('skill-item-average-report', new \Tk\Routing\Route('/staff/{subjectCode}/itemAverageReport.html', 'Skill\Controller\Report\ItemAverageReport::doDefault', $params));
$routes->add('skill-company-average-report', new \Tk\Routing\Route('/staff/{subjectCode}/companyAverageReport.html', 'Skill\Controller\Report\CompanyAverageReport::doDefault', $params));

// Student Only
$params = array('role' => array('student'));
$routes->add('skill-entry-edit-student', new \Tk\Routing\Route('/student/{subjectCode}/entryEdit.html', 'Skill\Controller\Entry\Edit::doDefault', $params));
$routes->add('skill-entry-view-student', new \Tk\Routing\Route('/student/{subjectCode}/entryView.html', 'Skill\Controller\Entry\View::doDefault', $params));
$routes->add('skill-entry-results-student', new \Tk\Routing\Route('/student/{subjectCode}/entryResults.html', 'Skill\Controller\Report\StudentResults::doDefault', $params));

// Guest Pages
$routes->add('guest-skill-entry-submit', new \Tk\Routing\Route('/inst/{institutionHash}/skillEdit.html', 'Skill\Controller\Entry\Edit::doPublicSubmission'));



