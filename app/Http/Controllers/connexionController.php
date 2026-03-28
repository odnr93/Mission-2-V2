<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use PdoGsb;

class connexionController extends Controller
{
    function connecter(){
        
        return view('connexion')->with('erreurs',null);
    } 
    function valider(Request $request){
        $login = $request['login'];
        $mdp = $request['mdp'];
        $visiteur = PdoGsb::getInfosVisiteur($login,$mdp);
        $comptable = PdoGsb::getInfosComptable($login,$mdp);
        if(is_array($comptable)){
            session(['visiteur' => $comptable]);
            session(['type' => 'comptable']);
            return view('sommaireComptable')->with('visiteur',session('visiteur'));
        }
        if(!is_array($visiteur)){ 
            $erreurs[] = "Login ou mot de passe incorrect(s)";
            return view('connexion')->with('erreurs',$erreurs);
        }
        else{
            session(['visiteur' => $visiteur]);
            session(['type' => 'visiteur']);
            return view('sommaire')->with('visiteur',session('visiteur'));
        }
    } 

    function deconnecter(){
            session(['visiteur' => null]);
            session(['type' => null]);
            return redirect()->route('chemin_connexion');
       
           
    }
       
}