<?php
// db_init.php

try {
    $pdo = new PDO("mysql:host=db;dbname=OnSie;charset=utf8", "user", "password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Configuration initiale
    $pdo->exec("SET foreign_key_checks = 0;");
    $pdo->exec("SET NAMES utf8;");
    $pdo->exec("SET time_zone = '+00:00';");
    $pdo->exec("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';");

    $sqlStatements = [

        // -------------------- TABLE ARTICLE --------------------
        "DROP TABLE IF EXISTS `ARTICLE`;",
        "CREATE TABLE `ARTICLE` (
            `NUMART` INT(4) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `TITRE` VARCHAR(150),
            `RESUME` VARCHAR(500),
            `TYPEARTICLE` VARCHAR(50)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

        // -------------------- TABLE CHERCHEUR --------------------
        "DROP TABLE IF EXISTS `CHERCHEUR`;",
        "CREATE TABLE `CHERCHEUR` (
            `NUMCHER` INT(4) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `EMAIL` VARCHAR(50),
            `NOMCHERCHEUR` VARCHAR(50),
            `PRENOMCHERCHEUR` VARCHAR(50),
            `URLCHERCHEUR` VARCHAR(50)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

        // -------------------- TABLE LABORATOIRE --------------------
        "DROP TABLE IF EXISTS `LABORATOIRE`;",
        "CREATE TABLE `LABORATOIRE` (
            `NUMLABO` INT(4) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `NOMLABO` VARCHAR(150),
            `SIGLELABO` VARCHAR(50),
            `ADRESSELABO` VARCHAR(300),
            `URLLABO` VARCHAR(50)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

        // -------------------- TABLE SUPPORT --------------------
        "DROP TABLE IF EXISTS `SUPPORT`;",
        "CREATE TABLE `SUPPORT` (
            `NOMSUPPORT` VARCHAR(50) PRIMARY KEY,
            `TYPESUPPORT` VARCHAR(50)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

        // -------------------- TABLE ANNOTATION --------------------
        "DROP TABLE IF EXISTS `ANNOTATION`;",
        "CREATE TABLE `ANNOTATION` (
            `LIBELLE` VARCHAR(50) PRIMARY KEY
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

        // -------------------- TABLE ECRIRE --------------------
        "DROP TABLE IF EXISTS `ECRIRE`;",
        "CREATE TABLE `ECRIRE` (
            `NUMCHER` INT(4),
            `NUMART` INT(4),
            PRIMARY KEY (`NUMCHER`, `NUMART`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

        // -------------------- TABLE PUBLIER --------------------
        "DROP TABLE IF EXISTS `PUBLIER`;",
        "CREATE TABLE `PUBLIER` (
            `NUMART` INT(4),
            `NOMSUPPORT` VARCHAR(50),
            `ANNEE_PUBLICATION` INT,
            PRIMARY KEY (`NUMART`, `NOMSUPPORT`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

        // -------------------- TABLE TRAVAILLER --------------------
        "DROP TABLE IF EXISTS `TRAVAILLER`;",
        "CREATE TABLE `TRAVAILLER` (
            `NUMCHER` INT(4),
            `NUMLABO` INT(4),
            PRIMARY KEY (`NUMCHER`, `NUMLABO`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

        // -------------------- TABLE ANNOTER --------------------
        "DROP TABLE IF EXISTS `ANNOTER`;",
        "CREATE TABLE `ANNOTER` (
            `NUMCHER` INT(4),
            `NUMART` INT(4),
            `LIBELLE` VARCHAR(50),
            PRIMARY KEY (`NUMCHER`, `NUMART`, `LIBELLE`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

        // -------------------- TABLE NOTER --------------------
        "DROP TABLE IF EXISTS `NOTER`;",
        "CREATE TABLE `NOTER` (
            `NUMCHER` INT(4),
            `NUMART` INT(4),
            `NOTE` INT,
            PRIMARY KEY (`NUMCHER`, `NUMART`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
    ];

    // Exécution des créations de tables
    foreach ($sqlStatements as $stmt) {
        $pdo->exec($stmt);
    }

    // -------------------- INSERTIONS --------------------
    $inserts = [

        // ARTICLE
        "INSERT INTO ARTICLE VALUES
        (1,'Adding Structure to Unstructured Data','We develop a new schema for unstructured data. Traditional schemas resemble the type systems of programming languages.','Long'),
        (2,'A User-centric Framework for Accessing Biological Sources and Tools','We study the representation and querying of XML with incomplete information...','Long'),
        (3,'PDiffView: Viewing the Difference in Provenance of Workflow Results','Provenance Difference Viewer (PDiffView) is a prototype...','Court'),
        (4,'Automata and Logics for Words and Trees over an Infinite Alphabet','In a data word or a data tree each position carries a label...','Long'),
        (5,'Representing and Querying XML with Incomplete Information','We study the representation and querying of XML with incomplete information...','Long'),
        (6,'The TLA+ Proof System: Building a Heterogeneous Verification Platform','Model checking has proved to be an efficient technique...','Long'),
        (7,'Partial reversal acyclicity','Partial Reversal (PR) is a link reversal algorithm...','Court'),
        (8,'Reliably Detecting Connectivity Using Local Graph Traits','This paper studies local graph traits...','Long'),
        (9,'Generalized Universality','This paper presents, two decades after k-set consensus was introduced...','Long'),
        (10,'Transactional Memory: Glimmer of a Theory','Transactional memory (TM) is a promising paradigm...','Tutoriel');",

        // CHERCHEUR
        "INSERT INTO CHERCHEUR VALUES
        (1,'peter@cis.upenn.edu','Buneman','Peter','http://homepages.inf.ed.ac.uk/opb/'),
        (2,'cohen@lri.fr','Cohen-Boulakia','Sarah','http://www.lri.fr/~cohen'),
        (3,'chris@lri.fr','Froidevaux','Christine','http://www.lri.fr/~chris/'),
        (4,'susan@cis.upenn.edu','Davidson','Susan','http://www.cis.upenn.edu/~susan/'),
        (5,'luc.segoufin@inria.fr','Segoufin','Luc','http://www-rocq.inria.fr/~segoufin/'),
        (6,'lamport@microsoft.com','Lamport','Leslie','http://www.lamport.org/'),
        (7,'lynch@theory.csail.mit.edu','Lynch','Nancy','http://people.csail.mit.edu/lynch/'),
        (8,'Rachid.Guerraoui@epfl.ch','Guerraoui','Rachid','http://lpdwww.epfl.ch/rachid/');",

        // LABORATOIRE
        "INSERT INTO LABORATOIRE VALUES
        (1,'Laboratory for Foundations of Computer Science','LFCS','LFCS, School of Informatics Crichton Street Edinburgh EH8 9LE',NULL),
        (2,'Department of Computer and Information Science University of Pennsylvania','CIS','305 Levine/572 Levine North Department of Computer and Information Science  University of Pennsylvania  Levine Hall  3330 Walnut Street  Philadelphia, PA 19104-6389',NULL),
        (3,'Laboratoire de Recherche en Informatique','LRI','Bât 490 Université Paris-Sud 11 91405 Orsay Cedex France',NULL),
        (4,'Laboratoire Spécification et Vérification','LSV','ENS de Cachan, 61 avenue du Président Wilson, 94235 CACHAN Cedex, FRANCE',NULL),
        (5,'Distributed Programming Laboratory','LPD','Bat INR 326 Station 14 1015 Lausanne Switzerland','http://lpd.epfl.ch/site/'),
        (6,'Theory of Distributed Systems','TDS','32 Vassar Street','http://groups.csail.mit.edu/tds/'),
        (7,'Microsoft Corporation','Microsoft','1065 La Avenida Mountain View, CA 94043, USA.','http://research.microsoft.com'),
        (8,'INRIA Saclay - Ile-de-France','INRIA Saclay','Domaine de Voluceau Rocquencourt - BP 105 78153 Le Chesnay Cedex, France','http://www.inria.fr/centre/saclay');",

        // SUPPORT
        "INSERT INTO SUPPORT VALUES
        ('ICDT','Conference'),('DILS','Conference'),('TODS','Journal'),('VLDB','Journal'),
        ('CLS','Conference'),('CAV','Conference'),('CONCUR','Conference'),
        ('OPODIS','Conference'),('PODC','Conference'),('ICTAC','Conference');",

        // ANNOTATION
        "INSERT INTO ANNOTATION VALUES
        ('data'),('bio'),('workflow'),('theory'),('XML'),('Concurrency'),('TLA'),('Consensus'),('Graph'),('Reliability');",

        // ECRIRE
        "INSERT INTO ECRIRE VALUES
        (1,1),(2,3),(4,1),(4,2),(2,2),(3,2),(5,4),(5,5),(8,9),(8,10),(7,8),(7,7),(6,6);",

        // PUBLIER
        "INSERT INTO PUBLIER VALUES
        (1,'ICDT',1997),(2,'DILS',2005),(5,'TODS',2006),(3,'VLDB',2009),
        (4,'CLS',2006),(6,'ICTAC',2009),(7,'PODC',2011),(8,'OPODIS',2010),
        (9,'CONCUR',2011),(10,'CAV',2010);",

        // TRAVAILLER
        "INSERT INTO TRAVAILLER VALUES
        (1,1),(4,2),(1,2),(2,3),(3,3),(5,4),(5,8),(6,7),(7,6),(8,5),(8,8);",

        // ANNOTER
        "INSERT INTO ANNOTER VALUES
        (5,1,'data'),(1,2,'bio'),(1,1,'XML'),(1,3,'workflow'),(2,4,'theory'),
        (6,6,'TLA'),(7,9,'Consensus'),(7,10,'Concurrency'),(8,7,'Graph'),(8,8,'Reliability');",

        // NOTER
        "INSERT INTO NOTER VALUES
        (5,1,4),(5,4,1),(5,2,4),(5,3,5),(5,5,1),(1,2,2),(1,4,1),(2,2,2),(2,3,1),
        (8,1,1),(8,4,4),(8,2,2),(8,3,1),(8,5,5),(6,2,3),(6,4,4);"
    ];

    foreach ($inserts as $stmt) {
        $pdo->exec($stmt);
    }

    $pdo->exec("SET foreign_key_checks = 1;");
    echo "✅ Base de données initialisée avec succès !";

} catch (PDOException $e) {
    echo "❌ Erreur lors de l'initialisation : " . $e->getMessage();
}
?>
