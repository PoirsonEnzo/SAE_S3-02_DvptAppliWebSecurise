<?php

namespace Service\action;

use Service\repository\DeefyRepository;

class AfficherEpisode extends Action
{
    public function getResult(): string
    {
        // Vérifie la connexion
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        // Vérifie qu’un ID d’épisode est passé
        if (!isset($_GET['id'])) {
            return "<p>Aucun épisode sélectionné.</p>";
        }

        $idEpisode = (int) $_GET['id'];
        $pdo = DeefyRepository::getInstance()->getPDO();

        // --- Récupération des infos de l'épisode ---
        $stmt = $pdo->prepare("
            SELECT titre, resume, duree, file
            FROM episode
            WHERE id_episode = ?
        ");
        $stmt->execute([$idEpisode]);
        $ep = $stmt->fetch();

        if (!$ep) {
            return "<p>❌ Épisode introuvable.</p>";
        }

        // --- Sécurisation des données ---
        $titre = htmlspecialchars($ep['titre']);
        $resume = nl2br(htmlspecialchars($ep['resume']));
        $duree = htmlspecialchars($ep['duree']);
        $fichierVideo = htmlspecialchars($ep['file']); // ex: episode1.mp4

        // --- HTML principal ---
        $html = "
        <div class='episode-detail'>
            <h2>{$titre}</h2>
            <img src='img/a.jpg' alt='Image de l’épisode' class='episode-detail-img'>

            <div class='video-container'>
                <video width='640' height='360' controls>
                    <source src='video/{$fichierVideo}' type='video/mp4'>
                    Votre navigateur ne supporte pas la lecture vidéo.
                </video>
            </div>

            <p><strong>Résumé :</strong> {$resume}</p>
            <p><strong>Durée :</strong> {$duree} min</p>

            <p><a href='?action=Catalogue' class='btn-retour'>← Retour au catalogue</a></p>
        </div>
        ";

        return $html;
    }
}
