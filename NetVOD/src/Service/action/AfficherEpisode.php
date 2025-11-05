<?php

namespace Service\action;

use Service\repository\DeefyRepository;
use \PDO;

class AfficherEpisode extends Action
{
    public function getResult(): string
    {
        // 🔹 Vérifie la connexion
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        // 🔹 Vérifie qu’un ID d’épisode est passé
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            return "<p>Aucun épisode sélectionné.</p>";
        }

        $idEpisode = (int) $_GET['id'];
        $pdo = DeefyRepository::getInstance()->getPDO();

        // 🔹 Vérifie que le profil actif existe
        if (!isset($_SESSION['profil'])) {
            return "<p class='text-red-500 font-semibold'>
                        Aucun profil sélectionné. 
                        <a href='?action=AddProfilAction' class='text-blue-500 hover:underline'>Créer ou choisir un profil</a>
                    </p>";
        }

        $idProfil = (int) $_SESSION['profil']['id_profil'];

        // 🔹 Récupération des infos de l’épisode
        $stmt = $pdo->prepare("
            SELECT titre, resume, duree, fichier, img, id_serie, numero_episode
            FROM episode
            WHERE id_episode = ?
        ");

        $stmt->execute([$idEpisode]);
        $ep = $stmt->fetch();

        if (!$ep) {
            return "<p>Épisode introuvable.</p>";
        }

        $idSerie = (int) $ep['id_serie'];

        try {
            // 🔹 Vérifie si le profil a déjà un épisode en cours pour cette série
            $check = $pdo->prepare("
                SELECT e.id_episode 
                FROM en_cours ec
                JOIN episode e ON ec.id_episode = e.id_episode
                WHERE ec.id_profil = :id_profil AND e.id_serie = :id_serie
            ");
            $check->execute([
                'id_profil' => $idProfil,
                'id_serie' => $idSerie
            ]);

            $existing = $check->fetch();

            if ($existing) {
                // ✅ Met à jour l’épisode existant pour cette série
                $update = $pdo->prepare("
                    UPDATE en_cours
                    SET id_episode = :new_episode
                    WHERE id_profil = :id_profil
                    AND id_episode = :old_episode
                ");
                $update->execute([
                    'new_episode' => $idEpisode,
                    'id_profil' => $idProfil,
                    'old_episode' => $existing['id_episode']
                ]);
            } else {
                // ✅ Ajoute un nouvel épisode pour cette série
                $insert = $pdo->prepare("
                    INSERT INTO en_cours (id_profil, id_episode)
                    VALUES (:id_profil, :id_episode)
                ");
                $insert->execute([
                    'id_profil' => $idProfil,
                    'id_episode' => $idEpisode
                ]);
            }

            // 🔹 Ajoute à la table "visionnées"
            $insertV = $pdo->prepare("
                INSERT IGNORE INTO visionnees (id_profil, id_episode)
                VALUES (:id_profil, :id_episode)
            ");
            $insertV->execute([
                'id_profil' => $idProfil,
                'id_episode' => $idEpisode
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur en_cours/visionnees : " . $e->getMessage());
        }

        // 🔹 Sécurisation des données pour affichage
        $titre = htmlspecialchars($ep['titre']);
        $resume = nl2br(htmlspecialchars($ep['resume']));
        $duree = htmlspecialchars($ep['duree']);
        $fichierVideo = htmlspecialchars($ep['fichier']);
        $imgFile = htmlspecialchars($ep['img'] ?? 'default.png');

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

        // 🔹 Note moyenne
        $moyNote = $pdo->prepare("
            SELECT ROUND(AVG(note), 2) AS moyenne
            FROM commentaire
            WHERE id_episode = ?
        ");
        $moyNote->execute([$idEpisode]);
        $note = $moyNote->fetchColumn() ?? "Aucune note";
        $html .= "<p>Note moyenne de cet épisode : <strong>{$note}</strong></p>";

        // 🔹 Commentaires
        $comms = $pdo->prepare("
            SELECT c.texte, p.username
            FROM commentaire c
            JOIN profil p ON c.id_profil = p.id_profil
            WHERE id_episode = ?
        ");
        $comms->execute([$idEpisode]);
        $results = $comms->fetchAll();

        $html .= "<div class='commentaires'><h3>Commentaires :</h3>";
        foreach ($results as $com) {
            $html .= "<p><strong>{$com['username']} :</strong> " . htmlspecialchars($com['texte']) . "</p>";
        }
        $html .= "</div>";

        // 🔹 Navigation épisodes
        $stmtNav = $pdo->prepare("
    SELECT id_episode, numero_episode 
    FROM episode 
    WHERE id_serie = :id_serie
");
        // 🔹 Navigation épisodes
        $stmtNav = $pdo->prepare("
    SELECT id_episode, numero_episode 
    FROM episode 
    WHERE id_serie = :id_serie
    ORDER BY numero_episode ASC
");
        $stmtNav->execute(['id_serie' => $idSerie]);
        $episodesSerie = $stmtNav->fetchAll(PDO::FETCH_ASSOC);

        $prevId = null;
        $nextId = null;

        foreach ($episodesSerie as $key => $epSerie) {
            if ($epSerie['id_episode'] == $idEpisode) {
                if ($key > 0) {
                    $prevId = $episodesSerie[$key - 1]['id_episode'];
                }
                if ($key < count($episodesSerie) - 1) {
                    $nextId = $episodesSerie[$key + 1]['id_episode'];
                }
                break;
            }
        }

// 🔹 Boutons de navigation avec ton CSS
        $html .= '<div class="episode-navigation mt-4" style="display:flex; justify-content:center; gap:15px;">';
        if ($prevId) {
            $html .= "<a href='?action=AfficherEpisode&id={$prevId}' class='btn-retour'>&laquo;</a>";
        }
        $html .= "<a href='?action=AfficherSerie&id={$idSerie}' class='btn-retour'>Épisodes</a>";
        if ($nextId) {
            $html .= "<a href='?action=AfficherEpisode&id={$nextId}' class='btn-retour'>&raquo;</a>";
        }
        $html .= '</div>';

        $html .= "
            <p><a href='?action=Catalogue' class='btn-retour'>Retour au catalogue</a></p>
        </div>
        ";

        return $html;
    }
}
