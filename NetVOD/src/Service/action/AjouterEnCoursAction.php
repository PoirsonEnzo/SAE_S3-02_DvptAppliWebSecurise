<?php
namespace Service\action;

use Service\repository\DeefyRepository;

class AjouterEnCoursAction extends Action
{
    public function getResult(): string
    {
        // 🔹 Vérification : utilisateur connecté ?
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        // 🔹 Vérification : profil actif ?
        if (!isset($_SESSION['profil'])) {
            return "<p class='text-red-500 font-semibold'>
                        Aucun profil sélectionné. 
                        <a href='?action=addProfilAction' class='text-blue-500 hover:underline'>Créer ou choisir un profil</a>
                    </p>";
        }

        // 🔹 Vérifie que l'épisode est précisé
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            return "<p class='text-red-500'>Aucun épisode sélectionné ou ID invalide.</p>";
        }

        $idEpisode = (int) $_GET['id'];
        $idProfil = (int) $_SESSION['profil']['id_profil'];

        try {
            $pdo = DeefyRepository::getInstance()->getPDO();

            // 🔹 Vérifie que l'épisode existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM episode WHERE id_episode = :id_episode");
            $stmt->execute(['id_episode' => $idEpisode]);
            if ($stmt->fetchColumn() == 0) {
                return "<p class='text-red-500'>L'épisode sélectionné n'existe pas.</p>";
            }

            // 🔹 Vérifie si le couple (profil, épisode) existe déjà
            $check = $pdo->prepare("
                SELECT COUNT(*) 
                FROM en_cours 
                WHERE id_profil = :id_profil AND id_episode = :id_episode
            ");
            $check->execute([
                'id_profil' => $idProfil,
                'id_episode' => $idEpisode
            ]);

            if ($check->fetchColumn() > 0) {
                return "<p class='text-yellow-500 font-semibold'>
                    Cet épisode est déjà dans la liste « En cours » de votre profil.
                </p>
                <p><a href='?action=AfficherEpisode&id={$idEpisode}' class='text-blue-500 hover:underline'>Retour à l’épisode</a></p>";
            }

            // 🔹 Insère dans la table en_cours en associant au profil
            $insert = $pdo->prepare("
                INSERT INTO en_cours (id_profil, id_episode)
                VALUES (:id_profil, :id_episode)
            ");
            $insert->execute([
                'id_profil' => $idProfil,
                'id_episode' => $idEpisode
            ]);

            return "<p class='text-green-500 font-semibold'>
                Épisode ajouté à la liste « En cours » du profil <strong>{$_SESSION['profil']['username']}</strong> !
            </p>
            <p><a href='?action=AfficherEpisode&id={$idEpisode}' class='text-blue-500 hover:underline'>
                Retour à l’épisode
            </a></p>";

        } catch (\PDOException $e) {
            // 🔹 Affiche l'erreur pour debug
            $msg = htmlspecialchars($e->getMessage());
            return "<p class='text-red-500 font-semibold'>Erreur PDO : {$msg}</p>";
        } catch (\Exception $e) {
            $msg = htmlspecialchars($e->getMessage());
            return "<p class='text-red-500 font-semibold'>Erreur : {$msg}</p>";
        }
    }
}
