<?php

namespace Service\action;

use Service\repository\DeefyRepository;

class AfficherSerie extends Action
{
    public function getResult(): string
    {
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        if (!isset($_GET['id'])) {
            return "<p>Aucune série sélectionnée.</p>";
        }

        $idSerie = (int) $_GET['id'];
        $pdo = DeefyRepository::getInstance()->getPDO();

        // --- Infos série ---
        $stmt = $pdo->prepare("
            SELECT id_serie, titre, descriptif, annee, date_ajout
            FROM serie
            WHERE id_serie = ?
        ");
        $stmt->execute([$idSerie]);
        $serie = $stmt->fetch();

        if (!$serie) {
            return "<p>❌ Série introuvable.</p>";
        }

        $titre = htmlspecialchars($serie['titre']);
        $desc = htmlspecialchars($serie['descriptif']);
        $annee = htmlspecialchars($serie['annee']);
        $dateAjout = htmlspecialchars($serie['date_ajout']);

        $html = "
        <div class='serie-details'>
            <h2>{$titre}</h2>
            <p><strong>Description :</strong> {$desc}</p>
            <p><strong>Année :</strong> {$annee}</p>
            <p><strong>Date d’ajout :</strong> {$dateAjout}</p>
        </div>
        <h3>Épisodes</h3>
        ";

        // --- Épisodes ---
        $stmt2 = $pdo->prepare("
            SELECT id_episode, numero, titre, duree
            FROM episode
            WHERE serie_id = ?
            ORDER BY numero ASC
        ");
        $stmt2->execute([$idSerie]);
        $episodes = $stmt2->fetchAll();

        if (empty($episodes)) {
            $html .= "<p>Aucun épisode disponible.</p>";
        } else {
            $html .= "<div class='episodes-grid'>";
            foreach ($episodes as $ep) {
                $num = (int)$ep['numero'];
                $titreEp = htmlspecialchars($ep['titre']);
                $duree = htmlspecialchars($ep['duree']);
                $idEp = (int)$ep['id_episode'];

                $html .= "
                    <div class='episode-card'>
                        <img src='../../../img/a.jpg' alt='Image épisode {$num}' class='episode-img'>
                        <div class='episode-info'>
                            <a href='?action=afficherEpisode&id={$idEp}'><strong>Épisode {$num}</strong> : {$titreEp}</a>
                            <p>Durée : {$duree}</p>
                        </div>
                    </div>
                ";
            }
            $html .= "</div>";
        }

        $html .= "<p><a href='?action=Catalogue' class='btn-retour'>← Retour au catalogue</a></p>";

        return $html;
    }
}
