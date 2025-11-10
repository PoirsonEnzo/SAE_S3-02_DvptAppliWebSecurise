<?php
namespace Service\action\compte;

use Service\action\Action;
use Service\auth\AuthnProvider;

class SigninAction extends Action
{
    public function getResult(): string
    {
        $message = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';

            try {
                $user = AuthnProvider::signin($email, $password);
                $_SESSION['user'] = $user;
                $_SESSION['user_role'] = $user['role'];

                $message = <<<HTML
                <p style="color:green; font-weight:bold;">Connexion réussie ! Bienvenue, <strong>{$user['email']}</strong>.</p>
                <a href="?action=home" class="btn-center">Retour à l'accueil</a>
HTML;

            } catch (\Exception $e) {
                $msg = htmlspecialchars($e->getMessage());

                // Si le message contient un token d'activation, crée un lien cliquable
                if (preg_match("/token=([a-f0-9]{64})/", $msg, $matches)) {
                    $token = $matches[1];
                    $message = <<<HTML
                        <p style="color:red; font-weight:bold;">
                            Votre compte n’est pas encore activé.
                        </p>
                        <a href="?action=activateAccount&token=$token" class="btn-center" 
                           style="display:inline-block; margin-top:5px; padding:6px 12px; background:#7aa9ff; color:white; text-decoration:none; border-radius:4px;">
                            Recevoir un nouveau lien d'activation
                        </a>
HTML;
                } else {
                    $message = "<p style='color:red; font-weight:bold;'>Erreur : {$msg}</p>";
                }
            }
        }

        return <<<HTML
        <div class="center-message">
            <h2>Connexion</h2>
            <form method="POST" style="display:flex; flex-direction:column; gap:12px; width:320px;">
                <input type="email" name="email" placeholder="Email" required class="catalogue-form-input">
                <input type="password" name="password" placeholder="Mot de passe" required class="catalogue-form-input">
                <button type="submit" class="btn-center">Se connecter</button>
            </form>
            <p style="margin-top:10px;">
                <a href="?action=ForgotPassword" class="btn-center" style="background:#7aa9ff;">Mot de passe oublié ?</a>
            </p>
            $message
        </div>
HTML;
    }
}
