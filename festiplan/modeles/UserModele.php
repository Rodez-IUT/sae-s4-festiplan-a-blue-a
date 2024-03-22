<?php

namespace modeles;

use PDO;
use PDOException;

class UserModele
{
    /**
     * Cherche un compte Festiplan dans la base de données par rapport au login
     * et au mot ed passe.
     * @param pdo un objet PDO connecté à la base de données.
     * @param login le login entré par un utilisateur.
     * @param pwd le mot de passe entré par un utilisateur.
     * @return searchStmt les données trouvées par rapport au login et mot de
     * passe.
     */
    public function trouverCompteUtilisateurParLoginMdp(PDO $pdo, $login, $mdp)
    {
        $sql = "SELECT idUtilisateur FROM utilisateur WHERE login = ? AND mdp = ?";
        $searchStmt = $pdo->prepare($sql);
        $searchStmt->execute([$login, $mdp]);
        return $searchStmt;
    }

    /**
    * @param pdo
    * @param login le login choisi par l'utilisateur, doit être unique dans la
    * base de données.
    * @param mdp mot de passe entré par l'utilisateur.
    * @param nom nom entré par l'utilisateur.
    * @param prenom prenom entré par l'utilisateur.
    * @param email mail entré par l'utilisateur, doit être unique dans la base
    * de données.
    * Insert un utilisateur dans la base de données afin de créer un compte.
    */
    public function creerCompteUtilisateur(PDO $pdo, $login, $mdp, $nom, $prenom, $email)
    {
        try {
            // Début de la transaction
            $pdo->beginTransaction();
            // Requête d'insertion
            $sql = "INSERT INTO utilisateur (login, mdp, nom, prenom, mail) VALUES (?,?,?,?,?)";
            $searchStmt = $pdo->prepare($sql);
            $searchStmt->execute([$login, $mdp, $nom, $prenom, $email]);
        
            // Fin de la transaction (enregistrement des modifications)
            $pdo->commit();
        } catch (PDOException $e) {
            // En cas d'erreur, annuler la transaction
            $pdo->rollBack();
            echo "Erreur : " . $e->getMessage();
        }
    }

    // Fonction pour vérifier si l'email existe déjà
    function emailExisteDeja($pdo, $email) {
        $sql = "SELECT COUNT(*) FROM utilisateur WHERE mail = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $count = $stmt->fetchColumn();
        return ($count > 0);
    }

    // Fonction pour vérifier si le login existe déjà
    function loginExisteDeja($pdo, $login) {
        $sql = "SELECT COUNT(*) FROM utilisateur WHERE login = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$login]);
        $count = $stmt->fetchColumn();
        return ($count > 0);
    }


    public function modifierCompteUtilisateur(PDO $pdo, $login, $mdp, $nom, $prenom, $email) {
        try {
            // Début de la transaction
            $pdo->beginTransaction();
            
            // Requête de mise à jour
            if ($mdp !== null && $mdp !== "" && strlen($mdp) !== 0) {
                $sql = "UPDATE utilisateur SET mdp = ?, nom = ?, prenom = ?, login = ?, mail = ? WHERE idUtilisateur = ?";
                $updateStmt = $pdo->prepare($sql);
                $updateStmt->execute([$mdp, $nom, $prenom, $login, $email, $_SESSION['id_utilisateur']]);
            } else {
                $sql = "UPDATE utilisateur SET nom = ?, prenom = ?, login = ?, mail = ? WHERE idUtilisateur = ?";
                $updateStmt = $pdo->prepare($sql);
                $updateStmt->execute([$nom, $prenom, $login, $email, $_SESSION['id_utilisateur']]);
            }
            
    
            // Fin de la transaction (enregistrement des modifications)
            $pdo->commit();
        } catch (PDOException $e) {
            // En cas d'erreur, annuler la transaction
            $pdo->rollBack();
            echo "Erreur : " . $e->getMessage();
        }
    }

    public function recupererInformationsProfil(PDO $pdo, $id) {            
        $sql = "SELECT login, nom, prenom, mail, mdp FROM utilisateur WHERE idUtilisateur = ?";
        $searchStmt = $pdo->prepare($sql);
        $searchStmt->execute([$id]);
        return $searchStmt;
    }
    
    public function supprimerCompteUtilisateur(PDO $pdo, $idUtilisateur) {
        // Supprimer de EquipeOrganisatrice
        $sqlUn = "DELETE FROM equipeorganisatrice WHERE idUtilisateur = ?";
        $deleteStmtUn = $pdo->prepare($sqlUn);
        $deleteStmtUn->execute([$idUtilisateur]);
    
        // Supprimer de SpectacleOrganisateur
        $sqlDeux = "DELETE FROM spectacleorganisateur WHERE idUtilisateur = ?";
        $deleteStmtDeux = $pdo->prepare($sqlDeux);
        $deleteStmtDeux->execute([$idUtilisateur]);
    
        // Supprimer de SpectacleDeFestival
        $sqlQuatre = "DELETE FROM spectacledefestival WHERE idSpectacle IN (SELECT idSpectacle FROM spectacleorganisateur WHERE idUtilisateur = ?)";
        $deleteStmtQuatre = $pdo->prepare($sqlQuatre);
        $deleteStmtQuatre->execute([$idUtilisateur]);
    
        // Supprimer de SpectaclesJour
        $sqlCinq = "DELETE FROM spectaclesjour WHERE idSpectacle IN (SELECT idSpectacle FROM spectacleorganisateur WHERE idUtilisateur = ?)";
        $deleteStmtCinq = $pdo->prepare($sqlCinq);
        $deleteStmtCinq->execute([$idUtilisateur]);

        // Supprimer de Jour
        $sqlNeuf = "DELETE FROM jour WHERE idJour NOT IN (SELECT idJour FROM spectaclesjour)";
        $deleteStmtNeuf = $pdo->prepare($sqlNeuf);
        $deleteStmtNeuf->execute();

        // Supprimer de Grij
        $sqlHuit = "DELETE FROM grij WHERE idGrij NOT IN (SELECT idGrij FROM jour)";
        $deleteStmtHuit = $pdo->prepare($sqlHuit);
        $deleteStmtHuit->execute();

        // Supprimer de Festival (où l'utilisateur est responsable)
        $sqlSix = "DELETE FROM festival WHERE idFestival IN (SELECT idFestival FROM equipeorganisatrice WHERE idUtilisateur = ? AND responsable = 1)";
        $deleteStmtSix = $pdo->prepare($sqlSix);
        $deleteStmtSix->execute([$idUtilisateur]);

        // Supprimer de Spectacle (de l'utilisateur)
        $sqlOnze = "DELETE FROM spectacle WHERE idSpectacle IN (SELECT idSpectacle FROM spectacleorganisateur WHERE idUtilisateur = ?)";
        $deleteStmtOnze = $pdo->prepare($sqlOnze);
        $deleteStmtOnze->execute([$idUtilisateur]);
    
        // Supprimer de Utilisateur
        $sqlDix = "DELETE FROM utilisateur WHERE idUtilisateur = ?";
        $deleteStmtDix = $pdo->prepare($sqlDix);
        $deleteStmtDix->execute([$idUtilisateur]);
    
        return ($deleteStmtUn && $deleteStmtDeux && $deleteStmtQuatre && $deleteStmtCinq && $deleteStmtSix && $deleteStmtHuit && $deleteStmtNeuf && $deleteStmtDix);
    }
    
    
}