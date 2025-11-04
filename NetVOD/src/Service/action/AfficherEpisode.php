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
        $idUtilisateur = (int) $_SESSION['user']['id']; // récupère l'id utilisateur

        $pdo = DeefyRepository::getInstance()->getPDO();

        // --- Récupération des infos de l'épisode ---
        $stmt = $pdo->prepare("
            SELECT titre, resume, duree, fichier
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
        $fichierVideo = htmlspecialchars($ep['fichier']);

        // --- Ajout automatique dans la table en_cours ---
        try {
            // Vérifie s'il n'est pas déjà enregistré
            $check = $pdo->prepare("
                SELECT 1 FROM en_cours 
                WHERE id_utilisateur = :id_utilisateur AND id_episode = :id_episode
            ");
            $check->execute([
                'id_utilisateur' => $idUtilisateur,
                'id_episode' => $idEpisode
            ]);

            if (!$check->fetch()) {
                // Ajoute le couple si inexistant
                $insert = $pdo->prepare("
                    INSERT INTO en_cours (id_utilisateur, id_episode)
                    VALUES (:id_utilisateur, :id_episode)
                ");
                $insert->execute([
                    'id_utilisateur' => $idUtilisateur,
                    'id_episode' => $idEpisode
                ]);
            }
        } catch (\Exception $e) {
            // Optionnel : tu peux loguer ou ignorer, car c’est non-bloquant
            error_log("Erreur lors de l'ajout dans en_cours : " . $e->getMessage());
        }

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
            <p><strong>Durée :</strong> {$duree} secondes</p>

            <p><a href='?action=Catalogue' class='btn-retour'>← Retour au catalogue</a></p>
        </div>
        ";

        return $html;
    }
}
