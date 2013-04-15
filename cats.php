<?php


require 'app/Mage.php';
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

Mage::app();

$factory = Mage::getModel('tcimport/import');
$process = $factory::init('category');
$process->run();