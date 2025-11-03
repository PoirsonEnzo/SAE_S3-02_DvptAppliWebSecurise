<?php

namespace iutnc\deefy\auth;

use iutnc\deefy\exception\AuthzException;
use iutnc\deefy\repository\DeefyRepository;

class Authz
{
    // Définition de constantes de rôle
    public const USER_AUTHENTICATED = 'AUTHENTICATED';
    public const USER_ADMIN = 'ADMIN';

    /**
     * Vérifie qu'un utilisateur est connecté
     * et qu'il possède un rôle donné.
     */
    public static function checkRole(string $requiredRole): void
    {
        // Vérifie la présence d’un utilisateur
        if (!isset($_SESSION['user'])) {
            // Redirige proprement vers la page de connexion
            header('Location: ?action=signin&error=not_logged_in');
            exit();
        }

        $user = $_SESSION['user'];

        // Si juste besoin d’être connecté : ok
        if ($requiredRole === self::USER_AUTHENTICATED) {
            return;
        }
    }


    /**
     * Vérifie que l’utilisateur connecté est bien
     * le propriétaire d’une playlist donnée.
     */
    public static function checkPlaylistOwner(int $playlistId): void
    {
        if (!isset($_SESSION['user'])) {
            throw new AuthzException("Utilisateur non connecté");
        }

        $repo = DeefyRepository::getInstance();
        $ownerId = $repo->findPlaylistOwner($playlistId);

        $user = $_SESSION['user'];
        if ($ownerId !== $user['id_utilisateur']) {
            throw new AuthzException("Accès refusé : vous n’êtes pas propriétaire de cette playlist");
        }
    }
}
