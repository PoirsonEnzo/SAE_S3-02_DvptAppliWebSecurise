<?php

namespace Service\auth;

use PDO;
use PDOException;
use Service\Exception\AuthnException;
use Service\repository\DeefyRepository;

class AuthnProvider
{
    /**
     * Authentification d'un utilisateur existant
     * @throws AuthnException
     */
    public static function signin(string $email, string $mdp): array
    {
        try {
            $repo = DeefyRepository::getInstance();
            $pdo = $repo->getPDO();

            $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new AuthnException("utilisateur inconnu.");
            }

            if (!password_verify($mdp, $user['mot_de_passe'])) {
                throw new AuthnException("Mot de passe incorrect.");
            }

            return [
                'id'    => $user['id_utilisateur'],
                'email' => $user['email'],
            ];

        } catch (PDOException $e) {
            throw new AuthnException("Erreur base de données : " . $e->getMessage());
        }
    }

    /**
     * Enregistrement d’un nouvel utilisateur
     * @throws AuthnException
     */
    public static function register(string $email, string $password): array
    {
        if (strlen($password) < 10) {
            throw new AuthnException("Le mot de passe doit contenir au moins 10 caractères.");
        }

        try {
            $repo = DeefyRepository::getInstance();
            $pdo = $repo->getPDO();

            // Vérifie si l'utilisateur existe déjà
            $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                throw new AuthnException("Un compte existe déjà avec cet email.");
            }

            // Hash sécurisé du mot de passe
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Insertion dans la table utilisateur
            $insert = $pdo->prepare("
            INSERT INTO utilisateur (email, mot_de_passe, date_creation, nb_profil)
            VALUES (:email, :mot_de_passe, NOW(), 0)
        ");

            $insert->execute([
                'email' => $email,
                'mot_de_passe' => $hash,
            ]);

            return [
                'id_utilisateur' => $pdo->lastInsertId(),
                'email' => $email,
            ];

        } catch (PDOException $e) {
            throw new AuthnException("Erreur base de données : " . $e->getMessage());
        }
    }



    /**
     * @return array Les données de l'utilisateur [id_utilisateur, email]
     * @throws AuthnException Si aucun utilisateur n'est connecté.
     */
    public static function getSignedInUser(): array
    {
        if (empty($_SESSION['user'])) {
            throw new AuthnException("Aucun utilisateur n'est authentifié.");
        }

        return $_SESSION['user'];
    }

    /**
     * @return bool
     */
    public static function isUserRegistered(): bool
    {
        return !empty($_SESSION['user']);
    }


    /**
     * Déconnecte l'utilisateur.
     */
    public static function signout(): void
    {
        unset($_SESSION['user']);
    }
}
