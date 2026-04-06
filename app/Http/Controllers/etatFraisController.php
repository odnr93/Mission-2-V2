<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use PdoGsb;
use MyDate;

class etatFraisController extends Controller
{
    /**
     * Retourne l'utilisateur connecté (visiteur ou comptable) et son id,
     * ou null si personne n'est connecté.
     */
    private function getUtilisateurConnecte(){
        if(session('type') == 'comptable' && session('comptable') != null){
            return session('comptable');
        }
        if(session('type') == 'visiteur' && session('visiteur') != null){
            return session('visiteur');
        }
        return null;
    }

    function selectionnerMois(){
        $user = $this->getUtilisateurConnecte();

        if($user == null){
            return view('connexion')->with('erreurs', null);
        }

        $idVisiteur = $user['id'];
        $lesMois = PdoGsb::getLesMoisDisponibles($idVisiteur);

        if(empty($lesMois)){
            // MODIFICATION ICI : On détecte le type pour adapter le message
            $typeUser = (session('type') == 'comptable') ? "comptable" : "visiteur";
            $erreurs[] = "Aucune fiche de frais disponible pour ce " . $typeUser . ".";
            return $this->vueListe([], null, $erreurs);
        }

        $lesCles = array_keys($lesMois);
        $moisASelectionner = $lesCles[0];

        return $this->vueListe($lesMois, $moisASelectionner);
    }

    function voirFrais(Request $request){
        $user = $this->getUtilisateurConnecte();

        if($user == null){
            return view('connexion')->with('erreurs', null);
        }

        $idVisiteur = $user['id'];
        $leMois = $request['lstMois'];
        $lesMois = PdoGsb::getLesMoisDisponibles($idVisiteur);
        $lesFraisForfait = PdoGsb::getLesFraisForfait($idVisiteur, $leMois);
        $lesInfosFicheFrais = PdoGsb::getLesInfosFicheFrais($idVisiteur, $leMois);

        $numAnnee = MyDate::extraireAnnee($leMois);
        $numMois  = MyDate::extraireMois($leMois);
        $libEtat  = $lesInfosFicheFrais['libEtat'];
        $montantValide = $lesInfosFicheFrais['montantValide'];
        $nbJustificatifs = $lesInfosFicheFrais['nbJustificatifs'];
        $dateModif = MyDate::getFormatFrançais($lesInfosFicheFrais['dateModif']);

        $vue = view('listefrais')
            ->with('lesMois', $lesMois)
            ->with('leMois', $leMois)
            ->with('numAnnee', $numAnnee)
            ->with('numMois', $numMois)
            ->with('libEtat', $libEtat)
            ->with('montantValide', $montantValide)
            ->with('nbJustificatifs', $nbJustificatifs)
            ->with('dateModif', $dateModif)
            ->with('lesFraisForfait', $lesFraisForfait);

        // Transmettre le bon type de variable à la vue
        if(session('type') == 'comptable'){
            return $vue->with('comptable', session('comptable'));
        }
        return $vue->with('visiteur', session('visiteur'));
    }

    /**
     * Construit la vue listemois en passant la bonne variable selon le type connecté.
     */
    private function vueListe($lesMois, $leMois, $erreurs = null){
        $vue = view('listemois')
            ->with('lesMois', $lesMois)
            ->with('leMois', $leMois)
            ->with('erreurs', $erreurs);

        if(session('type') == 'comptable'){
            return $vue->with('comptable', session('comptable'));
        }
        return $vue->with('visiteur', session('visiteur'));
    }
}