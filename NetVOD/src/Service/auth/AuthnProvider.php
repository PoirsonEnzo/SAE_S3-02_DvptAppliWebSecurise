<?php

namespace Service\auth;

use PDO;
use PDOException;
use Service\Exception\AuthnException;
use Service\repository\DeefyRepository;

class AuthnProvider
{
    private PDO $pdo;



    public function __construct()
    {
        $this->pdo = DeefyRepository::getInstance()->getPDO();
    }




    /**
     * Authentification d'un utilisateur existant
     * @throws AuthnException
     */
    public function signin(string $email, string $mdp): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new AuthnException("Utilisateur inconnu.");
            }

            if (!password_verify($mdp, $user['mot_de_passe'])) {
                throw new AuthnException("Mot de passe incorrect.");
            }

            if ((int)$user['actif'] !== 1) {
                // Génération d’un nouveau token d’activation
                $token = bin2hex(random_bytes(32));
                $expiration = date('Y-m-d H:i:s', strtotime('+1 day'));

                $this->pdo->prepare("
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
    public function register(string $email, string $password): array
    {
        if (strlen($password) < 10) {
            throw new AuthnException("Le mot de passe doit contenir au moins 10 caractères.");
        }

        try {
            // Vérifie si l'utilisateur existe déjà
            $stmt = $this->pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                throw new AuthnException("Un compte existe déjà avec cet email.");
            }

            // Hash sécurisé
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Insertion utilisateur (désactivé par défaut)
            $insert = $this->pdo->prepare("
                INSERT INTO utilisateur (email, mot_de_passe, date_creation)
                VALUES (:email, :mot_de_passe, NOW())
            ");
            $insert->execute([
                'email' => $email,
                'mot_de_passe' => $hash,
            ]);

            $idUtilisateur = $this->pdo->lastInsertId();

            // Génération du token d’activation
            $token = bin2hex(random_bytes(32));
            $expiration = date('Y-m-d H:i:s', strtotime('+1 day'));

            $this->pdo->prepare("
                INSERT INTO activation_token (id_utilisateur, token, expiration)
                VALUES (:id, :token, :expiration)
            ")->execute([
                'id' => $idUtilisateur,
                'token' => $token,
                'expiration' => $expiration
            ]);

            $activationLink = "?action=activateAccount&token=$token";

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
     * Génère un token de réinitialisation pour un utilisateur existant
     */
    public function generateResetToken(string $email): string
    {
        $stmt = $this->pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new \Exception("Email inconnu");
        }

        $token = bin2hex(random_bytes(32));

        $this->pdo->prepare("
            UPDATE utilisateur 
            SET token_activation = :token, date_token = DATE_ADD(NOW(), INTERVAL 15 MINUTE)
            WHERE id_utilisateur = :id
        ")->execute([
            'token' => $token,
            'id' => $user['id_utilisateur']
        ]);

        return "?action=ResetPassword&token=$token";
    }



    public function isUserRegistered(): bool
    {
        return !empty($_SESSION['user']);
    }

    public function getSignedInUser(): array
    {
        if (empty($_SESSION['user'])) {
            throw new AuthnException("Aucun utilisateur n'est authentifié.");
        }

        return $_SESSION['user'];
    }



    public function signout(): void
    {
        unset($_SESSION['user'], $_SESSION['user_role']);
    }
}
