<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use PdoGsb;
use MyDate;

class gererFraisController extends Controller {

    /**
     * Retourne l'utilisateur connecté (visiteur ou comptable), ou null.
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

    function saisirFrais(Request $request){
        $user = $this->getUtilisateurConnecte();
        if($user == null) return view('connexion')->with('erreurs', null);

        if(session('type') == 'comptable') return redirect()->route('chemin_SuiviPaiment');

        $visiteur = session('visiteur');
        $idVisiteur = $visiteur['id'];
        $anneeMois = MyDate::getAnneeMoisCourant();
        $mois = $anneeMois['mois'];

        if(PdoGsb::estPremierFraisMois($idVisiteur, $mois)){
            PdoGsb::creeNouvellesLignesFrais($idVisiteur, $mois);
        }

        $lesFrais = PdoGsb::getLesFraisForfait($idVisiteur, $mois);

        return view('majFraisForfait')
            ->with('lesFrais', $lesFrais)
            ->with('numMois', $anneeMois['numMois'])
            ->with('numAnnee', $anneeMois['numAnnee'])
            ->with('erreurs', null)
            ->with('message', "")
            ->with('visiteur', $visiteur)
            ->with('method', $request->method());
    }

    function sauvegarderFrais(Request $request){
        $user = $this->getUtilisateurConnecte();
        if($user == null) return view('connexion')->with('erreurs', null);
        if(session('type') == 'comptable') return redirect()->route('chemin_SuiviPaiment');

        $visiteur = session('visiteur');
        $idVisiteur = $visiteur['id'];
        $anneeMois = MyDate::getAnneeMoisCourant();
        $mois = $anneeMois['mois'];
        $lesFrais = $request['lesFrais'];
        $lesLibFrais = $request['lesLibFrais'];

        $nbNumeric = 0;
        foreach($lesFrais as $unFrais){
            if(is_numeric($unFrais)) $nbNumeric++;
        }

        $view = view('majFraisForfait')
            ->with('lesFrais', $lesFrais)
            ->with('numMois', $anneeMois['numMois'])
            ->with('numAnnee', $anneeMois['numAnnee'])
            ->with('visiteur', $visiteur)
            ->with('lesLibFrais', $lesLibFrais)
            ->with('method', $request->method());

        if($nbNumeric == 4){
            $message = "Votre fiche a été mise à jour";
            PdoGsb::majFraisForfait($idVisiteur, $mois, $lesFrais);
            return $view->with('erreurs', null)->with('message', $message);
        } else {
            return $view->with('erreurs', ["Les valeurs doivent être numériques"])->with('message', '');
        }
    }

    function suiviPaiment(Request $request){
        if($this->getUtilisateurConnecte() == null) return view('connexion')->with('erreurs', null);
        $fiches = PdoGsb::getLesFichesValider();
        return view('suiviPaiment')
            ->with('comptable', session('comptable'))
            ->with('visiteur', session('visiteur'))
            ->with('fiches', $fiches);
    }

    /**
     * GESTION ACTIONS : Centralise les boutons individuels et de masse.
     * Utilise les messages exacts demandés.
     */
    function traiterActionMasse(Request $request){
        if($this->getUtilisateurConnecte() == null) return view('connexion')->with('erreurs', null);
        $msg = "";

        // 1. Détection d'une action individuelle (boutons par ligne)
        if ($request->has('action_individuelle')) {
            $data = explode('|', $request->input('action_individuelle'));
            $action = $data[0];
            $idV = $data[1];
            $mois = $data[2];

            if ($action == 'valider') {
                PdoGsb::majEtatFicheFrais($idV, $mois, 'VA');
                $msg = "La fiche a été validée et mise en paiement.";
            }
            elseif ($action == 'rembourser') {
                PdoGsb::majEtatFicheFrais($idV, $mois, 'RB');
                $msg = "La fiche a été marquée comme remboursée.";
            }
            elseif ($action == 'annuler') {
                PdoGsb::majEtatFicheFrais($idV, $mois, 'CL');
                $msg = "L'action a été annulée.";
            }
            elseif ($action == 'supprimer') {
                PdoGsb::supprimerFiche($idV, $mois);
                $msg = "La fiche a été supprimée.";
            }

            return redirect()->route('chemin_SuiviPaiment')->with('message', $msg);
        }

        // 2. Détection d'une action de masse (boutons sous le tableau)
        $selection = $request->input('fiches_selectionnees', []);
        if (empty($selection)) {
            return redirect()->route('chemin_SuiviPaiment')->with('message', 'Aucune fiche sélectionnée.');
        }

        if ($request->has('btn_valider_masse')) {
            foreach($selection as $ficheStr) {
                [$idV, $m] = explode('|', $ficheStr);
                PdoGsb::majEtatFicheFrais($idV, $m, 'VA');
            }
            $msg = "Les fiches sélectionnées ont été validées et mises en paiement.";
        } elseif ($request->has('btn_supprimer_masse')) {
            foreach($selection as $ficheStr) {
                [$idV, $m] = explode('|', $ficheStr);
                PdoGsb::supprimerFiche($idV, $m);
            }
            $msg = count($selection) . " fiche(s) supprimée(s).";
        }

        return redirect()->route('chemin_SuiviPaiment')->with('message', $msg);
    }

    function editerPDF(Request $request){
        if($this->getUtilisateurConnecte() == null) return view('connexion')->with('erreurs', null);
        $anneeMois = MyDate::getAnneeMoisCourant();
        $fiches = PdoGsb::getLesFichesParEtat('VA');
        return view('editionPDF')
            ->with('fiches', $fiches)
            ->with('numMois', MyDate::extraireMois($anneeMois['mois']))
            ->with('numAnnee', MyDate::extraireAnnee($anneeMois['mois']))
            ->with('dateEdition', date('d/m/Y'));
    }
}