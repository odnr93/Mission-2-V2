@extends('sommaireComptable')

{{--
  Flux de paiement (3 états) :
  CL = En attente       → le comptable sélectionne et valide
  VA = Mise en paiement → étape intermédiaire avant remboursement effectif
  RB = Remboursée       → paiement confirmé

  Note : le cahier des charges (PDF) prévoit un passage direct CL → RB.
  L'état VA a été ajouté délibérément pour permettre une vérification
  intermédiaire par le comptable avant la confirmation du remboursement.
--}}

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
    }
  </style>

  <div id="contenu">
    <h2>Suivi des paiements</h2>

    @if(session('message'))
      <p class="info">{{ session('message') }}</p>
    @endif

    @if(!empty($fiches))
      {{-- Le tableau est englobé dans un formulaire pour la sélection multiple --}}
      <form action="{{ route('chemin_validerPaiement') }}" method="post">
        {{ csrf_field() }}
        <table class="listeLegere">
          <thead>
            <tr>
              {{-- Colonne case à cocher avec "tout cocher" --}}
              <th class="no-print"><input type="checkbox" id="toutCocher"></th>
              {{-- Colonne case à cocher suppression --}}
              <th class="no-print"><input type="checkbox" id="toutCocherSuppr"></th>
              <th>Visiteur</th>
              <th>Mois</th>
              <th>Etat</th>
              <th>Montant valide</th>
              <th>Derniere modification</th>
              <th class="no-print">Action</th>
            </tr>
          </thead>
          <tbody>
          @foreach($fiches as $fiche)
            <tr>
              {{-- Case à cocher uniquement pour les fiches en attente (CL) --}}
              <td class="no-print">
                @if($fiche['idEtat'] === 'CL')
                  <input type="checkbox" name="fiches[]"
                    value="{{ $fiche['idvisiteur'] }}|{{ $fiche['mois'] }}">
                @endif
              </td>
              {{-- Case à cocher suppression (tous états) --}}
              <td class="no-print">
                <input type="checkbox" name="fichesSuppr[]"
                  value="{{ $fiche['idvisiteur'] }}|{{ $fiche['mois'] }}">
              </td>
              <td>{{ $fiche['nom'] }} {{ $fiche['prenom'] }}</td>
              <td>{{ $fiche['numMois'] }}/{{ $fiche['numAnnee'] }}</td>
              {{--
                Affichage des 3 états du flux :
                CL → En attente | VA → Mise en paiement | RB → Remboursée
              --}}
              <td>
                @if($fiche['idEtat'] === 'CL')
                  En attente
                @elseif($fiche['idEtat'] === 'VA')
                  Mise en paiement
                @elseif($fiche['idEtat'] === 'RB')
                  Remboursée
                @else
                  {{ $fiche['idEtat'] }}
                @endif
              </td>
              <td>{{ number_format($fiche['montantValide'], 2, ',', ' ') }} &euro;</td>
              <td>{{ MyDate::getFormatFrançais($fiche['dateModif']) }}</td>
              <td class="no-print">
                @if($fiche['idEtat'] === 'CL')
                  {{-- CL → VA : valider la fiche individuellement --}}
                  <form action="{{ route('chemin_validerPaiement_individuel') }}" method="post">
                    {{ csrf_field() }}
                    <input type="hidden" name="idVisiteur" value="{{ $fiche['idvisiteur'] }}">
                    <input type="hidden" name="mois" value="{{ $fiche['mois'] }}">
                    <button type="submit">Valider</button>
                  </form>
                @elseif($fiche['idEtat'] === 'VA')
                  {{-- VA → CL : annuler la mise en paiement --}}
                  <form action="{{ route('chemin_annulerPaiement') }}" method="post">
                    {{ csrf_field() }}
                    <input type="hidden" name="idVisiteur" value="{{ $fiche['idvisiteur'] }}">
                    <input type="hidden" name="mois" value="{{ $fiche['mois'] }}">
                    <button type="submit">Annuler</button>
                  </form>
                  {{-- VA → RB : confirmer le remboursement effectif --}}
                  <form action="{{ route('chemin_rembourserPaiement') }}" method="post">
                    {{ csrf_field() }}
                    <input type="hidden" name="idVisiteur" value="{{ $fiche['idvisiteur'] }}">
                    <input type="hidden" name="mois" value="{{ $fiche['mois'] }}">
                    <button type="submit">Rembourser</button>
                  </form>
                @elseif($fiche['idEtat'] === 'RB')
                  {{-- RB → CL : annuler le remboursement et repasser en attente --}}
                  <form action="{{ route('chemin_annulerRemboursement') }}" method="post">
                    {{ csrf_field() }}
                    <input type="hidden" name="idVisiteur" value="{{ $fiche['idvisiteur'] }}">
                    <input type="hidden" name="mois" value="{{ $fiche['mois'] }}">
                    <button type="submit">Annuler</button>
                  </form>
                @endif
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
        {{-- Bouton de validation groupée (CL → VA) + bouton imprimer --}}
        <p class="no-print">
          <button type="submit" class="btn-large">Valider la sélection</button>
          <button type="button" class="btn-large" onclick="window.print()">Enregistrer en PDF</button>
          <button type="button" class="btn-large" style="background-color:#c0392b; color:#fff;" onclick="document.getElementById('formSupprimer').dispatchEvent(new Event('submit'))">Supprimer la sélection</button>
        </p>
      </form>

      {{-- Formulaire séparé pour la suppression multiple --}}
      <form action="{{ route('chemin_supprimerFiches') }}" method="post" id="formSupprimer"
            onsubmit="return confirm('Confirmer la suppression des fiches sélectionnées ? Cette action est irréversible.')">
        {{ csrf_field() }}
        <div id="champsSuppr"></div>
      </form>
    @else
      <p>Aucune fiche a suivre pour l'instant.</p>
    @endif
  </div>

  {{-- Script pour cocher/décocher toutes les fiches CL --}}
  <script>
    // Tout cocher/décocher pour la validation (CL uniquement)
    document.getElementById('toutCocher').addEventListener('change', function() {
      document.querySelectorAll('input[name="fiches[]"]').forEach(function(cb) {
        cb.checked = document.getElementById('toutCocher').checked;
      });
    });

    // Tout cocher/décocher pour la suppression (tous états)
    document.getElementById('toutCocherSuppr').addEventListener('change', function() {
      document.querySelectorAll('input[name="fichesSuppr[]"]').forEach(function(cb) {
        cb.checked = document.getElementById('toutCocherSuppr').checked;
      });
    });

    // Avant soumission du formulaire suppression, copier les valeurs cochées
    document.getElementById('formSupprimer').addEventListener('submit', function() {
      var container = document.getElementById('champsSuppr');
      container.innerHTML = '';
      document.querySelectorAll('input[name="fichesSuppr[]"]:checked').forEach(function(cb) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'fichesSuppr[]';
        input.value = cb.value;
        container.appendChild(input);
      });
    });
  </script>
@endsection