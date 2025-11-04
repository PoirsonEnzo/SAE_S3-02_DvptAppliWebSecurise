<?php

namespace Service\action;

use Service\repository\DeefyRepository;

class AfficherSerie extends Action
{
    public function getResult(): string
    {
        // Vérifie la connexion
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        // Vérifie qu’un ID de série est passé
        if (!isset($_GET['id'])) {
            return "<p>Aucune série sélectionnée.</p>";
        }

        $idSerie = (int) $_GET['id'];
        $pdo = DeefyRepository::getInstance()->getPDO();

        // --- Infos sur la série ---
        $stmt = $pdo->prepare("
            SELECT S.id_serie, S.titre, S.descriptif, 
                   S.annee, S.date_ajout, COUNT(E.id_episode) AS nbepisodes
            FROM serie S 
            LEFT JOIN episode E ON S.id_serie = E.serie_id
            WHERE S.id_serie = ?
            GROUP BY S.id_serie
        ");
        $stmt->execute([$idSerie]);
        $serie = $stmt->fetch();

        if (!$serie) {
            return "<p>❌ Série introuvable.</p>";
        }

        // --- Sécurisation ---
        $titre = htmlspecialchars($serie['titre']);
        $desc = htmlspecialchars($serie['descriptif']);
        $annee = htmlspecialchars($serie['annee']);
        $dateAjout = htmlspecialchars($serie['date_ajout']);
        $nbEpisodes = (int) $serie['nbepisodes'];

        // --- HTML principal ---
        $html = "
        <div class='serie-details'>
            <h2>{$titre}</h2>
            <p><strong>Description :</strong> {$desc}</p>
            <p><strong>Année de sortie :</strong> {$annee}</p>
            <p><strong>Date d’ajout :</strong> {$dateAjout}</p>
            <p><strong>Nombre d’épisodes :</strong> {$nbEpisodes}</p>
        </div>
        <h3>Liste des épisodes</h3>
        ";

        // --- Récupération des épisodes ---
        $stmt2 = $pdo->prepare("
            SELECT id_episode, numero, titre, duree
            FROM episode
            WHERE serie_id = ?
            ORDER BY numero ASC
        ");
        $stmt2->execute([$idSerie]);
        $episodes = $stmt2->fetchAll();

        if (empty($episodes)) {
            $html .= "<p>Aucun épisode disponible pour cette série.</p>";
        } else {
            $html .= "<div class='episodes-grid'>";
            foreach ($episodes as $ep) {
                $num = (int)$ep['numero'];
                $titreEp = htmlspecialchars($ep['titre']);
                $duree = htmlspecialchars($ep['duree']);

                // Image fixe
                $html .= "
                    <div class='episode-card'>
                        <img src='../../../img/a.jpg' alt='Image épisode {$num}' class='episode-img'>
                        <div class='episode-info'>
                            <p><strong>Épisode {$num}</strong> : {$titreEp}</p>
                            <p>Durée : {$duree}</p>
                        </div>
                    </div>
                ";
            }
            $html .= "</div>";
        }

        // --- Retour catalogue ---
        $html .= "<p><a href='?action=Catalogue' class='btn-retour'>← Retour au catalogue</a></p>";

        return $html;
    }
}
