<?php
namespace Service\action\profil;

use Service\action\Action;
use Service\repository\DeefyRepository;

class ChoisirProfilAction extends Action
{
    public function getResult(): string
    {
        if (!isset($_SESSION['user'])) {
            return <<<HTML
    <div class="center-message">
        <h2>Il faut se connecter pour choisir un profil.</h2>
        <div class="btn-container">
            <a href="?action=SignIn" class="btn-center">Se connecter</a>
            <a href="?action=AddUser" class="btn-center">S’inscrire</a>
        </div>
    </div>
HTML;
        }

        $pdo = DeefyRepository::getInstance()->getPDO();
        $idUtilisateur = (int) $_SESSION['user']['id_utilisateur'];

        // --- Récupération des profils de l'utilisateur ---
        $stmt = $pdo->prepare("
            SELECT p.id_profil, p.username, p.img_profil
            FROM profil p
            WHERE p.id_utilisateur = ?
        ");
        $stmt->execute([$idUtilisateur]);
        $profils = $stmt->fetchAll();

        if (!$profils) {
            return <<<HTML
                <div class="center-message">
                <h2>Aucun profil trouvé</h2>
                <p>Vous n'avez pas encore créé de profil.</p>
                <a href='?action=AddProfilAction' class='btn-center'>Créer un profil</a>
            </div>
        HTML;
        }


        // --- Calcul dynamique du chemin des images (avatars) ---
        $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // chemin relatif à la racine Web
        $imgPrefix = $baseUrl . '/img/';
        $avatarsUrl = $imgPrefix . 'Profil/';

        // --- Affichage des profils avec avatars ---
        $html = "<h2>Choisir un profil</h2>
                 <div class='liste-profils' style='display:flex; gap:20px; flex-wrap:wrap; justify-content:center;'>";

        foreach ($profils as $p) {
            $username = htmlspecialchars($p['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $avatar = htmlspecialchars($p['img_profil'] ?? 'default.png', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $html .= "<div class='profil-item' style='text-align:center;'>
                        <a href='?action=ProfilActifAction&id={$p['id_profil']}' class='profil-lien' style='text-decoration:none; color:#fff;'>
                            <img src='{$avatarsUrl}{$avatar}' alt='{$username}' 
                                 style='width:80px; height:80px; border-radius:50%; object-fit:cover; display:block; margin-bottom:8px;'>
                            <span>{$username}</span>
                        </a>
                      </div>";
        }


        $nbProfils = count($profils);
        if($nbProfils<4) {

            // --- Bouton "Ajouter un profil" ---
            $html .= "<div class='profil-item' style='text-align:center;'>
            <a href='?action=AddProfilAction' class='profil-lien' style='text-decoration:none; color:#fff;'>
                <img src='{$avatarsUrl}add.png' alt='Ajouter un profil'
                     style='width:80px; height:80px; border-radius:50%; object-fit:cover; display:block; margin-bottom:8px;'>
                <span>Ajouter</span>
            </a>
          </div>";
        }


        $html .= "</div>";

        // --- Bouton de déconnexion du profil ---
        if (!empty($_SESSION['profil'])) {
            $html .= "<div class='profil-deconnexion' style='text-align:center; margin-top:30px;'>
                        <a href='?action=SignoutProfilAction' class='btn-deconnexion' 
                           style='padding:10px 20px; background:#4d7aff; color:#fff; border-radius:8px; text-decoration:none;'>
                           Se déconnecter du profil
                        </a>
                      </div>";
        }

        return $html;
    }
}
