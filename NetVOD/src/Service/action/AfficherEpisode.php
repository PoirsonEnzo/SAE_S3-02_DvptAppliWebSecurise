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
        $idUtilisateur = (int) $_SESSION['user']['id']; // id de l'utilisateur connecté

        $pdo = DeefyRepository::getInstance()->getPDO();

        // --- Récupération de l'id_profil par défaut pour cet utilisateur ---
        $stmtProfil = $pdo->prepare("
            SELECT id_profil 
            FROM profil 
            WHERE id_utilisateur = ? 
            ORDER BY id_profil ASC 
            LIMIT 1
        ");
        $stmtProfil->execute([$idUtilisateur]);
        $profil = $stmtProfil->fetch();

        if (!$profil) {
            return "<p>Aucun profil trouvé pour cet utilisateur.</p>";
        }

        $idProfil = (int) $_SESSION['profil']['id_profil'] ?? null;

        // --- Récupération des infos de l'épisode ---
        $stmt = $pdo->prepare("
            SELECT titre, resume, duree, fichier, img
            FROM episode
            WHERE id_episode = ?
        ");
        $stmt->execute([$idEpisode]);
        $ep = $stmt->fetch();

        if (!$ep) {
            return "<p>Épisode introuvable.</p>";
        }

        // --- Sécurisation des données ---
        $titre = htmlspecialchars($ep['titre']);
        $resume = nl2br(htmlspecialchars($ep['resume']));
        $duree = htmlspecialchars($ep['duree']);
        $fichierVideo = htmlspecialchars($ep['fichier']);
        $imgFile = htmlspecialchars($ep['img'] ?? 'default.png'); // fallback si image manquante


        try {
            // Vérifie s'il n'est pas déjà enregistré
            $check = $pdo->prepare("
                SELECT 1 FROM en_cours 
                WHERE id_profil = :id_profil AND id_episode = :id_episode
            ");
            $check->execute([
                'id_profil' => $idProfil,
                'id_episode' => $idEpisode
            ]);

            if (!$check->fetch()) {
                // Ajoute le couple si inexistant
                $insert = $pdo->prepare("
                    INSERT INTO en_cours (id_profil, id_episode)
                    VALUES (:id_profil, :id_episode)
                ");
                $insert->execute([
                    'id_profil' => $idProfil,
                    'id_episode' => $idEpisode
                ]);
                // 🔹 Insère dans la table vision en associant au profil
                $insertV = $pdo->prepare("
                INSERT INTO visionnees (id_profil, id_episode)
                VALUES (:id_profil, :id_episode)
            ");
                $insertV->execute([
                    'id_profil' => $idProfil,
                    'id_episode' => $idEpisode
                ]);
            }
        } catch (\Exception $e) {

            error_log("Erreur lors de l'ajout dans en_cours : " . $e->getMessage());
        }

        // --- HTML principal ---
        $html = "
        <div class='episode-detail'>
            <h2>{$titre}</h2>

            <div class='video-container'>
                <video width='640' height='360' controls>
                    <source src='video/{$fichierVideo}' type='video/mp4'>
                    Votre navigateur ne supporte pas la lecture vidéo.
                </video>
            </div>

            <p><strong>Résumé :</strong> {$resume}</p>
            <p><strong>Durée :</strong> {$duree} secondes</p>
            
            <p><a href='?action=Commentaire&id={$idEpisode}' class='btn-retour'>- - Laisser un commentaire - -</a></p>
            ";

            $html .= '<div class="moyenneNote">';
            $moyNote = $pdo -> prepare(
              "SELECT ROUND(AVG(note), 2) AS moyenne
                    FROM commentaire
                    WHERE id_episode = ?"
            );
            $moyNote ->execute([$idEpisode]);
            $results = $moyNote->fetchAll();
            $html .= "<p>Note moyenne de cet épisode :</p>";
            foreach ($results as $moy){
                $html .= "<p>{$moy['moyenne']}</p>";
            }
            $html .= '<div>';


            $html .= '<div class="commentaires">';
            $comms = $pdo -> prepare(
                "SELECT c.texte,p.username
                        FROM commentaire c
                        INNER JOIN profil p
                        ON c.id_profil = p.id_profil
                        WHERE id_episode = ?"
            );
            $comms ->execute([$idEpisode]);
            $results = $comms->fetchAll();
            $html .= "<p>Commentaires :</p>";
            foreach ($results as $com){
                $html .= "<p>{$com['username']}</p><p>{$com['texte']}</p>";
            }
            $html .= '<div>';

            $html .= "
            <p><a href='?action=Catalogue' class='btn-retour'>Retour au catalogue</a></p>
        </div>
        ";

        return $html;
    }
}
