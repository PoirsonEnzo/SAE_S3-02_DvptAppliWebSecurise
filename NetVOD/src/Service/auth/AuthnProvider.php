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

            $stmt = $pdo->prepare("SELECT * FROM User WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new AuthnException("Utilisateur inconnu.");
            }

            if (!password_verify($mdp, $user['passwd'])) {
                throw new AuthnException("Mot de passe incorrect.");
            }

            return [
                'id'    => $user['id'],
                'email' => $user['email'],
                'role'  => $user['role']
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

            $stmt = $pdo->prepare("SELECT id FROM User WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                throw new AuthnException("Un compte existe déjà avec cet email.");
            }

            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $insert = $pdo->prepare("INSERT INTO User (email, passwd, role) VALUES (:email, :passwd, 1)");
            $insert->execute(['email' => $email, 'passwd' => $hash]);

            return [
                'id'    => $pdo->lastInsertId(),
                'email' => $email,
                'role'  => 1
            ];

        } catch (PDOException $e) {
            throw new AuthnException("Erreur base de données : " . $e->getMessage());
        }
    }

    /**
     * @return array Les données de l'utilisateur [id, email, role]
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
     * Déconnecte l'utilisateur.
     */
    public static function signout(): void
    {
        unset($_SESSION['user']);
    }
}
