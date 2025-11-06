<?php
namespace Service\action\interaction;

use Service\action\Action;
use Service\repository\DeefyRepository;

class AjouterFavorisAction extends Action
{
    public function getResult(): string
    {
        // Vérification de connexion
        if (!isset($_SESSION['user'])) {
            return "<p>Vous devez être connecté pour ajouter une série à vos favoris.</p>";
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
            return "<div class='favoris'><p>impossible d'ajouter en favoris : Aucun profil de sélectionné.</p></div>";
        }

        $idProfil = (int) $_SESSION['profil']['id_profil'];

        // Vérifier si déjà en favoris
        $check = $pdo->prepare("SELECT * FROM favoris WHERE id_profil = ? AND id_serie = ?");
        $check->execute([$idProfil, $idSerie]);

        if ($check->fetch()) {
            return "<div class='favoris'><p>Cette série est déjà dans vos favoris.</p>
                    <p><a href='?action=AfficherSerie&id={$idSerie}'>Retour à la série</a></p></div>";
        }

        // Insertion dans favoris
        $stmt = $pdo->prepare("INSERT INTO favoris (id_profil, id_serie) VALUES (?, ?)");
        $stmt->execute([$idProfil, $idSerie]);

        return "<div class='favoris'><p>Série ajoutée à vos favoris !</p>
                <p><a href='?action=AfficherSerie&id={$idSerie}'>Retour à la série</a></p></div>";
    }
}
