<?php
namespace Service\action;

use Service\repository\DeefyRepository;

class DefaultAction extends Action
{
    public function getResult(): string
    {
        // Vérifie si un utilisateur est connecté
        if (!isset($_SESSION['user'])) {
            return '<h2>Bienvenue !</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        $idUtilisateur = (int) $_SESSION['user']['id'];
        $pdo = DeefyRepository::getInstance()->getPDO();

        // Récupère la liste des épisodes “en cours” pour cet utilisateur
        $stmt = $pdo->prepare("
            SELECT e.id_episode, e.titre, e.numero_episode, s.titre_serie
            FROM en_cours ec
            JOIN episode e ON ec.id_episode = e.id_episode
            JOIN serie s ON e.id_serie = s.id_serie
            WHERE ec.id_profil = :id_profil
            ORDER BY e.id_serie, e.numero_episode
        ");
        $stmt->execute(['id_profil' => $id_profil]);
        $episodes = $stmt->fetchAll();

        $html = "<h2>Bienvenue, {$_SESSION['user']['email']} !</h2>";

        if (!$episodes) {
            $html .= "<p>Vous n'avez aucun épisode en cours.</p>";
        } else {
            $html .= "<h3>Vos épisodes en cours :</h3><ul class='episodes-en-cours'>";
            foreach ($episodes as $ep) {
                $titreSerie = htmlspecialchars($ep['titre_serie']);
                $titreEpisode = htmlspecialchars($ep['titre']);
                $numero = htmlspecialchars($ep['numero_episode']);
                $idEp = (int)$ep['id_episode'];

                $html .= "
                <li>
                    <strong>{$titreSerie}</strong> - Épisode {$numero} : 
                    <a href='?action=AfficherEpisode&id={$idEp}'>{$titreEpisode}</a>
                </li>";
            }
            $html .= "</ul>";
        }

        // Tu peux ajouter ici la liste de préférences ou autres sections si nécessaire
        return $html;
    }
}
