<?php
namespace Service\action;

use Service\repository\DeefyRepository;

class AddProfilAction extends Action
{
    public function getResult(): string
    {
        // Vérifie si l'utilisateur est connecté
        if (!isset($_SESSION['user'])) {
            return "<p>Vous devez être connecté pour créer un profil.</p>";
        }

        $pdo = DeefyRepository::getInstance()->getPDO();
        $idUtilisateur = (int)$_SESSION['user']['id'];

        // --- GET : afficher le formulaire de création ---
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM profil2utilisateur WHERE id_utilisateur = ?");
            $stmt->execute([$idUtilisateur]);
            $nbProfils = (int)$stmt->fetchColumn();

            if ($nbProfils >= 4) {
                return "<p>Vous avez déjà atteint le nombre maximal de 4 profils.</p>";
            }

            return <<<HTML
            <div class="max-w-md mx-auto p-6 bg-white rounded shadow-md">
                <h2 class="text-2xl font-bold mb-4">Créer un nouveau profil</h2>
                <form method="post" action="?action=addProfilAction" class="space-y-4">
                    <div>
                        <label for="username" class="block font-semibold">Nom du profil :</label>
                        <input id="username" name="username" required class="w-full border px-3 py-2 rounded">
                    </div>
                    <div>
                        <label for="nom" class="block font-semibold">Nom :</label>
                        <input id="nom" name="nom" class="w-full border px-3 py-2 rounded">
                    </div>
                    <div>
                        <label for="prenom" class="block font-semibold">Prénom :</label>
                        <input id="prenom" name="prenom" class="w-full border px-3 py-2 rounded">
                    </div>
                    <div>
                        <label for="genre_prefere" class="block font-semibold">Genre préféré :</label>
                        <input id="genre_prefere" name="genre_prefere" class="w-full border px-3 py-2 rounded">
                    </div>
                    <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                        Créer le profil
                    </button>
                </form>
            </div>
HTML;
        }

        // --- POST : traitement du formulaire ---
        $username = trim($_POST['username'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $genre_prefere = trim($_POST['genre_prefere'] ?? '');

        // Vérifie à nouveau le nombre de profils
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM profil WHERE id_utilisateur = ?");
        $stmt->execute([$idUtilisateur]);
        $nbProfils = (int)$stmt->fetchColumn();

        if ($nbProfils >= 4) {
            return "<p>Vous avez déjà 4 profils. Impossible d'en créer un autre.</p>";
        }

        // Insertion du profil dans la table 'profil'
        $stmt = $pdo->prepare("
            INSERT INTO profil (username, nom, prenom, genre_prefere, id_utilisateur) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$username, $nom, $prenom, $genre_prefere, $idUtilisateur]);
        $idProfil = (int)$pdo->lastInsertId();

        // Liaison profil → utilisateur
        $stmt = $pdo->prepare("INSERT INTO profil2utilisateur (id_utilisateur, id_profil) VALUES (?, ?)");
        $stmt->execute([$idUtilisateur, $idProfil]);

        // Enregistrer le profil actif en session
        $_SESSION['profil'] = [
            'id_profil' => $idProfil,
            'username' => $username,
            'nom' => $nom,
            'prenom' => $prenom,
            'genre_prefere' => $genre_prefere
        ];

        return "<p>Profil <strong>{$username}</strong> créé avec succès et activé !</p>
                <p><a href='?action=default'>Retour à l'accueil</a></p>";
    }
}
