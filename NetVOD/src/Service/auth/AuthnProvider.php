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
                throw new AuthnException("Utilisateur inconnu.");
            }

            if (!password_verify($mdp, $user['mot_de_passe'])) {
                throw new AuthnException("Mot de passe incorrect.");
            }

            if ((int)$user['actif'] !== 1) {
                // Générer un nouveau token d'activation
                $token = bin2hex(random_bytes(32));
                $expiration = date('Y-m-d H:i:s', strtotime('+1 day'));
                $pdo->prepare("
                INSERT INTO activation_token (id_utilisateur, token, expiration)
                VALUES (:id, :token, :expiration)
                ON DUPLICATE KEY UPDATE token = :token, expiration = :expiration
            ")->execute([
                    'id' => $user['id_utilisateur'],
                    'token' => $token,
                    'expiration' => $expiration
                ]);

                $activationLink = "?action=activateAccount&token=$token";
                throw new AuthnException("Votre compte n’est pas encore activé. <a href='$activationLink'>Cliquez ici pour recevoir un nouveau lien d'activation</a>.");
            }

            return [
                'id_utilisateur' => $user['id_utilisateur'],
                'email' => $user['email'],
                'role'  => (int)$user['role']
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

            // Insertion utilisateur (désactivé par défaut)
            $insert = $pdo->prepare("
                INSERT INTO utilisateur (email, mot_de_passe, date_creation)
                VALUES (:email, :mot_de_passe, NOW())
            ");
            $insert->execute([
                'email' => $email,
                'mot_de_passe' => $hash,
            ]);

            $idUtilisateur = $pdo->lastInsertId();

            // Génération du token aléatoire
            $token = bin2hex(random_bytes(32));
            $expiration = date('Y-m-d H:i:s', strtotime('+1 day'));

            // Enregistrement du token
            $insertToken = $pdo->prepare("
                INSERT INTO activation_token (id_utilisateur, token, expiration)
                VALUES (:id, :token, :expiration)
            ");
            $insertToken->execute([
                'id' => $idUtilisateur,
                'token' => $token,
                'expiration' => $expiration
            ]);

            $activationLink = "?action=activateAccount&token={$token}";

            return [
                'id_utilisateur' => $idUtilisateur,
                'email' => $email,
                'activation_link' => $activationLink
            ];

        } catch (PDOException $e) {
            throw new AuthnException("Erreur base de données : " . $e->getMessage());
        }
    }

    /**
     * Génération d’un token de réinitialisation pour un compte existant
     */
    public static function generateResetToken(string $email): string
    {
        $repo = DeefyRepository::getInstance();
        $pdo = $repo->getPDO();

        $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new \Exception("Email inconnu");
        }

        $token = bin2hex(random_bytes(32));
        $pdo->prepare("
            UPDATE utilisateur 
            SET token_activation = :token, date_token = DATE_ADD(NOW(), INTERVAL 15 MINUTE) 
            WHERE id_utilisateur = :id
        ")->execute([
            'token' => $token,
            'id' => $user['id_utilisateur']
        ]);

        return "?action=ResetPassword&token=$token";
    }

    /**
     * Vérifie si un utilisateur est connecté
     * @return bool
     */
    public static function isUserRegistered(): bool
    {
        return !empty($_SESSION['user']);
    }

    /**
     * Récupère les informations de l'utilisateur connecté
     * @return array
     * @throws AuthnException
     */
    public static function getSignedInUser(): array
    {
        if (empty($_SESSION['user'])) {
            throw new AuthnException("Aucun utilisateur n'est authentifié.");
        }

        return $_SESSION['user'];
    }

    /**
     * Déconnecte l'utilisateur
     */
    public static function signout(): void
    {
        unset($_SESSION['user']);
        unset($_SESSION['user_role']);
    }
}
