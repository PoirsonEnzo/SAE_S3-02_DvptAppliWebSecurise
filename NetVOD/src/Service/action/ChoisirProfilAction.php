<?php
namespace Service\action;

use Service\repository\DeefyRepository;

class ChoisirProfilAction extends Action
{
    public function getResult(): string
    {
        if (!isset($_SESSION['user'])) {
            return "<p>Vous devez être connecté pour choisir un profil.</p>";
        }

        $pdo = DeefyRepository::getInstance()->getPDO();
        $idUtilisateur = (int) $_SESSION['user']['id'];

        // Récupérer tous les profils de l'utilisateur
        $stmt = $pdo->prepare("
            SELECT p.id_profil, p.username
            FROM profil p
            JOIN profil p2u ON p.id_profil = p2u.id_profil
            WHERE p2u.id_utilisateur = ?
        ");
        $stmt->execute([$idUtilisateur]);
        $profils = $stmt->fetchAll();

        if (!$profils) {
            return "<p>Aucun profil trouvé. <a href='?action=AddProfilAction'>Créer un profil</a></p>";
        }

        // Construction du HTML à l'intérieur de la méthode
        $html = "<h2>Choisir un profil</h2><ul>";
        foreach ($profils as $p) {
            $html .= "<li>
            <a href='?action=ProfiActiflAction&id={$p['id_profil']}' class='text-blue-500 hover:underline'>
                {$p['username']}
            </a>
          </li>";
        }
        $html .= "</ul>";

        return $html;
    }
}
