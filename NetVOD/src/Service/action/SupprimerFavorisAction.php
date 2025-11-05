<?php
namespace Service\action;

use Service\repository\DeefyRepository;

class SupprimerFavorisAction extends Action
{
    public function getResult(): string
    {
        // Vérification de connexion
        if (!isset($_SESSION['user'])) {
            return "<p>Vous devez être connecté pour supprimer une série de vos favoris.</p>";
        }

        // Vérification de l'ID série
        if (!isset($_GET['id'])) {
            return "<p>Aucune série spécifiée.</p>";
        }

        $idSerie = (int) $_GET['id'];
        $pdo = DeefyRepository::getInstance()->getPDO();

        // Récupération de l'id du profil courant
        // Si tu stockes le profil actif dans $_SESSION['profil']['id_profil'] :
        if (!isset($_SESSION['profil']['id_profil'])) {
            return "<p>impossible de supprimer des favoris : Aucun profil de sélectionné.</p>";
        }

        $idProfil = (int) $_SESSION['profil']['id_profil'];

        // Vérifier si déjà en favoris
        $check = $pdo->prepare("SELECT * FROM favoris WHERE id_profil = ? AND id_serie = ?");
        $check->execute([$idProfil, $idSerie]);

        if (!$check->fetch()) {
            return "<p>Cette série n'est pas dans vos favoris.</p>
                    <p><a href='?action=AfficherSerie&id={$idSerie}'>Retour à la série</a></p>";
        }

        // Insertion dans favoris
        $stmt = $pdo->prepare("DELETE FROM favoris WHERE id_profil = ? AND id_serie = ?");
        $stmt->execute([$idProfil, $idSerie]);

        return "<p>Série supprimée de vos favoris !</p>
                <p><a href='?action=AfficherSerie&id={$idSerie}'>Retour à la série</a></p>";
    }
}
