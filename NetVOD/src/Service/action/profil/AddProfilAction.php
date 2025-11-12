<?php
namespace Service\action\profil;

use Service\action\Action;
use Service\repository\DeefyRepository;

class AddProfilAction extends Action
{
    public function getResult(): string
    {
        if (!isset($_SESSION['user'])) {
            return <<<HTML
    <div class="center-message">
        <h2>Il faut se connecter pour accéder au profil.</h2>
        <div class="btn-container">
            <a href="?action=SignIn" class="btn-center">Se connecter</a>
            <a href="?action=AddUser" class="btn-center">S’inscrire</a>
        </div>
    </div>
HTML;
        }

        $pdo = DeefyRepository::getInstance()->getPDO();
        $idUtilisateur = $_SESSION['user']['id_utilisateur'];
        $message = "";

        // --- Vérifie la limite de profils
        $stmt = $pdo->prepare("
            SELECT p.id_profil, p.username, p.img_profil
            FROM profil p
            WHERE p.id_utilisateur = ?
        ");
        $stmt->execute([$idUtilisateur]);
        $profils = $stmt->fetchAll();

        if (count($profils) >= 4) {
            return "<p>Vous avez atteint la limite de création de profils.</p>";
        }

        // --- Récupération des genres depuis la base ---
        $genres = $pdo->query("SELECT DISTINCT libelle FROM genre ORDER BY libelle")->fetchAll();

        // --- Dossier des avatars ---
        $avatarsDir = __DIR__ . '/../../../../img/Profil/';
        $avatars = [];

        // --- Calcul dynamique du chemin des images ---
        $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $avatarsUrl = $baseUrl . '/img/Profil/';

        if (is_dir($avatarsDir)) {
            foreach (scandir($avatarsDir) as $file) {
                if (preg_match('/\.(png|jpg|jpeg)$/i', $file)) {
                    $avatars[] = $file;
                }
            }
        }

        $defaultAvatar = $avatars[0] ?? 'DefaultProfil.png';

        // --- Traitement du formulaire ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $genre_prefere = trim($_POST['genre_prefere'] ?? '');
            $img = trim($_POST['img'] ?? $defaultAvatar);

            if (empty($username)) {
                $message = "<p style='color:red;'>Le nom d'utilisateur est obligatoire.</p>";
            } else {
                // Insertion du profil
                $stmt = $pdo->prepare("
                    INSERT INTO profil (username, nom, prenom, genre_prefere, id_utilisateur, img_profil)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$username, $nom, $prenom, $genre_prefere, $idUtilisateur, $img]);

                // On récupère l'ID du profil créé
                $idProfilCree = $pdo->lastInsertId();

                // Connexion automatique au nouveau profil
                $_SESSION['profil'] = [
                    'id_profil'  => $idProfilCree,
                    'username'   => $username,
                    'img_profil' => $img
                ];

                // Redirection automatique vers la page d’accueil
                header("Location: ?action=DefaultAction");
                exit();
            }
        }

        // --- Formulaire HTML ---
        $html = <<<HTML
        <div style="display:flex; flex-direction:column; align-items:center; justify-content:center;">
            <h2>Créer un nouveau profil</h2>
            <form method="POST" id="form-profil" style="display:flex; flex-direction:column; align-items:center;">
                <label>Nom d'utilisateur :</label><br>
                <input type="text" name="username" required><br>

                <label>Nom :</label><br>
                <input type="text" name="nom"><br>

                <label>Prénom :</label><br>
                <input type="text" name="prenom"><br>

                <label>Genre préféré :</label><br>
                <select name="genre_prefere" required>
                    <option value="">-- Choisir un genre --</option>
HTML;

        foreach ($genres as $g) {
            $lib = htmlspecialchars($g['libelle']);
            $html .= "<option value='{$lib}'>{$lib}</option>";
        }

        $html .= <<<HTML
                </select><br><br>

                <h3>Choisir une image de profil</h3>
                <div class="avatar-selection">
HTML;

        foreach ($avatars as $file) {
            if ($file !== "add.png") {
                $fileSafe = htmlspecialchars($file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $html .= "<img src='{$avatarsUrl}{$fileSafe}' data-file='{$fileSafe}' class='avatar-choice' alt='{$fileSafe}'>";
            }
        }

        $defaultAvatarSafe = htmlspecialchars($defaultAvatar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $html .= <<<HTML
                </div>
                <input type="hidden" name="img" id="selected-avatar" value="{$defaultAvatarSafe}">
                <br><button type="submit">Créer le profil</button>
            </form>
            {$message}
        </div>

        <script>
        const avatars = document.querySelectorAll('.avatar-choice');
        const input = document.getElementById('selected-avatar');

        avatars.forEach(img => {
            img.addEventListener('click', () => {
                avatars.forEach(i => i.classList.remove('selected'));
                img.classList.add('selected');
                input.value = img.dataset.file;
            });
        });
        </script>
HTML;

        return $html;
    }
}
