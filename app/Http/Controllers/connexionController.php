<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use PdoGsb;

class connexionController extends Controller
{
    function connecter(){
        return view('connexion')->with('erreurs', null);
    }

    function valider(Request $request){
        $login = $request['login'];
        $mdp = $request['mdp'];

        $visiteur = PdoGsb::getInfosVisiteur($login, $mdp);
        $comptable = PdoGsb::getInfosComptable($login, $mdp);

        // Connexion comptable
        if(is_array($comptable)){
            session(['comptable' => $comptable]);   // stocké dans 'comptable'
            session(['visiteur'  => null]);
            session(['type'      => 'comptable']);
            return view('sommaireComptable')->with('comptable', $comptable);
        }

        // Connexion visiteur
        if(!is_array($visiteur)){
            $erreurs[] = "Login ou mot de passe incorrect(s)";
            return view('connexion')->with('erreurs', $erreurs);
        }

        session(['visiteur' => $visiteur]);         // stocké dans 'visiteur'
        session(['comptable' => null]);
        session(['type'      => 'visiteur']);
        return view('sommaire')->with('visiteur', $visiteur);
    }

    function deconnecter(){
        session(['visiteur'  => null]);
        session(['comptable' => null]);
        session(['type'      => null]);
        return redirect()->route('chemin_connexion');
    }
}