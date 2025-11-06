<?php
namespace Service\action\profil;

use Service\action\Action;
use Service\repository\DeefyRepository;

class AddProfilAction extends Action
{
    public function getResult(): string
    {
        if (!isset($_SESSION['user'])) {
            return "<p>Vous devez être connecté pour créer un profil.</p>";
        }

        $pdo = DeefyRepository::getInstance()->getPDO();
        $idUtilisateur = $_SESSION['user']['id'];
        $message = "";

        // --- Dossier des avatars ---
        $avatarsDir = __DIR__ . '/../../../../IMG/Profil/';
        $avatarsUrl = 'IMG/Profil/';
        $avatars = [];

        if (is_dir($avatarsDir)) {
            foreach (scandir($avatarsDir) as $file) {
                if (preg_match('/\.(png|jpg|jpeg)$/i', $file)) {
                    $avatars[] = $file;
                }
            }
        }

        // Avatar par défaut
        $defaultAvatar = $avatars[0] ?? '';

        // --- Traitement du formulaire ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
            $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
            $genre_prefere = isset($_POST['genre_prefere']) ? trim($_POST['genre_prefere']) : '';
            $img = isset($_POST['img']) && trim($_POST['img']) !== '' ? trim($_POST['img']) : $defaultAvatar;

            if (empty($username)) {
                $message = "<p style='color:red;'>Le nom d'utilisateur est obligatoire.</p>";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO profil (username, nom, prenom, genre_prefere, id_utilisateur, img_profil)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$username, $nom, $prenom, $genre_prefere, $idUtilisateur, $img]);

                $message = "<p style='color:green;'>Profil créé avec succès !</p>
                            <a href='?action=ChoisirProfilAction'>Retour à la sélection</a>";
            }
        }

        // --- Formulaire centré ---
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
                <input type="text" name="genre_prefere"><br><br>

                <h3>Choisir une image de profil</h3>
                <div class="avatar-selection">
HTML;

        foreach ($avatars as $file) {
            $url = $avatarsUrl . $file;
            $html .= "<img src='$url' data-file='$file' class='avatar-choice'>";
        }

        $html .= <<<HTML
                </div>
                <input type="hidden" name="img" id="selected-avatar" value="$defaultAvatar">
                <br><button type="submit">Créer le profil</button>
            </form>
            $message
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
