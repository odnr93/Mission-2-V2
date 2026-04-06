<?php
namespace App\MyApp;
use PDO;
use Illuminate\Support\Facades\Config;

/**
 * Classe d'accès aux données. 
 * Utilise l'extension PHP Data Objects (PDO)
 * Les méthodes utilisent désormais des requêtes préparées pour la sécurité.
 */
class PdoGsb{
        private static $serveur;
        private static $bdd;
        private static $user;
        private static $mdp;
        private  $monPdo;
	
	/**
	 * crée l'instance de PDO qui sera sollicitée
	 * pour toutes les méthodes de la classe
	 */				
	public function __construct(){
        self::$serveur='mysql:host=' . Config::get('database.connections.mysql.host');
        self::$bdd='dbname=' . Config::get('database.connections.mysql.database');
        self::$user=Config::get('database.connections.mysql.username') ;
        self::$mdp=Config::get('database.connections.mysql.password');	  
        $this->monPdo = new PDO(self::$serveur.';'.self::$bdd, self::$user, self::$mdp); 
  		$this->monPdo->query("SET CHARACTER SET utf8");
	}

	public function _destruct(){
		$this->monPdo = null;
	}

	/**
	 * Retourne les informations d'un visiteur
	 */
	public function getInfosVisiteur($login, $mdp){
		$req = "select visiteur.id as id, visiteur.nom as nom, visiteur.prenom as prenom 
                from visiteur 
                where visiteur.login = ? and visiteur.mdp = ?";
    	$stmt = $this->monPdo->prepare($req);
		$stmt->execute([$login, $mdp]);
		return $stmt->fetch();
	}
	
	/**
	 * Retourne les informations d'un comptable
	 */
	public function getInfosComptable($login, $mdp){
		$req = "select comptable.id_compt as id, comptable.name as nom, comptable.Prenom as prenom 
                from comptable 
                where comptable.login = ? and comptable.mdp = ?";
    	$stmt = $this->monPdo->prepare($req);
		$stmt->execute([$login, $mdp]);
		return $stmt->fetch();
	}

	/**
	 * Retourne les lignes de frais au forfait d'un visiteur pour un mois donné
	 */
	public function getLesFraisForfait($idVisiteur, $mois){
		$req = "select fraisforfait.id as idfrais, fraisforfait.libelle as libelle, 
		lignefraisforfait.quantite as quantite from lignefraisforfait inner join fraisforfait 
		on fraisforfait.id = lignefraisforfait.idfraisforfait
		where lignefraisforfait.idvisiteur = ? and lignefraisforfait.mois = ?
		order by lignefraisforfait.idfraisforfait";	
		$stmt = $this->monPdo->prepare($req);
		$stmt->execute([$idVisiteur, $mois]);
		return $stmt->fetchAll(); 
	}

	/**
	 * Retourne tous les id de la table FraisForfait
	 */
	public function getLesIdFrais(){
		$req = "select fraisforfait.id as idfrais from fraisforfait order by fraisforfait.id";
		$res = $this->monPdo->query($req);
		return $res->fetchAll();
	}

	/**
	 * Met à jour la table ligneFraisForfait
	 */
	public function majFraisForfait($idVisiteur, $mois, $lesFrais){
		$req = "update lignefraisforfait set lignefraisforfait.quantite = ?
		where lignefraisforfait.idvisiteur = ? and lignefraisforfait.mois = ?
		and lignefraisforfait.idfraisforfait = ?";
		$stmt = $this->monPdo->prepare($req);

		foreach($lesFrais as $unIdFrais => $qte){
			$stmt->execute([$qte, $idVisiteur, $mois, $unIdFrais]);
		}
	}

	/**
	 * Teste si un visiteur possède une fiche de frais pour le mois passé en argument
	 */	
	public function estPremierFraisMois($idVisiteur, $mois) {
		$req = "select count(*) as nblignesfrais from fichefrais 
		where fichefrais.mois = ? and fichefrais.idvisiteur = ?";
		$stmt = $this->monPdo->prepare($req);
		$stmt->execute([$mois, $idVisiteur]);
		$laLigne = $stmt->fetch();
		return ($laLigne['nblignesfrais'] == 0);
	}

	/**
	 * Retourne le dernier mois en cours d'un visiteur
	 */	
	public function dernierMoisSaisi($idVisiteur){
		$req = "select max(mois) as dernierMois from fichefrais where fichefrais.idvisiteur = ?";
		$stmt = $this->monPdo->prepare($req);
		$stmt->execute([$idVisiteur]);
		$laLigne = $stmt->fetch();
		return $laLigne['dernierMois'];
	}
	
	/**
	 * Crée une nouvelle fiche de frais et les lignes de frais au forfait
	 */
	public function creeNouvellesLignesFrais($idVisiteur, $mois){
		$dernierMois = $this->dernierMoisSaisi($idVisiteur);
		$laDerniereFiche = $this->getLesInfosFicheFrais($idVisiteur, $dernierMois);
		
		if($laDerniereFiche && $laDerniereFiche['idEtat'] == 'CR'){
				$this->majEtatFicheFrais($idVisiteur, $dernierMois, 'CL');
		}

		$req = "insert into fichefrais(idvisiteur, mois, nbJustificatifs, montantValide, dateModif, idEtat) 
		values(?, ?, 0, 0, now(), 'CR')";
		$stmt = $this->monPdo->prepare($req);
		$stmt->execute([$idVisiteur, $mois]);

		$lesIdFrais = $this->getLesIdFrais();
		$reqIns = "insert into lignefraisforfait(idvisiteur, mois, idFraisForfait, quantite) values(?, ?, ?, 0)";
		$stmtIns = $this->monPdo->prepare($reqIns);
		foreach($lesIdFrais as $uneLigneIdFrais){
			$stmtIns->execute([$idVisiteur, $mois, $uneLigneIdFrais['idfrais']]);
		}
	}

	/**
	 * Retourne les mois pour lesquels un visiteur a une fiche de frais
	 */
	public function getLesMoisDisponibles($idVisiteur){
		$req = "select fichefrais.mois as mois from fichefrais where fichefrais.idvisiteur = ? 
		order by fichefrais.mois desc ";
		$stmt = $this->monPdo->prepare($req);
		$stmt->execute([$idVisiteur]);
		
		$lesMois = array();
		while($laLigne = $stmt->fetch())	{
			$mois = $laLigne['mois'];
			$lesMois["$mois"] = array(
		        "mois" => "$mois",
		        "numAnnee" => substr($mois, 0, 4),
			    "numMois" => substr($mois, 4, 2)
            );
		}
		return $lesMois;
	}

	/**
	 * Retourne les informations d'une fiche de frais
	 */	
	public function getLesInfosFicheFrais($idVisiteur, $mois){
		$req = "select fichefrais.idEtat as idEtat, fichefrais.dateModif as dateModif, 
                fichefrais.nbJustificatifs as nbJustificatifs, fichefrais.montantValide as montantValide, 
                etat.libelle as libEtat 
                from fichefrais inner join etat on fichefrais.idEtat = etat.id 
			    where fichefrais.idvisiteur = ? and fichefrais.mois = ?";
		$stmt = $this->monPdo->prepare($req);
		$stmt->execute([$idVisiteur, $mois]);
		return $stmt->fetch();
	}

	/**
	 * Modifie l'état et la date de modification d'une fiche de frais
	 */
	public function majEtatFicheFrais($idVisiteur, $mois, $etat){
		$req = "update fichefrais set idEtat = ?, dateModif = now() 
		where fichefrais.idvisiteur = ? and fichefrais.mois = ?";
		$stmt = $this->monPdo->prepare($req);
		$stmt->execute([$etat, $idVisiteur, $mois]);
	}

	/**
	 * Supprime une fiche de frais et ses lignes associées
	 */
	public function supprimerFiche($idVisiteur, $mois){
		$req1 = "delete from lignefraisforfait where idvisiteur = ? and mois = ?";
		$stmt1 = $this->monPdo->prepare($req1);
		$stmt1->execute([$idVisiteur, $mois]);

		$req2 = "delete from fichefrais where idvisiteur = ? and mois = ?";
		$stmt2 = $this->monPdo->prepare($req2);
		$stmt2->execute([$idVisiteur, $mois]);
	}

	/**
	 * Retourne les fiches à valider ou traiter pour le comptable
	 */
	public function getLesFichesValider(){
		$req = "select fichefrais.idvisiteur as idvisiteur, fichefrais.mois as mois, 
                fichefrais.montantValide as montantValide, fichefrais.dateModif as dateModif, 
                visiteur.nom as nom, visiteur.prenom as prenom, fichefrais.idEtat as idEtat
		        from fichefrais inner join visiteur on fichefrais.idvisiteur = visiteur.id
		        where fichefrais.idEtat in ('CL','VA','RB') 
		        order by fichefrais.mois desc ";
		$res = $this->monPdo->query($req);
		$lesFiches = array();
		while($laLigne = $res->fetch())	{
			$mois = $laLigne['mois'];
			$laLigne['numAnnee'] = substr($mois, 0, 4);
			$laLigne['numMois'] = substr($mois, 4, 2);
			$lesFiches[] = $laLigne;
		}
		return $lesFiches;
	}
}