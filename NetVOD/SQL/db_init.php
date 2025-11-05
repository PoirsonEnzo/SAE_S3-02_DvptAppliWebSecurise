<?php
try {
    // --- Connexion en root (pour créer la base et l'utilisateur) ---
    $rootPdo = new PDO("mysql:host=db;charset=utf8mb4", "root", "root");
    $rootPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- Création de la base si nécessaire ---
    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `netvod` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    // --- Créer l'utilisateur s'il n'existe pas, ou mettre à jour son mot de passe ---
    // CREATE USER IF NOT EXISTS ... fonctionne sur MySQL 8+
    $rootPdo->exec("CREATE USER IF NOT EXISTS 'user'@'%' IDENTIFIED BY 'password';");
    // Si l'utilisateur existe déjà, on s'assure que son mot de passe est correct (ALTER USER)
    $rootPdo->exec("ALTER USER 'user'@'%' IDENTIFIED BY 'password';");

    // --- Accorder les droits sur la base NetVOD ---
    $rootPdo->exec("GRANT ALL PRIVILEGES ON `netvod`.* TO 'user'@'%';");
    $rootPdo->exec("FLUSH PRIVILEGES;");

    // --- Connexion avec l'utilisateur 'user' à la base NetVOD ---
    $pdo = new PDO("mysql:host=db;dbname=netvod;charset=utf8mb4", "user", "password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- Configuration initiale ---
    $pdo->exec("SET foreign_key_checks = 0;");
    $pdo->exec("SET NAMES utf8mb4;");
    $pdo->exec("SET time_zone = '+00:00';");

    // --- Suppression des anciennes tables ---
    $tables = [
        "commentaire", "en_cours", "visionnees", "favoris",
        "episode", "public2serie", "genre2serie",
        "public_cible", "genre", "serie",
        "profil", "utilisateur"
    ];

    foreach ($tables as $t) {
        $pdo->exec("DROP TABLE IF EXISTS `$t`;");
    }

    // --- Création des tables ---
    $sql = [

        // UTILISATEUR
        "CREATE TABLE `utilisateur` (
            `id_utilisateur` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `mot_de_passe` VARCHAR(255) NOT NULL,
            `num_carte` VARCHAR(255) NOT NULL,
            `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_utilisateur`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // PROFIL
        "CREATE TABLE `profil` (
            `id_profil` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `username` VARCHAR(100) NOT NULL,
            `nom` VARCHAR(100) DEFAULT NULL,
            `prenom` VARCHAR(100) DEFAULT NULL,
            `genre_prefere` VARCHAR(100) DEFAULT NULL,
            `id_utilisateur` VARCHAR(100) NOT NULL,
            PRIMARY KEY (`id_profil`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // SERIE
        "CREATE TABLE `serie` (
            `id_serie` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `titre_serie` VARCHAR(128) NOT NULL,
            `descriptif` TEXT DEFAULT NULL,
            `img` VARCHAR(256) DEFAULT NULL,
            `annee` YEAR NOT NULL,
            `date_ajout` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_serie`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // GENRE
        "CREATE TABLE `genre` (
            `id_genre` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `libelle` VARCHAR(100) NOT NULL UNIQUE,
            PRIMARY KEY (`id_genre`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // genre2serie
        "CREATE TABLE `genre2serie` (
            `id_serie` INT UNSIGNED NOT NULL,
            `id_genre` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id_serie`, `id_genre`),
            FOREIGN KEY (`id_serie`) REFERENCES `serie`(`id_serie`) ON DELETE CASCADE,
            FOREIGN KEY (`id_genre`) REFERENCES `genre`(`id_genre`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // TYPE_PUBLIC
        "CREATE TABLE `public_cible` (
            `id_public` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `libelle` VARCHAR(100) NOT NULL UNIQUE,
            PRIMARY KEY (`id_public`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // public2serie
        "CREATE TABLE `public2serie` (
            `id_serie` INT UNSIGNED NOT NULL,
            `id_public` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id_serie`, `id_public`),
            FOREIGN KEY (`id_serie`) REFERENCES `serie`(`id_serie`) ON DELETE CASCADE,
            FOREIGN KEY (`id_public`) REFERENCES `public_cible`(`id_public`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // EPISODE
        "CREATE TABLE `episode` (
            `id_episode` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `numero_episode` INT NOT NULL,
            `titre` VARCHAR(128) NOT NULL,
            `resume` TEXT DEFAULT NULL,
            `duree` INT NOT NULL DEFAULT 0,
            `fichier` VARCHAR(256) DEFAULT NULL,
            `img` VARCHAR(256) DEFAULT NULL,
            `id_serie` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id_episode`),
            FOREIGN KEY (`id_serie`) REFERENCES `serie`(`id_serie`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // FAVORIS
        "CREATE TABLE `favoris` (
            `id_profil` INT UNSIGNED NOT NULL,
            `id_serie` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id_profil`, `id_serie`),
            FOREIGN KEY (`id_profil`) REFERENCES `profil`(`id_profil`) ON DELETE CASCADE,
            FOREIGN KEY (`id_serie`) REFERENCES `serie`(`id_serie`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // VISIONNEES
        "CREATE TABLE `visionnees` (
            `id_profil` INT UNSIGNED NOT NULL,
            `id_episode` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id_profil`, `id_episode`),
            FOREIGN KEY (`id_profil`) REFERENCES `profil`(`id_profil`) ON DELETE CASCADE,
            FOREIGN KEY (`id_episode`) REFERENCES `episode`(`id_episode`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // EN_COURS
        "CREATE TABLE `en_cours` (
            `id_profil` INT UNSIGNED NOT NULL,
            `id_episode` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id_profil`, `id_episode`),
            FOREIGN KEY (`id_profil`) REFERENCES `profil`(`id_profil`) ON DELETE CASCADE,
            FOREIGN KEY (`id_episode`) REFERENCES `episode`(`id_episode`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // COMMENTAIRE
        "CREATE TABLE `commentaire` (
            `id_commentaire` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_profil` INT UNSIGNED NOT NULL,
            `id_episode` INT UNSIGNED NOT NULL,
            `texte` TEXT DEFAULT NULL,
            `note` TINYINT UNSIGNED NOT NULL CHECK (`note` BETWEEN 1 AND 5),
            PRIMARY KEY (`id_commentaire`),
            FOREIGN KEY (`id_profil`) REFERENCES `profil`(`id_profil`) ON DELETE CASCADE,
            FOREIGN KEY (`id_episode`) REFERENCES `episode`(`id_episode`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];

    foreach ($sql as $stmt) {
        $pdo->exec($stmt);
    }

    // -------------------- INSERTIONS INITIALES --------------------
    $inserts = [

        // Genres
        "INSERT INTO `episode` (`id_episode`, `numero_episode`, `titre`, `resume`, `duree`,`fichier`, `id_serie`) VALUES
        (1,    1,    'Le lac',    'Le lac se révolte ',    8,    'lake.mp4',    1),
        (2,    2,    'Le lac : les mystères de l\'eau trouble',    'Un grand mystère, l\'eau du lac est trouble. Jack trouvera-t-il la solution ?',    8,    'lake.mp4',    1),
        (3,    3,    'Le lac : les mystères de l\'eau sale',    'Un grand mystère, l\'eau du lac est sale. Jack trouvera-t-il la solution ?',    8,    'lake.mp4',    1),
        (4,    3,    'Le lac : les mystères de l\'eau chaude',    'Un grand mystère, l\'eau du lac est chaude. Jack trouvera-t-il la solution ?',    8,    'lake.mp4',    1),
        (5,    3,    'Le lac : les mystères de l\'eau froide',    'Un grand mystère, l\'eau du lac est froide. Jack trouvera-t-il la solution ?',    8,    'lake.mp4',    1),
        (6,    1,    'Eau calme',    'L\'eau coule tranquillement au fil du temps.',    15,    'water.mp4',    2),
        (7,    2,    'Eau calme 2',    'Le temps a passé, l\'eau coule toujours tranquillement.',    15,    'water.mp4',    2),
        (8,    3,    'Eau moins calme',    'Le temps des tourments est pour bientôt, l\'eau s\'agite et le temps passe.',    15,    'water.mp4',    2),
        (9,    4,    'la tempête',    'C\'est la tempête, l\'eau est en pleine agitation. Le temps passe mais rien n\'y fait. Jack trouvera-t-il la solution ?',    15,    'water.mp4',    2),
        (10,    5,    'Le calme après la tempête',    'La tempête est passée, l\'eau retrouve son calme. Le temps passe et Jack part en vacances.',    15,    'water.mp4',    2),
        (11,    1,    'les chevaux s\'amusent',    'Les chevaux s\'amusent bien, ils ont apportés les raquettes pour faire un tournoi de badmington.',    7,    'horses.mp4',    3),
        (12,    2,    'les chevals fous',    '- Oh regarde, des beaux chevals !!\r\n- non, des chevaux, des CHEVAUX !\r\n- oh, bin ça alors, ça ressemble drôlement à des chevals ?!!?',    7,    'horses.mp4',    3),
        (13,    3,    'les chevaux de l\'étoile noire',    'Les chevaux de l\'Etoile Noire débrquent sur terre et mangent toute l\'herbe !',    7,    'horses.mp4',    3),
        (14,    1,    'Tous à la plage',    'C\'est l\'été, tous à la plage pour profiter du soleil et de la mer.',    18,    'beach.mp4',    4),
        (15,    2,    'La plage le soir',    'A la plage le soir, il n\'y a personne, c\'est tout calme',    18,    'beach.mp4',    4),
        (16,    3,    'La plage le matin',    'A la plage le matin, il n\'y a personne non plus, c\'est tout calme et le jour se lève.',    18,    'beach.mp4',    4),
        (17,    1,    'champion de surf',    'Jack fait du surf le matin, le midi le soir, même la nuit. C\'est un pro.',    11,    'surf.mp4',    5),
        (18,    2,    'surf détective',    'Une planche de surf a été volée. Jack mène l\'enquête. Parviendra-t-il à confondre le brigand ?',    11,    'surf.mp4',    5),
        (19,    3,    'surf amitié',    'En fait la planche n\'avait pas été volée, c\'est Jim, le meilleur ami de Jack, qui lui avait fait une blague. Les deux amis partagent une menthe à l\'eau pour célébrer leur amitié sans faille.',    11,    'surf.mp4',    5),
        (20,    1,    'Ça roule, ça roule',    'Ça roule, ça roule toute la nuit. Jack fonce dans sa camionnette pour rejoindre le spot de surf.',    27,    'cars-by-night.mp4',    6),
        (21,    2,    'Ça roule, ça roule toujours',    'Ça roule la nuit, comme chaque nuit. Jim fonce avec son taxi, pour rejoindre Jack à la plage. De l\'eau a coulé sous les ponts. Le mystère du Lac trouve sa solution alors que les chevaux sont de retour après une virée sur l\'Etoile Noire.',    27,    'cars-by-night.mp4',    6);",

        // Publics
        "INSERT INTO `serie` (`id_serie`, `titre_serie`, `descriptif`, `img`, `annee`, `date_ajout`) VALUES
        (1,    'Le lac aux mystères',    'C est l histoire d un lac mystérieux et plein de surprises. La série, bluffante et haletante, nous entraine dans un labyrinthe d intrigues époustouflantes. A ne rater sous aucun prétexte !',    '',    2020,    '2022-10-30'),
        (2,    'L eau a coulé',    'Une série nostalgique qui nous invite à revisiter notre passé et à se remémorer tout ce qui s est passé depuis que tant d eau a coulé sous les ponts.',    '',    1907,    '2022-10-29'),
        (3,    'Chevaux fous',    'Une série sur la vie des chevals sauvages en liberté. Décoiffante.',    '',    2017,    '2022-10-31'),
        (4,    'A la plage',    'Le succès de l été 2021, à regarder sans modération et entre amis.',    '',    2021,    '2022-11-04'),
        (5,    'Champion',    'La vie trépidante de deux champions de surf, passionnés dès leur plus jeune age. Ils consacrent leur vie à ce sport. ',    '',    2022,    '2022-11-03'),
        (6,    'Une ville la nuit',    'C est beau une ville la nuit, avec toutes ces voitures qui passent et qui repassent. La série suit un livreur, un chauffeur de taxi, et un insomniaque. Tous parcourent la grande ville une fois la nuit venue, au volant de leur véhicule.',    '',    2017,    '2022-10-31');",


    ];

    foreach ($inserts as $stmt) {
        $pdo->exec($stmt);
    }



    $pdo->exec("SET foreign_key_checks = 1;");
    echo <<<PAGE
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>NetVOD</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<p>Base initialisée : pas d'erreur</p>
<a href="../"> Retour à l'accueil</a>
</body>
</html>
PAGE;

} catch (PDOException $e) {
    echo "❌ Erreur lors de l'initialisation : " . $e->getMessage();
}
?>