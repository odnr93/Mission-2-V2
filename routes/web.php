<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
        /*-------------------- Use case connexion---------------------------*/
Route::get('/',[
        'as' => 'chemin_connexion',
        'uses' => 'connexionController@connecter'
]);

Route::post('/',[
        'as'=>'chemin_valider',
        'uses'=>'connexionController@valider'
]);
Route::get('deconnexion',[
        'as'=>'chemin_deconnexion',
        'uses'=>'connexionController@deconnecter'
]);

         /*-------------------- Use case état des frais---------------------------*/
Route::get('selectionMois',[
        'as'=>'chemin_selectionMois',
        'uses'=>'etatFraisController@selectionnerMois'
]);

Route::post('listeFrais',[
        'as'=>'chemin_listeFrais',
        'uses'=>'etatFraisController@voirFrais'
]);

        /*-------------------- Use case gérer les frais---------------------------*/

Route::get('gererFrais',[
        'as'=>'chemin_gestionFrais',
        'uses'=>'gererFraisController@saisirFrais'
]);

Route::post('sauvegarderFrais',[
        'as'=>'chemin_sauvegardeFrais',
        'uses'=>'gererFraisController@sauvegarderFrais'
]);

Route::get('suiviPaiment',[
        'as'=>'chemin_SuiviPaiment',
        'uses'=>'gererFraisController@suiviPaiment'
]);

// AJOUT : route pour la validation individuelle (ancien bouton "Valider" par ligne)
Route::post('validerPaiementIndividuel',[
        'as'=>'chemin_validerPaiement_individuel',
        'uses'=>'gererFraisController@validerPaiementIndividuel'
]);

// ANCIEN CODE conservé : renommé pour la validation multiple (cases à cocher)
Route::post('validerPaiement',[
        'as'=>'chemin_validerPaiement',
        'uses'=>'gererFraisController@validerPaiement'
]);



Route::post('annulerPaiement',[
        'as'=>'chemin_annulerPaiement',
        'uses'=>'gererFraisController@annulerPaiement'
]);

// AJOUT : route pour passer une fiche à l'état Remboursée (RB)
Route::post('rembourserPaiement',[
        'as'=>'chemin_rembourserPaiement',
        'uses'=>'gererFraisController@rembourserPaiement'
]);

// AJOUT : route pour annuler un remboursement (RB → CL)
Route::post('annulerRemboursement',[
        'as'=>'chemin_annulerRemboursement',
        'uses'=>'gererFraisController@annulerRemboursement'
]);

// AJOUT : route pour supprimer plusieurs fiches
Route::post('supprimerFiches',[
        'as'=>'chemin_supprimerFiches',
        'uses'=>'gererFraisController@supprimerFiches'
]);

// AJOUT : route pour l'édition PDF des fiches de remboursement
Route::get('editionPdf',[
        'as'=>'chemin_editionPdf',
        'uses'=>'gererFraisController@editerPdf'
]);