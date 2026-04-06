@extends ('modeles/visiteur')
    @section('menu')
            <!-- Division pour le sommaire -->
        <div id="menuGauche">
            <div id="infosUtil">
                  
             </div>  
               <ul id="menuList">
                   <li >
                    <strong>Bonjour {{ $comptable['nom'] . ' ' . $comptable['prenom'] }} vous êtes connecté en tant que comptable</strong>
                   </li>
                  <li class="smenu">
                     <a href="{{ route('chemin_SuiviPaiment')}}" title="Suivi de paiement">Suivi de paiement</a>
                  </li>
                  <li class="smenu">
                    <a href="{{ route('chemin_selectionMois') }}" title="Consultation des fiches de frais">Fiches de frais</a>
                  </li>
               <li class="smenu">
                <a href="{{ route('chemin_deconnexion') }}" title="Se déconnecter">Déconnexion</a>
                  </li>
                </ul>
               
        </div>
    @endsection