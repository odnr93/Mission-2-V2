<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Edition des fiches de remboursement - {{ $numMois }}/{{ $numAnnee }}</title>
    <style>
        /* ---- Général ---- */
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        /* ---- En-tête document ---- */
        .entete {
            border-bottom: 3px solid #2c3e6b;
            padding: 20px 40px 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .entete .societe {
            font-size: 22px;
            font-weight: bold;
            color: #2c3e6b;
            letter-spacing: 1px;
        }
        .entete .sous-titre {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
        }
        .entete .infos-doc {
            text-align: right;
            font-size: 12px;
            color: #444;
        }
        .entete .infos-doc strong {
            color: #2c3e6b;
        }

        /* ---- Titre principal ---- */
        .titre-principal {
            text-align: center;
            margin: 24px 40px 8px 40px;
        }
        .titre-principal h1 {
            font-size: 18px;
            color: #2c3e6b;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .titre-principal .periode {
            font-size: 13px;
            color: #555;
        }

        /* ---- Séparateur ---- */
        .separateur {
            border: none;
            border-top: 1px solid #ddd;
            margin: 16px 40px;
        }

        /* ---- Fiche par visiteur ---- */
        .fiche {
            margin: 0 40px 30px 40px;
            border: 1px solid #c8d0e0;
            border-radius: 4px;
            page-break-inside: avoid;
        }
        .fiche-header {
            background-color: #2c3e6b;
            color: #fff;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 3px 3px 0 0;
        }
        .fiche-body {
            padding: 14px 16px;
        }

        /* ---- Tableau frais ---- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        thead tr {
            background-color: #eef1f8;
        }
        th {
            text-align: left;
            padding: 7px 10px;
            font-size: 12px;
            color: #2c3e6b;
            border-bottom: 2px solid #c8d0e0;
        }
        td {
            padding: 7px 10px;
            border-bottom: 1px solid #e8eaf0;
            font-size: 12px;
        }
        tr:last-child td {
            border-bottom: none;
        }

        /* ---- Ligne montant total ---- */
        .ligne-total {
            margin-top: 12px;
            text-align: right;
            font-size: 14px;
        }
        .ligne-total span {
            background-color: #2c3e6b;
            color: #fff;
            padding: 6px 16px;
            border-radius: 3px;
            font-weight: bold;
        }

        /* ---- Pied de page ---- */
        .pied-page {
            margin-top: 40px;
            border-top: 1px solid #ddd;
            padding: 12px 40px;
            font-size: 11px;
            color: #888;
            display: flex;
            justify-content: space-between;
        }

        /* ---- Bouton impression (masqué à l'impression) ---- */
        .no-print {
            text-align: center;
            margin: 20px 0;
        }
        .no-print button {
            background-color: #2c3e6b;
            color: #fff;
            border: none;
            padding: 10px 28px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
        }
        .no-print button:hover {
            background-color: #1e2d52;
        }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            .fiche { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    {{-- En-tête du document --}}
    <div class="entete">
        <div>
            <div class="societe">GSB</div>
            <div class="sous-titre">Galaxy Swiss Bourdin &mdash; Service comptabilité</div>
        </div>
        <div class="infos-doc">
            <div><strong>Date d'édition :</strong> {{ $dateEdition }}</div>
            <div><strong>Période :</strong> {{ $numMois }}/{{ $numAnnee }}</div>
            <div><strong>Document :</strong> Fiches de remboursement</div>
        </div>
    </div>

    {{-- Titre principal --}}
    <div class="titre-principal">
        <h1>Fiches de remboursement à mettre en paiement</h1>
        <div class="periode">Mois : {{ $numMois }}/{{ $numAnnee }}</div>
    </div>

    <hr class="separateur">

    {{-- Bouton impression --}}
    <div class="no-print">
        <button onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    </div>

    {{-- Une fiche par visiteur --}}
    @forelse($fiches as $fiche)
        <div class="fiche">
            <div class="fiche-header">
                {{ $fiche['nom'] }} {{ $fiche['prenom'] }}
            </div>
            <div class="fiche-body">

                {{-- Informations mois --}}
                <p style="margin:0 0 8px 0; color:#444;">
                    <strong>Mois concerné :</strong> {{ $fiche['numMois'] }}/{{ $fiche['numAnnee'] }}
                </p>

                {{-- Détail des frais forfaitisés --}}
                @if(!empty($fiche['lesFraisForfait']))
                <table>
                    <thead>
                        <tr>
                            <th>Libellé</th>
                            <th>Quantité</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fiche['lesFraisForfait'] as $frais)
                        <tr>
                            <td>{{ $frais['libelle'] }}</td>
                            <td>{{ $frais['quantite'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif

                {{-- Montant total à rembourser --}}
                <div class="ligne-total">
                    <span>Montant à rembourser : {{ number_format($fiche['montantValide'], 2, ',', ' ') }} &euro;</span>
                </div>

            </div>
        </div>
    @empty
        <p style="text-align:center; color:#888; margin: 40px;">
            Aucune fiche en attente de paiement pour cette période.
        </p>
    @endforelse

    {{-- Pied de page --}}
    <div class="pied-page">
        <span>GSB &mdash; Document confidentiel, usage interne</span>
        <span>Edité le {{ $dateEdition }}</span>
    </div>

</body>
</html>