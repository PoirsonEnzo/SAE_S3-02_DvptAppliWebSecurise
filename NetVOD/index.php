<?php

require_once __DIR__ . '/vendor/autoload.php';

use Service\dispatch\Dispatcher;
use Service\repository\DeefyRepository;

session_start();

// Détection de l'environnement (Webetu vs Docker/XAMPP)
$host = $_SERVER['HTTP_HOST'] ?? '';

if (strpos($host, 'webetu.iutnc.univ-lorraine.fr') !== false) {
    // En ligne (serveur Webetu)
    $configPath = __DIR__ . '/../config/db.config.ini';
} else {
    // En local (Docker)
    // __DIR__ = /var/www/html
    $configPath = __DIR__ . '/db.config.ini';
}

// Vérifie que le fichier de config existe, pour éviter les erreurs silencieuses
if (!file_exists($configPath)) {
    die("Erreur : le fichier de configuration est introuvable à l'emplacement : $configPath");
}

// Initialisation du dépôt avec la bonne config
DeefyRepository::setConfig($configPath);

// Lancement du routeur principal
$dispatcher = new Dispatcher();
$dispatcher->run();
