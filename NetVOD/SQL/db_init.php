<?php

try {
    // Connexion PDO
    $pdo = new PDO("mysql:host=db;charset=utf8", "user", "password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //crée la base si elle n'existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS netvod CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $pdo->exec("USE netvod;");

    // Configuration initiale
    $pdo->exec("SET foreign_key_checks = 0;");
    $pdo->exec("SET NAMES utf8;");
    $pdo->exec("SET time_zone = '+00:00';");

    // -------------------- SUPPRESSION DES TABLES --------------------
    $tables = [
        "avis", "episodes_vus", "favoris", "episode", "serie",
        "profil2utilisateur", "Profil", "utilisateur",
        "genre", "public_cible"
    ];

    foreach ($tables as $t) {
        $pdo->exec("DROP TABLE IF EXISTS `$t`;");
    }

    // -------------------- CRÉATION DES TABLES --------------------
    $sql = [

        // GENRE
        "CREATE TABLE `genre` (
            `id_genre` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `nom_genre` VARCHAR(100) NOT NULL UNIQUE,
            PRIMARY KEY (`id_genre`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // PUBLIC CIBLE
        "CREATE TABLE `public_cible` (
            `id_public` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `nom_public` VARCHAR(100) NOT NULL UNIQUE,
            PRIMARY KEY (`id_public`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // UTILISATEUR
        "CREATE TABLE `utilisateur` (
            `id_utilisateur` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `mot_de_passe` VARCHAR(255) NOT NULL,
            `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `nb_profil` INT DEFAULT 0,
            PRIMARY KEY (`id_utilisateur`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // PROFIL
        "CREATE TABLE `Profil` (
            `id_profil` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_utilisateur` INT UNSIGNED NOT NULL,
            `prenom` VARCHAR(100) DEFAULT NULL,
            `nom` VARCHAR(100) DEFAULT NULL,
            `id_genre_prefere` INT UNSIGNED DEFAULT NULL,
            PRIMARY KEY (`id_profil`),
            FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur`(`id_utilisateur`) ON DELETE CASCADE,
            FOREIGN KEY (`id_genre_prefere`) REFERENCES `genre`(`id_genre`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // TABLE DE LIAISON profil2utilisateur
        "CREATE TABLE `profil2utilisateur` (
            `id_utilisateur` INT UNSIGNED NOT NULL,
            `id_profil` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id_utilisateur`, `id_profil`),
            FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur`(`id_utilisateur`) ON DELETE CASCADE,
            FOREIGN KEY (`id_profil`) REFERENCES `Profil`(`id_profil`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // SERIE
        "CREATE TABLE `serie` (
            `id_serie` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `titre` VARCHAR(128) NOT NULL,
            `descriptif` TEXT NOT NULL,
            `img` VARCHAR(256) NOT NULL,
            `annee` INT NOT NULL,
            `date_ajout` DATE NOT NULL,
            PRIMARY KEY (`id_serie`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // EPISODE
        "CREATE TABLE `episode` (
            `id_episode` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `numero` INT NOT NULL DEFAULT 1,
            `titre` VARCHAR(128) NOT NULL,
            `resume` TEXT DEFAULT NULL,
            `duree` INT NOT NULL DEFAULT 0,
            `file` VARCHAR(256) DEFAULT NULL,
            `serie_id` INT UNSIGNED DEFAULT NULL,
            PRIMARY KEY (`id_episode`),
            FOREIGN KEY (`serie_id`) REFERENCES `serie`(`id_serie`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // FAVORIS
        "CREATE TABLE `favoris` (
            `id_utilisateur` INT UNSIGNED NOT NULL,
            `id_serie` INT UNSIGNED NOT NULL,
            `date_ajout` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_utilisateur`,`id_serie`),
            FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur`(`id_utilisateur`) ON DELETE CASCADE,
            FOREIGN KEY (`id_serie`) REFERENCES `serie`(`id_serie`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // EPISODES VUS
        "CREATE TABLE `episodes_vus` (
            `id_utilisateur` INT UNSIGNED NOT NULL,
            `id_episode` INT UNSIGNED NOT NULL,
            `date_visionnage` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_utilisateur`,`id_episode`),
            FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur`(`id_utilisateur`) ON DELETE CASCADE,
            FOREIGN KEY (`id_episode`) REFERENCES `episode`(`id_episode`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // AVIS
        "CREATE TABLE `avis` (
            `id_utilisateur` INT UNSIGNED NOT NULL,
            `id_serie` INT UNSIGNED NOT NULL,
            `note` TINYINT UNSIGNED NOT NULL CHECK (`note` BETWEEN 1 AND 5),
            `commentaire` TEXT DEFAULT NULL,
            `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_utilisateur`,`id_serie`),
            FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur`(`id_utilisateur`) ON DELETE CASCADE,
            FOREIGN KEY (`id_serie`) REFERENCES `serie`(`id_serie`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];

    foreach ($sql as $stmt) {
        $pdo->exec($stmt);
    }

    $pdo->exec("SET foreign_key_checks = 1;");
    echo "✅ Base de données NetVOD initialisée avec succès !";

} catch (PDOException $e) {
    echo "❌ Erreur lors de l'initialisation : " . $e->getMessage();
}
?>
