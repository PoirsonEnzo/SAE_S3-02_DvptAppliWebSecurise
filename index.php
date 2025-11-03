<?php

require_once __DIR__ . '/vendor/autoload.php';

use Service\dispatch\Dispatcher;
use Service\repository\DeefyRepository;

session_start();

DeefyRepository::setConfig(__DIR__ . '/db.config.ini');

$dispatcher = new Dispatcher();
$dispatcher->run();
