<?php
namespace Service\action;

use Service\repository\DeefyRepository;
use Service\Exception\AuthnException;

class AjouterEnCoursAction extends Action
{
    public function getResult(): string
    {
        // Vérification : utilisateur connecté ?
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        // Vérification : id_episode présent dans la requête ?
        if (!isset($_GET['id'])) {
            return "<p class='text-red-500'>Aucun épisode sélectionné.</p>";
        }

        $idEpisode = (int) $_GET['id'];
        $idUtilisateur = (int) $_SESSION['user']['id'];

        try {
            $pdo = DeefyRepository::getInstance()->getPDO();

            // Vérifie si le couple existe déjà
            $check = $pdo->prepare("
                SELECT COUNT(*) 
                FROM en_cours 
                WHERE id_utilisateur = :id_utilisateur AND id_episode = :id_episode
            ");
            $check->execute([
                'id_utilisateur' => $idUtilisateur,
                'id_episode' => $idEpisode
            ]);

            if ($check->fetchColumn() > 0) {
                return "<p class='text-yellow-500 font-semibold'>
                    Cet épisode est déjà dans votre liste « En cours ».
                </p>
                <p><a href='?action=afficherEpisode&id={$idEpisode}' class='text-blue-500 hover:underline'>Retour à l’épisode</a></p>";
            }

            // Insertion du couple dans la table en_cours
            $insert = $pdo->prepare("
                INSERT INTO en_cours (id_utilisateur, id_episode, date_ajout)
                VALUES (:id_utilisateur, :id_episode, NOW())
            ");
            $insert->execute([
                'id_utilisateur' => $idUtilisateur,
                'id_episode' => $idEpisode
            ]);

            return "<p class='text-green-500 font-semibold'>
                Épisode ajouté à votre liste « En cours » !
            </p>
            <p><a href='?action=afficherEpisode&id={$idEpisode}' class='text-blue-500 hover:underline'>
                Retour à l’épisode
            </a></p>";

        } catch (\Exception $e) {
            $msg = htmlspecialchars($e->getMessage());
            return "<p class='text-red-500 font-semibold'>Erreur : {$msg}</p>";
        }
    }
}
