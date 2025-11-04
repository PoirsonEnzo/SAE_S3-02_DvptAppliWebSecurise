<?php

namespace Service\action;

use Service\repository\DeefyRepository;

class AfficherEpisode extends Action
{
    public function getResult(): string
    {
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        if (!isset($_GET['id'])) {
            return "<p>Aucun épisode sélectionné.</p>";
        }

        $idEpisode = (int) $_GET['id'];
        $pdo = DeefyRepository::getInstance()->getPDO();

        $stmt = $pdo->prepare("
            SELECT E.titre, E.resume, E.duree, E.numero, S.id_serie, S.titre AS titre_serie
            FROM episode E
            JOIN serie S ON E.serie_id = S.id_serie
            WHERE E.id_episode = ?
        ");
        $stmt->execute([$idEpisode]);
        $ep = $stmt->fetch();

        if (!$ep) {
            return "<p>❌ Épisode introuvable.</p>";
        }

        $titreEp = htmlspecialchars($ep['titre']);
        $resume = htmlspecialchars($ep['resume']);
        $duree = htmlspecialchars($ep['duree']);
        $num = (int)$ep['numero'];
        $titreSerie = htmlspecialchars($ep['titre_serie']);
        $idSerie = (int)$ep['id_serie'];

        $html = "
        <div class='episode-details'>
            <h2>{$titreSerie} — Épisode {$num} : {$titreEp}</h2>
            <img src='../../../img/a.jpg' alt='Image épisode {$num}' class='episode-img'>
            <p><strong>Résumé :</strong> {$resume}</p>
            <p><strong>Durée :</strong> {$duree}</p>
            <p><a href='?action=afficherSerie&id={$idSerie}' class='btn-retour'>← Retour à la série</a></p>
        </div>
        ";

        return $html;
    }
}
