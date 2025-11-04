<?php

namespace Service\action;

use Service\repository\DeefyRepository;

class AfficherSerie extends Action
{
    public function getResult(): string
    {
        // --- Vérification de la connexion ---
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        // --- Vérification d’un ID de série ---
        if (!isset($_GET['id'])) {
            return "<p>Aucune série sélectionnée.</p>";
        }

        $idSerie = (int) $_GET['id'];
        $pdo = DeefyRepository::getInstance()->getPDO();

        // --- Récupération des informations de la série ---
        $stmt = $pdo->prepare("
            SELECT S.id_serie, S.titre_serie, S.descriptif, S.annee, S.date_ajout,
                   G.libelle AS genre, P.libelle AS public
            FROM serie S
            LEFT JOIN genre2serie GS ON S.id_serie = GS.id_serie
            LEFT JOIN genre G ON GS.id_genre = G.id_genre
            LEFT JOIN public2serie PS ON S.id_serie = PS.id_serie
            LEFT JOIN public_cible P ON PS.id_public = P.id_public
            WHERE S.id_serie = ?
        ");
        $stmt->execute([$idSerie]);
        $serie = $stmt->fetch();

        if (!$serie) {
            return "<p>❌ Série introuvable.</p>";
        }

        // --- Sécurisation des données ---
        $titre = htmlspecialchars($serie['titre_serie'] ?? '');
        $genre = htmlspecialchars($serie['genre'] ?? 'Inconnu');
        $public = htmlspecialchars($serie['public'] ?? 'Non précisé');
        $desc = htmlspecialchars($serie['descriptif'] ?? '');
        $annee = htmlspecialchars($serie['annee'] ?? '');
        $dateAjout = htmlspecialchars($serie['date_ajout'] ?? '');

        // --- Récupération du nombre d’épisodes ---
        $stmtCount = $pdo->prepare("SELECT COUNT(*) AS nb FROM episode WHERE id_serie = ?");
        $stmtCount->execute([$idSerie]);
        $nbEpisodes = (int) $stmtCount->fetchColumn();

        // --- En-tête série ---
        $html = "
        <div class='serie-details'>
            <h2>{$titre}</h2>
            <p><strong>Genre :</strong> {$genre}</p>
            <p><strong>Public visé :</strong> {$public}</p>
            <p><strong>Description :</strong> {$desc}</p>
            <p><strong>Année de sortie :</strong> {$annee}</p>
            <p><strong>Date d’ajout :</strong> {$dateAjout}</p>
            <p><strong>Nombre d’épisodes :</strong> {$nbEpisodes}</p>
        </div>";



        "<h3>Liste des épisodes</h3>
        ";

        // --- Bouton pour ajouter aux favoris ---
        $html .= "
            <form method='post' action='?action=ajouterFavorisAction&id={$idSerie}'>
                <button type='submit' class='btn-favori'>Ajouter à mes favoris</button>
            </form>
        ";
        echo "<br>";

        // --- Liste des épisodes ---
        $stmt2 = $pdo->prepare("
            SELECT id_episode, numero_episode, titre, duree
            FROM episode
            WHERE id_serie = ?
            ORDER BY numero_episode ASC
        ");
        $stmt2->execute([$idSerie]);
        $episodes = $stmt2->fetchAll();


        if (empty($episodes)) {
            $html .= "<p>Aucun épisode disponible.</p>";
        } else {
            $html .= "<div class='episodes-grid'>";
            foreach ($episodes as $ep) {
                $num = (int)($ep['numero_episode'] ?? 0);
                $titreEp = htmlspecialchars($ep['titre'] ?? '');
                $duree = htmlspecialchars($ep['duree'] ?? '');
                $idEp = (int)($ep['id_episode'] ?? 0);

                $html .= "
                    <div class='episode-card'>
                        <img src='../../../img/a.jpg' alt='Image épisode {$num}' class='episode-img'>
                        <div class='episode-info'>
                            <a href='?action=afficherEpisode&id={$idEp}'><strong>Épisode {$num}</strong> : {$titreEp}</a>
                            <p>Durée : {$duree} min</p>
                        </div>
                    </div>
                ";
            }
            $html .= "</div>";
        }



        // --- Lien de retour ---
        $html .= "<p><a href='?action=Catalogue' class='btn-retour'>← Retour au catalogue</a></p>";

        return $html;
    }
}
