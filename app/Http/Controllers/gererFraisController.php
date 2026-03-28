<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use PdoGsb;
use MyDate;

class gererFraisController extends Controller{

    function saisirFrais(Request $request){
        if( session('visiteur') != null){
            $visiteur = session('visiteur');
            if(session('type') == 'comptable'){
                return redirect()->route('chemin_SuiviPaiment');
            }
            $idVisiteur = $visiteur['id'];
            $anneeMois = MyDate::getAnneeMoisCourant();
            $mois = $anneeMois['mois'];
            if(PdoGsb::estPremierFraisMois($idVisiteur,$mois)){
                 PdoGsb::creeNouvellesLignesFrais($idVisiteur,$mois);
            }
            $lesFrais = PdoGsb::getLesFraisForfait($idVisiteur,$mois);
            $view = view('majFraisForfait')
                    ->with('lesFrais', $lesFrais)
                    ->with('numMois',$anneeMois['numMois'])
                    ->with('erreurs',null)
                    ->with('numAnnee',$anneeMois['numAnnee'])
                    ->with('visiteur',$visiteur)
                    ->with('message',"")
                    ->with ('method',$request->method());
            return $view;
        }
        else{
            return view('connexion')->with('erreurs',null);
        }
    }

    function sauvegarderFrais(Request $request){
        if( session('visiteur')!= null){
            $visiteur = session('visiteur');
            if(session('type') == 'comptable'){
                return redirect()->route('chemin_SuiviPaiment');
            }
            $idVisiteur = $visiteur['id'];
            $anneeMois = MyDate::getAnneeMoisCourant();
            $mois = $anneeMois['mois'];
            $lesFrais = $request['lesFrais'];
            $lesLibFrais = $request['lesLibFrais'];
            $nbNumeric = 0;
            foreach($lesFrais as $unFrais){
                if(is_numeric($unFrais))
                    $nbNumeric++;
            }
            $view = view('majFraisForfait')->with('lesFrais', $lesFrais)
                    ->with('numMois',$anneeMois['numMois'])
                    ->with('numAnnee',$anneeMois['numAnnee'])
                    ->with('visiteur',$visiteur)
                    ->with('lesLibFrais',$lesLibFrais)
                    ->with ('method',$request->method());
            if($nbNumeric == 4){
                $message = "Votre fiche a été mise à jour";
                $erreurs = null;
                PdoGsb::majFraisForfait($idVisiteur,$mois,$lesFrais);
        	}
		    else{
                $erreurs[] ="Les valeurs des frais doivent être numériques";
                $message = '';
            }
            return $view->with('erreurs',$erreurs)
                        ->with('message',$message);
        }
        else{
            return view('connexion')->with('erreurs',null);
        }
    }

    function suiviPaiment(Request $request){
        if( session('visiteur') != null){
            $visiteur = session('visiteur');
            $comptables = PdoGsb::getLesFichesValider();
            return view('suiviPaiment')
                ->with('visiteur', $visiteur)
                ->with('fiches', $comptables);
        }
        else{
            return view('connexion')->with('erreurs',null);
        }
    }

    // Validation individuelle d'une fiche : CL → VA
    // (bouton "Valider" par ligne dans le tableau)
    function validerPaiementIndividuel(Request $request){
        if( session('visiteur') == null){
            return view('connexion')->with('erreurs',null);
        }

        $idVisiteur = $request->input('idVisiteur');
        $mois = $request->input('mois');

        if(empty($idVisiteur) || empty($mois)){
            return redirect()->route('chemin_SuiviPaiment')
                    ->with('message', 'La fiche sélectionnée est invalide.');
        }

        $fiche = PdoGsb::getLesInfosFicheFrais($idVisiteur, $mois);
        if(!is_array($fiche) || $fiche['idEtat'] !== 'CL'){
            return redirect()->route('chemin_SuiviPaiment')
                    ->with('message', 'Cette fiche ne peut pas être validée.');
        }

        PdoGsb::majEtatFicheFrais($idVisiteur, $mois, 'VA');

        return redirect()->route('chemin_SuiviPaiment')
                ->with('message', 'La fiche a été validée et mise en paiement.');
    }

    /*
     * Validation groupée via cases à cocher : CL → VA
     *
     * Flux étendu par rapport au cahier des charges (PDF) :
     *   CL (en attente) → VA (mise en paiement) → RB (remboursée)
     *
     * Le PDF prévoit un passage direct CL → RB.
     * L'état VA a été ajouté pour permettre une vérification intermédiaire
     * par le comptable avant la confirmation du remboursement effectif.
     */
    function validerPaiement(Request $request){
        if( session('visiteur') == null){
            return view('connexion')->with('erreurs',null);
        }

        $fiches = $request->input('fiches', []);

        if(empty($fiches)){
            return redirect()->route('chemin_SuiviPaiment')
                    ->with('message', 'Aucune fiche sélectionnée.');
        }

        $nbValides = 0;
        foreach($fiches as $ficheStr){
            // Chaque valeur est au format "idVisiteur|mois"
            $parts = explode('|', $ficheStr);
            if(count($parts) !== 2) continue;

            [$idVisiteur, $mois] = $parts;

            $fiche = PdoGsb::getLesInfosFicheFrais($idVisiteur, $mois);
            if(!is_array($fiche) || $fiche['idEtat'] !== 'CL') continue;

            PdoGsb::majEtatFicheFrais($idVisiteur, $mois, 'VA');
            $nbValides++;
        }

        $message = $nbValides > 0
            ? $nbValides . ' fiche(s) validée(s) et mise(s) en paiement.'
            : 'Aucune fiche valide à traiter.';

        return redirect()->route('chemin_SuiviPaiment')
                ->with('message', $message);
    }

    // Confirmation du remboursement effectif : VA → RB
    function rembourserPaiement(Request $request){
        if( session('visiteur') == null){
            return view('connexion')->with('erreurs',null);
        }

        $idVisiteur = $request->input('idVisiteur');
        $mois = $request->input('mois');

        if(empty($idVisiteur) || empty($mois)){
            return redirect()->route('chemin_SuiviPaiment')
                    ->with('message', 'La fiche sélectionnée est invalide.');
        }

        $fiche = PdoGsb::getLesInfosFicheFrais($idVisiteur, $mois);
        if(!is_array($fiche) || $fiche['idEtat'] !== 'VA'){
            return redirect()->route('chemin_SuiviPaiment')
                    ->with('message', 'Cette fiche ne peut pas être remboursée.');
        }

        PdoGsb::majEtatFicheFrais($idVisiteur, $mois, 'RB');

        return redirect()->route('chemin_SuiviPaiment')
                ->with('message', 'La fiche a été marquée comme remboursée.');
    }

    // Edition des fiches en état VA (mise en paiement) pour le mois précédent
    function editerPDF(Request $request){
        if( session('visiteur') == null){
            return view('connexion')->with('erreurs',null);
        }

        $anneeMois = MyDate::getAnneeMoisCourant();
        // On prend le mois précédent comme demandé dans le PDF
        $moisPrecedent = MyDate::getMoisPrecedent($anneeMois['mois']);
        $numMois = MyDate::extraireMois($moisPrecedent);
        $numAnnee = MyDate::extraireAnnee($moisPrecedent);

        $fiches = PdoGsb::getLesFichesParEtat('VA');

        return view('editionPDF')
            ->with('fiches', $fiches)
            ->with('numMois', $numMois)
            ->with('numAnnee', $numAnnee)
            ->with('dateEdition', date('d/m/Y'));
    }

    // Suppression de plusieurs fiches sélectionnées (toutes états confondus)
    function supprimerFiches(Request $request){
        if( session('visiteur') == null){
            return view('connexion')->with('erreurs',null);
        }

        $fiches = $request->input('fichesSuppr', []);

        if(empty($fiches)){
            return redirect()->route('chemin_SuiviPaiment')
                    ->with('message', 'Aucune fiche sélectionnée pour la suppression.');
        }

        $nbSupprimes = 0;
        foreach($fiches as $ficheStr){
            // Chaque valeur est au format "idVisiteur|mois"
            $parts = explode('|', $ficheStr);
            if(count($parts) !== 2) continue;

            [$idVisiteur, $mois] = $parts;

            PdoGsb::supprimerFiche($idVisiteur, $mois);
            $nbSupprimes++;
        }

        $message = $nbSupprimes > 0
            ? $nbSupprimes . ' fiche(s) supprimée(s).'
            : 'Aucune fiche supprimée.';

        return redirect()->route('chemin_SuiviPaiment')
                ->with('message', $message);
    }

    // Annulation du remboursement : RB → CL (retour en attente)
    function annulerRemboursement(Request $request){
        if( session('visiteur') == null){
            return view('connexion')->with('erreurs',null);
        }

        $idVisiteur = $request->input('idVisiteur');
        $mois = $request->input('mois');

        if(empty($idVisiteur) || empty($mois)){
            return redirect()->route('chemin_SuiviPaiment')
                    ->with('message', 'La fiche sélectionnée est invalide.');
        }

        $fiche = PdoGsb::getLesInfosFicheFrais($idVisiteur, $mois);
        if(!is_array($fiche) || $fiche['idEtat'] !== 'RB'){
            return redirect()->route('chemin_SuiviPaiment')
                    ->with('message', 'Cette fiche ne peut pas être annulée.');
        }

        PdoGsb::majEtatFicheFrais($idVisiteur, $mois, 'CL');

        return redirect()->route('chemin_SuiviPaiment')
                ->with('message', 'Le remboursement a été annulé, la fiche est repassée en attente.');
    }

    // Annulation de la mise en paiement : VA → CL
    function annulerPaiement(Request $request){
        if( session('visiteur') == null){
            return view('connexion')->with('erreurs',null);
        }

        $idVisiteur = $request->input('idVisiteur');
        $mois = $request->input('mois');

        if(empty($idVisiteur) || empty($mois)){
            return redirect()->route('chemin_SuiviPaiment')
                    ->with('message', 'La fiche sélectionnée est invalide.');
        }

        $fiche = PdoGsb::getLesInfosFicheFrais($idVisiteur, $mois);
        if(!is_array($fiche) || $fiche['idEtat'] !== 'VA'){
            return redirect()->route('chemin_SuiviPaiment')
                    ->with('message', 'Cette fiche ne peut pas être annulée.');
        }

        PdoGsb::majEtatFicheFrais($idVisiteur, $mois, 'CL');

        return redirect()->route('chemin_SuiviPaiment')
                ->with('message', 'La fiche a été repassée en attente.');
    }
}