@extends('sommaireComptable')

@section('contenu1')
  {{-- Style pour masquer les éléments inutiles à l'impression --}}
  <style>
    @media print {
      #menuGauche, .no-print { display: none !important; }
      #contenu { margin: 0; padding: 0; }
    }
    .btn-large {
      padding: 15px 40px !important;
      font-size: 15px !important;
      white-space: nowrap !important;
      min-width: 200px !important;
      height: 50px !important;
      cursor: pointer;
      margin: 5px;
    }
    .btn-suppr {
      background-color: #c0392b;
      color: #fff;
      border: none;
      border-radius: 3px;
    }
  </style>

  <div id="contenu">
    <h2>Suivi des paiements (Gestion 100% PHP)</h2>

    @if(session('message'))
      <p class="info">{{ session('message') }}</p>
    @endif

    @if(!empty($fiches))
      {{-- UN SEUL FORMULAIRE pour toutes les actions groupées --}}
      <form action="{{ route('chemin_traiterActionMasse') }}" method="post">
        {{ csrf_field() }}
        <table class="listeLegere">
          <thead>
            <tr>
              <th class="no-print">Sélection</th>
              <th>Visiteur</th>
              <th>Mois</th>
              <th>Etat</th>
              <th>Montant valide</th>
              <th>Derniere modification</th>
              <th class="no-print">Actions individuelles</th>
            </tr>
          </thead>
          <tbody>
          @foreach($fiches as $fiche)
            <tr>
              <td class="no-print">
                {{-- Une seule case à cocher pour identifier la fiche --}}
                <input type="checkbox" name="fiches_selectionnees[]"
                  value="{{ $fiche['idvisiteur'] }}|{{ $fiche['mois'] }}">
              </td>
              <td>{{ $fiche['nom'] }} {{ $fiche['prenom'] }}</td>
              <td>{{ $fiche['numMois'] }}/{{ $fiche['numAnnee'] }}</td>
              <td>
                @if($fiche['idEtat'] === 'CL') En attente
                @elseif($fiche['idEtat'] === 'VA') Mise en paiement
                @elseif($fiche['idEtat'] === 'RB') Remboursée
                @else {{ $fiche['idEtat'] }} @endif
              </td>
              <td>{{ number_format($fiche['montantValide'], 2, ',', ' ') }} &euro;</td>
              <td>{{ MyDate::getFormatFrançais($fiche['dateModif']) }}</td>
              <td class="no-print">
                {{-- Liens ou boutons directs pour les actions par ligne --}}
                @if($fiche['idEtat'] === 'CL')
                    <button type="submit" name="action_individuelle" value="valider|{{ $fiche['idvisiteur'] }}|{{ $fiche['mois'] }}">Valider</button>
                @elseif($fiche['idEtat'] === 'VA')
                    <button type="submit" name="action_individuelle" value="annuler|{{ $fiche['idvisiteur'] }}|{{ $fiche['mois'] }}">Annuler</button>
                    <button type="submit" name="action_individuelle" value="rembourser|{{ $fiche['idvisiteur'] }}|{{ $fiche['mois'] }}">Rembourser</button>
                @elseif($fiche['idEtat'] === 'RB')
                    <button type="submit" name="action_individuelle" value="annuler_rb|{{ $fiche['idvisiteur'] }}|{{ $fiche['mois'] }}">Annuler RB</button>
                @endif
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>

        {{-- BOUTONS D'ACTIONS GROUPÉES --}}
        <div class="no-print" style="margin-top: 20px;">
          <button type="submit" name="action_masse" value="valider" class="btn-large">
            Valider la sélection
          </button>
          
          <button type="submit" name="action_masse" value="supprimer" class="btn-large btn-suppr">
            Supprimer la sélection
          </button>

          {{-- Note: L'impression reste une fonction navigateur, mais on peut appeler print() via un bouton sans JS complexe --}}
          <button type="button" class="btn-large" onclick="window.print()">
            Imprimer la page
          </button>
        </div>
      </form>
    @else
      <p>Aucune fiche à suivre pour l'instant.</p>
    @endif
  </div>
@endsection