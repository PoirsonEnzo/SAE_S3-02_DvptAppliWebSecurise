<?php
namespace Service\action;

use Service\repository\DeefyRepository;

class RechercheMotCleAction extends Action
{
    public function getResult(): string
    {
        // Vérifie la connexion
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        $pdo = DeefyRepository::getInstance()->getPDO();

        // --- Récupération du mot clé ---
        $motCle = $_GET['search'] ?? '';
        $motCleSQL = '%' . $motCle . '%';

        $html = "
        <h2>Résultats de recherche</h2>
        <form method='get' action='' class='search-bar'>
            <input type='hidden' name='action' value='RechercheMotCle'>
            <input type='text' name='search' placeholder='Rechercher une série...' value='" . htmlspecialchars($motCle) . "'>
            <button type='submit'>🔍</button>
        </form>
        ";

        if (!empty($motCle)) {
            $stmt = $pdo->prepare("SELECT id_serie, titre_serie, descriptif FROM serie 
                                   WHERE titre_serie LIKE ? OR descriptif LIKE ?");
            $stmt->execute([$motCleSQL, $motCleSQL]);
        } else {
            $stmt = $pdo->query("SELECT id_serie, titre_serie, descriptif FROM serie");
        }

        $results = $stmt->fetchAll();

        if (empty($results)) {
            $html .= "<p>Aucune série trouvée pour « " . htmlspecialchars($motCle) . " ».</p>";
        } else {
            $html .= "<div class='series-grid'>";
            foreach ($results as $data) {
                $titre = htmlspecialchars($data['titre_serie']);
                $desc = htmlspecialchars(substr($data['descriptif'], 0, 120)) . '...';
                $id = (int)$data['id_serie'];

                $html .= "
                    <div class='serie-card'>
                        <img src='../../../img/a.jpg' alt='Image de la série {$titre}' class='serie-img'>
                        <div class='serie-info'>
                            <a href='?action=AfficherSerie&id={$id}'><strong>{$titre}</strong></a>
                            <p>{$desc}</p>
                        </div>
                    </div>
                ";
            }
            $html .= "</div>";
        }

        return $html;
    }
}
