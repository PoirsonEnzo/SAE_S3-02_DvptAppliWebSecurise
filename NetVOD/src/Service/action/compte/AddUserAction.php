<?php
namespace Service\action\compte;

use Service\action\Action;
use Service\auth\AuthnProvider;
use Service\Exception\AuthnException;
use Service\repository\DeefyRepository;

class AddUserAction extends Action
{
    public function getResult(): string
    {
        $message = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'] ?? '';
            $password2 = $_POST['password2'] ?? '';
            $carte = $_POST['carte'] ?? '';

            if (!$email) {
                $message = "<p style='color:red; font-weight:bold;'>Veuillez saisir un email valide.</p>";
            } elseif ($password !== $password2) {
                $message = "<p style='color:red; font-weight:bold;'>Les mots de passe ne correspondent pas.</p>";
            } else {
                try {
                    $user = (new AuthnProvider)->register($email, $password);
                    $pdo = DeefyRepository::getInstance()->getPDO();

                    $stmtId = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
                    $stmtId->execute([$email]);
                    $idUtilisateur = (int)$stmtId->fetchColumn();

                    $stmt = $pdo->prepare("UPDATE utilisateur SET num_carte = ? WHERE id_utilisateur = ?");
                    $stmt->execute([$carte, $idUtilisateur]);

                    $token = bin2hex(random_bytes(32));
                    $expiration = date('Y-m-d H:i:s', strtotime('+1 day'));
                    $stmtToken = $pdo->prepare("INSERT INTO activation_token (id_utilisateur, token, expiration) VALUES (?, ?, ?)");
                    $stmtToken->execute([$idUtilisateur, $token, $expiration]);

                    $activationLink = "?action=activateAccount&token=$token";
                    $message = <<<HTML
                <p style='color:green; font-weight:bold;'>Compte créé avec succès pour <strong>{$user['email']}</strong>.</p>
                <div class="btn-container">
                    <a href="$activationLink" class="btn-center">Activer le compte</a>
                </div>
            HTML;

                } catch (AuthnException $e) {
                    $message = "<p style='color:red; font-weight:bold;'>Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }


        return <<<HTML
        <div class="center-message">
            <h2>Créer un compte utilisateur</h2>
            <form method="POST" style="display:flex; flex-direction:column; gap:12px; width:320px;">
                <input type="email" name="email" placeholder="Email" required class="catalogue-form-input">
                <input type="password" name="password" placeholder="Mot de passe" required class="catalogue-form-input">
                <input type="password" name="password2" placeholder="Confirmer le mot de passe" required class="catalogue-form-input">
                <input type="text" name="carte" placeholder="Numéro de carte" required class="catalogue-form-input">
                <button type="submit" class="btn-center">Créer le compte</button>
            </form>
            $message
        </div>
HTML;
    }
}
