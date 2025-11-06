<?php

namespace Service\action\affichage;

use PDO;
use Service\action\Action;
use Service\repository\DeefyRepository;

class AfficherSerie extends Action
{
    public function getResult(): string
    {
        if (!isset($_SESSION['user'])) {
            return '<br><h2>Il faut se connecter.</h2>
                    <p><a href="?action=SignIn">Se connecter</a> ou 
                    <a href="?action=AddUser">S’inscrire</a></p>';
        }

        if (!isset($_GET['id'])) {
            return "<p>Aucune série sélectionnée.</p>";
        }

        $idSerie = (int) $_GET['id'];
        $pdo = DeefyRepository::getInstance()->getPDO();

        // --- Récupération des informations principales de la série ---
        $stmt = $pdo->prepare("
            SELECT id_serie, titre_serie, descriptif, annee, date_ajout
            FROM serie
            WHERE id_serie = ?
        ");
        $stmt->execute([$idSerie]);
        $serie = $stmt->fetch();
        if (!$serie) {
            return "<p>Série introuvable.</p>";
        }

        $titre = htmlspecialchars($serie['titre_serie']);
        $desc = htmlspecialchars($serie['descriptif'] ?? '');
        $annee = htmlspecialchars($serie['annee'] ?? '');
        $dateAjout = htmlspecialchars($serie['date_ajout'] ?? '');

        // --- Récupération des genres associés ---
        $stmtGenres = $pdo->prepare("
            SELECT G.libelle 
            FROM genre2serie GS
            JOIN genre G ON GS.id_genre = G.id_genre
            WHERE GS.id_serie = ?
        ");
        $stmtGenres->execute([$idSerie]);
        $genres = $stmtGenres->fetchAll(PDO::FETCH_COLUMN);
        $genreText = $genres ? htmlspecialchars(implode(', ', $genres)) : 'Inconnu';

        // --- Récupération des publics associés ---
        $stmtPublics = $pdo->prepare("
            SELECT P.libelle 
            FROM public2serie PS
            JOIN public_cible P ON PS.id_public = P.id_public
            WHERE PS.id_serie = ?
        ");
        $stmtPublics->execute([$idSerie]);
        $publics = $stmtPublics->fetchAll(PDO::FETCH_COLUMN);
        $publicText = $publics ? htmlspecialchars(implode(', ', $publics)) : 'Non précisé';

        // --- Récupération du nombre d'épisodes ---
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM episode WHERE id_serie = ?");
        $stmtCount->execute([$idSerie]);
        $nbEpisodes = (int) $stmtCount->fetchColumn();

        // --- Construction HTML ---
        $html = "
        <div class='serie-details'>
            <h2>{$titre}</h2>
            <p><strong>Genre :</strong> {$genreText}</p>
            <p><strong>Public visé :</strong> {$publicText}</p>
            <p><strong>Description :</strong> {$desc}</p>
            <p><strong>Année de sortie :</strong> {$annee}</p>
            <p><strong>Date d’ajout :</strong> {$dateAjout}</p>
            <p><strong>Nombre d’épisodes :</strong> {$nbEpisodes}</p>
            ";

        $idProfil = (int) $_SESSION['profil']['id_profil'];

        // Vérifier si déjà en favoris
        $check = $pdo->prepare("SELECT * FROM favoris WHERE id_profil = ? AND id_serie = ?");
        $check->execute([$idProfil, $idSerie]);

        if (!$check->fetch()) {
            $html .= " 
                <div class='favoris-container'>
                    <form method='post' action='?action=AjouterFavoris&id={$idSerie}'>
                        <button type='submit' class='btn-favori'>Ajouter à mes favoris</button>
                    </form>
                </div>
            </div>
    
            <h3>Liste des épisodes</h3>
            ";
        }else{
            $html .= " 
                <div class='favoris-container'>
                    <form method='post' action='?action=SupFavoris&id={$idSerie}'>
                        <button type='submit' class='btn-favori'>Supprimer de mes favoris</button>
                    </form>
                </div>
            </div>
    
            <h3>Liste des épisodes</h3>
            ";
        }


        // --- Liste des épisodes ---
        $stmtEpisodes = $pdo->prepare("
            SELECT id_episode, numero_episode, titre, duree, img
            FROM episode
            WHERE id_serie = ?
            ORDER BY numero_episode ASC
        ");
        $stmtEpisodes->execute([$idSerie]);
        $episodes = $stmtEpisodes->fetchAll();

        if (!$episodes) {
            $html .= "<p>Aucun épisode disponible.</p>";
        } else {
            $html .= "<div class='episodes-grid'>";
            foreach ($episodes as $ep) {
                $num = (int)($ep['numero_episode']);
                $titreEp = htmlspecialchars($ep['titre']);
                $duree = htmlspecialchars($ep['duree']);
                $idEp = (int)($ep['id_episode']);
                $imgFile = htmlspecialchars($ep['img'] ?? 'default.png');


                $html .= "
                    <div class='episode-card'>
                    <a href='?action=AfficherEpisode&id={$idEp}'>
                        <img src='img/{$imgFile}' alt='Image épisode {$num}' class='episode-img'>
                        <div class='episode-info'>  
                            
                                <strong>Épisode {$num}</strong> : {$titreEp}
                            </a>
                            <p>Durée : {$duree} secondes</p>
                        </div>
                    </div>
                ";
            }
            $html .= "</div>";
        }

        $html .= "<p><a href='?action=Catalogue' class='btn-retour'>← Retour au catalogue</a></p>";

        return $html;
    }
}
