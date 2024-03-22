<?php

namespace modeles;

use PDOException;
use PDO;

class FestivalModele
{
    /**
     * Recherche la liste des categories de festival dans la base de données 
     * @param pdo un objet PDO connecté à la base de données.
     * @return searchStmt l'ensemble des categorie de festival
     */
    public function listeCategorieFestival(PDO $pdo)
    {
        $sql = "SELECT * FROM categoriefestival ";
        $searchStmt = $pdo->prepare($sql);
        $searchStmt->execute();
        return $searchStmt;
    }

    /**
     * Insere un festival dans la base de données
     * @param pdo un objet PDO connecté à la base de données.
     * @param nom nom du festival.
     * @param description description du festival.
     * @param dateDebut date de debut du festival.
     * @param dateFin date de fin du festival.
     * @param categorie categorie du festival.
     * @param illustration illustration du festival.
     * @param idOrganisateur l'id de l'utilisateur courant.
     */
    public function insertionFestival(PDO $pdo, $nom, $description, $dateDebut, $dateFin, $categorie, $illustration, $idOrganisateur)
    {
        $sql = "INSERT INTO festival (titre,categorie,description,dateDebut,dateFin,illustration) VALUES (:leNom,:laCate,:laDesc,:leDeb,:laFin,:lIllu)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":leNom", $nom);
        $stmt->bindParam(":laCate", $categorie);
        $stmt->bindParam(":laDesc", $description);
        $stmt->bindParam(":leDeb", $dateDebut);
        $stmt->bindParam(":laFin", $dateFin);
        $stmt->bindParam(":lIllu", $illustration);
        $stmt->execute();

        // Enregistre le créateur du festival en tant qu'organisateur
        $responsable = true;
        $idFestival = $pdo->lastInsertId();

        $sql2 = "INSERT INTO equipeorganisatrice (idUtilisateur, idFestival, responsable) VALUES (:idOrg,:idFestival,:responsable)";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->bindParam(":idOrg", $idOrganisateur);
        $stmt2->bindParam(":idFestival", $idFestival);
        $stmt2->bindParam(":responsable", $responsable);
        $stmt2->execute();
    }

    /**
     * Compte le nombre de festival de l'utilisateur
     * @param pdo un objet PDO connecté à la base de données.
     * @param idOrganisateur l'id de l'utilisateur courant.
     * @return nbFestival le nombre de festivals.
     */
    public function nombreMesFestivals(PDO $pdo, $idOrganisateur)
    {
        $sql = "SELECT Count(festival.idFestival) AS nbFestival FROM festival JOIN equipeorganisatrice ON festival.idFestival=equipeorganisatrice.idFestival JOIN utilisateur ON utilisateur.idUtilisateur=equipeorganisatrice.idUtilisateur WHERE equipeorganisatrice.idUtilisateur = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam("id",$idOrganisateur);
        $stmt->execute();
        // Récupérer le résultat du COUNT
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Maintenant, $result contient le résultat du COUNT
        $nbFestival = $result['nbFestival'];

        return $nbFestival;
    }

    /**
     * Recherche la liste des festivals de l'utilisateur
     * @param pdo un objet PDO connecté à la base de données.
     * @param idOrganisateur l'id de l'utilisateur courant.
     * @return stmt l'ensemble des festivals.
     */
    public function listeMesFestivals(PDO $pdo, $idOrganisateur, $premier)
    {
        $sql = "SELECT festival.titre,utilisateur.nom,festival.idFestival,festival.illustration,equipeorganisatrice.responsable FROM festival JOIN equipeorganisatrice ON festival.idFestival=equipeorganisatrice.idFestival JOIN utilisateur ON utilisateur.idUtilisateur=equipeorganisatrice.idUtilisateur WHERE equipeorganisatrice.idUtilisateur = :id LIMIT 4 OFFSET :nPage";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam("id",$idOrganisateur);
        $stmt->bindParam("nPage",$premier,PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
        
    }

    /**
     * Recherche la liste des responsables de Festivals
     * @param pdo un objet PDO connecté à la base de données.
     * @return stmt l'ensemble des responsables.
     */
    public function listeLesResponsables(PDO $pdo)
    {
        $sql = "SELECT utilisateur.nom,equipeorganisatrice.idFestival FROM equipeorganisatrice JOIN utilisateur ON utilisateur.idUtilisateur=equipeorganisatrice.idUtilisateur WHERE equipeorganisatrice.responsable = true";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt;
    }


    /**
     * Recherche tout les parametre du festival voulu.
     * @param pdo un objet PDO connecté à la base de données.
     * @param idFestival l'id du festival a rechercher.
     * @return fetch lefestival.
     */
    public function leFestival(PDO $pdo, $idFestival)
    {
        $sql = "SELECT * FROM festival WHERE idFestival = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam("id",$idFestival);
        $stmt->execute();
        $fetch = $stmt->fetch();
        return $fetch;
    }

    /**
     * Modifier un festival dans la base de données
     * @param pdo un objet PDO connecté à la base de données.
     * @param nom nom du festival.
     * @param description description du festival.
     * @param dateDebut date de debut du festival.
     * @param dateFin date de fin du festival.
     * @param categorie categorie du festival.
     * @param illustration illustration du festival.
     * @param idFestival l'id de l'utilisateur courant.
     * @return stmt true si cela a marché
     */
    public function modificationFestival(PDO $pdo, $nom, $description, $dateDebut, $dateFin, $categorie, $illustration, $idFestival)
    {   
        try {
            // Début de la transaction
            $pdo->beginTransaction();
            $sql = "UPDATE festival SET titre =:leNom, categorie =:laCate, description =:laDesc, dateDebut =:leDeb, dateFin =:laFin, illustration=:lIllu WHERE idFestival =:id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam("leNom",$nom);
            $stmt->bindParam("laCate",$categorie);
            $stmt->bindParam("laDesc",$description);
            $stmt->bindParam("leDeb",$dateDebut);
            $stmt->bindParam("laFin",$dateFin);
            $stmt->bindParam("lIllu",$illustration);
            $stmt->bindParam("id",$idFestival);
            $stmt->execute();
            // Valider la transaction
            $pdo->commit();
        } catch (PDOException $e) {
            // En cas d'erreur, annuler la transaction
            $pdo->rollBack();
            echo "Erreur : " . $e->getMessage();
        }
    }

    /**
     * Supprime festival voulu
     * @param pdo un objet PDO connecté à la base de données.
     * @param idFestival l'id du festival a supprimer.
     */
    public function supprimerFestival(PDO $pdo, $idFestival)
    {   
        $sql = "DELETE FROM equipeorganisatrice WHERE idFestival = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam("id",$idFestival);
        $stmt->execute();
        $sql2 = "DELETE FROM festival WHERE idFestival = :id";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->bindParam("id",$idFestival);
        $stmt2->execute();
    }

    /**
     * Regarde si l'utilisateur et le responsable du festival voulus.
     * @param pdo un objet PDO connecté à la base de données.
     * @param idFestival l'id du festival.
     * @param idOrganisateur l'id de l'organisateur.
     */
    public function estResponsable($pdo,$idFestival,$idOrganisateur)
    {
        $sql = "SELECT responsable FROM equipeorganisatrice WHERE idFestival =:idFestival AND idUtilisateur =:idUtilisateur";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam("idFestival",$idFestival);
        $stmt->bindParam("idUtilisateur",$idOrganisateur);
        $stmt->execute();
        $fetch = $stmt->fetch();
        return $fetch;
    }

    /**
     * Renvoie la liste des organisateur du festival voulus.
     * @param pdo un objet PDO connecté à la base de données.
     * @param idFestival l'id du festival.
     */
    public function listeOrganisateurFestival($pdo,$idFestival) 
    {
        $sql = "SELECT utilisateur.idUtilisateur,utilisateur.nom,utilisateur.prenom FROM utilisateur JOIN equipeorganisatrice ON utilisateur.idUtilisateur=equipeorganisatrice.idUtilisateur AND idFestival =:idFestival";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam("idFestival",$idFestival);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Renvoie la liste de tout les utilisateurs
     * @param pdo un objet PDO connecté à la base de données.
     */
    public function listeUtilisateur($pdo) 
    {
        $sql = "SELECT idUtilisateur,nom,prenom FROM utilisateur";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Supprime la liste des organisateur d'un festival
     * @param pdo un objet PDO connecté à la base de données.
     * @param idFestival l'id du festival.
     */
    public function supprimerOrganisateurs($pdo,$idFestival) 
    {
        $sql = "DELETE FROM equipeorganisatrice WHERE idFestival = :id AND responsable = false ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam("id",$idFestival);
        $stmt->execute();
    }

    /**
     * Met a jour la liste des organisateur d'un festival
     * @param pdo un objet PDO connecté à la base de données.
     */
    public function majOrganisateur($pdo,$idFestival,$utilisateur) 
    {
        $sql = "INSERT INTO equipeorganisatrice (idUtilisateur, idFestival) VALUES (:idOrg,:idFestival)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam("idOrg",$utilisateur);
        $stmt->bindParam("idFestival",$idFestival);
        $stmt->execute();
    }

    /**
     * Supprimer tout les spectacles d'un festival.
     * @param pdo un objet PDO connecté à la base de données.
     */
    public function supprimerSpectacleDeFestival ($pdo,$idFestival, $idSpectacle) 
    {
        $sql = "DELETE FROM spectacledefestival WHERE idFestival = :idFestival AND idSpectacle = :idSpectacle";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam("idFestival",$idFestival);
        $stmt->bindParam("idSpectacle",$idSpectacle);
        $stmt->execute();
    }

    /**
     * Met a jour la liste des spectacles d'un festival
     * @param pdo un objet PDO connecté à la base de données.
     */
    public function majSpectacleDeFestival ($pdo,$idFestival,$idSpectacle) 
    {
        $sql = "INSERT INTO spectacledefestival (idSpectacle, idFestival) VALUES (:idSpectacle,:idFestival)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam("idSpectacle",$idSpectacle);
        $stmt->bindParam("idFestival",$idFestival);
        $stmt->execute();
    }

    /**
     * Renvoie la liste des spectacles du festival voulu
     * @param pdo un objet PDO connecté à la base de données.
     * @param idFestival l'id du festival.
     */
    public function listeSpectacleDeFestival($pdo,$idFestival) 
    {
        $sql = "SELECT idSpectacle FROM spectacledefestival WHERE idFestival = :id ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam("id",$idFestival);
        $stmt->execute();
        return $stmt;
    }
}