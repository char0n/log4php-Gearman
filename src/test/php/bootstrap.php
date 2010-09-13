<?php
date_default_timezone_set('Europe/Bratislava');  

$basePath = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
require_once $basePath.'lib'.DIRECTORY_SEPARATOR.'log4php'.DIRECTORY_SEPARATOR.'Logger.php';
require_once $basePath.'src'.DIRECTORY_SEPARATOR.'main'.DIRECTORY_SEPARATOR.'php'.DIRECTORY_SEPARATOR.'appenders'.DIRECTORY_SEPARATOR.'LoggerAppenderGearman.php';
/*
require_once $basePath.'lib'.DIRECTORY_SEPARATOR.'phpunit'.DIRECTORY_SEPARATOR.'PHPUnit'.DIRECTORY_SEPARATOR.'Framework'.DIRECTORY_SEPARATOR.'TestCase.php';
require_once $basePath.'lib'.DIRECTORY_SEPARATOR.'phpunit'.DIRECTORY_SEPARATOR.'PHPUnit'.DIRECTORY_SEPARATOR.'Framework'.DIRECTORY_SEPARATOR.'TestSuite.php';
*/
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'PHPUnit/Util/Filter.php';
?>