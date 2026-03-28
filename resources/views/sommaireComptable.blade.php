@extends ('modeles/visiteur')
    @section('menu')
            <!-- Division pour le sommaire -->
        <div id="menuGauche">
            <div id="infosUtil">
                  
             </div>  
               <ul id="menuList">
                   <li >
                    <strong>Bonjour {{ $visiteur['nom'] . ' ' . $visiteur['prenom'] }} vous êtes connecte en tant que comptable</strong>
                      
                   </li>
                  <li class="smenu">
                     <a href="{{ route('chemin_SuiviPaiment')}}" title="Saisie de paiment ">Suivi de paiment</a>
                  </li>
                  <li class="smenu">
                    <a href="{{ route('chemin_selectionMois') }}" title="Consultation de mes fiches de frais">Mes fiches de frais</a>
                  </li>
               <li class="smenu">
                <a href="{{ route('chemin_deconnexion') }}" title="Se déconnecter">Déconnexion</a>
                  </li>
                </ul>
               
        </div>
    @endsection