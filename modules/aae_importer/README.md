# AAE Import Manager — Module Perfex CRM

Module complet pour **Algerie Auto Export (AAE)** : gestion du catalogue de véhicules chinois,
suivi des opérations d'import (Chine → Algérie), devis rapides intégrés à Perfex (Estimates)
et portail catalogue pour l'espace client.

**Version :** 1.0.0 — **Compatible :** Perfex CRM 3.x (PHP 7.4+ / PHP 8.x)

---

## Installation

1. Copier le dossier `aae_importer` dans le dossier `modules/` de Perfex :
   `votre-site.com/modules/aae_importer/`
2. Aller dans **Configuration → Modules**, repérer « AAE Import Manager » et activer le module.
   - À l'activation, les tables `tbl{préfixe}aae_vehicles`, `tbl{préfixe}aae_operations`,
     `tbl{préfixe}aae_operation_status` sont créées automatiquement ainsi que les options par défaut
     (taux EUR 0,892 / majoration 0,8 % / taux DA 282 / commission 850 € / validité devis 30 jours).
3. Menu administration : **AAE Import** avec Tableau de bord / Catalogue véhicules /
   Opérations d'import / Import–Export CSV / Réglages (administrateurs uniquement).
4. Droits du personnel : **Configuration → Personnels → Rôle** → fonctionnalité
   « AAE Import Manager » (Voir / Créer / Modifier / Supprimer).

## Démarrage rapide (import du catalogue)

1. **AAE Import → Import / Export CSV** → choisir le fichier `CATALOGUE VOITURE - CHINOISES.csv`
   tel quel (séparateur `;` ou `,`, montants en format français, lignes décoratives ignorées).
2. Choisir le statut par défaut des véhicules importés (ex. *Disponible*).
3. Cocher **« Remplacer tout »** seulement pour écraser le catalogue actuel ;
   sinon les lignes sont mises à jour par correspondance marque/modèle/moteur/année
   (le statut d'origine des véhicules déjà saisis est conservé).
4. Le calcul est reproduit depuis le fichier :
   `FOB + traductions + livraison = Prix total $` → `× taux € (0,892) + 0,8 %` → `+ commission` → `Total TTC €` → `× taux DA (282/275)` → `TTC en DA`.

## Fonctionnalités

### Catalogue véhicules
- Tableau paginé serveur avec recherche, filtres marque/statut, tri.
- Véhicule : marque, modèle, moteur, année modèle, couleur, lien (dongchedi),
  statut de vente (Étude / Disponible / Commandé / Réservé / Vendu), notes.
- Modes de saisie : **calcul automatique** (à partir du FOB) ou **saisie manuelle**
  des montants du fichier (véhicules sans FOB).
- Badge « millions DA » (montant DA / 10 000) et taux d'estimation de dédouanement par classe moteur.

### Opérations d'import
- Pipeline complet : Demande → Commande → Production → Embarquement → Transit →
  Arrivée en Algérie → Dédouanement → Disponible → Livré (+ Annulé).
- Chaque changement de statut crée un historique horodaté et met à jour la date du pipeline ;
  le statut catalogue du véhicule suit automatiquement
  (en cours → « Commandé », disponible → « Disponible », livré → « Vendu »).
- Onglets **Liste** et **Kanban** ; bouton « étape suivante » directement depuis la liste.
- Coûts réels (fournisseur, transport, traductions, commission, douane, divers),
  coût total et **marge** recalculés automatiquement + prix de vente DZD.
- Lien optionnel avec un devis Perfex.

### Devis rapides
- Depuis le catalogue : bouton **Devis** → choix du client, devise, base de prix
  (TTC DA / Total USD / TTC € / FOB $), quantité, prix personnalisé éventuel.
- Crée un vrai devis Perfex (Estimates, statut brouillon) avec le détail du calcul
  dans la description longue, référence AAE, validité configurable ;
  option « créer aussi une opération de suivi ».

### Portail client
- Option **Afficher le catalogue dans l'espace client** (Réglages).
- Page `votre-site.com/aae_importer/catalogue` (menu « Véhicules disponibles ») :
  uniquement les véhicules **Disponible**, regroupés par marque, avec prix TTC en DA
  et bouton « Demander un devis » (vers les tickets du client).

## Réglages (AAE Import → Réglages)
| Option | Défaut | Rôle |
|---|---|---|
| Taux € (1 USD →) | 0,892 | Conversion USD → EUR du catalogue |
| Majoration % → € | 0,8 | Majoration bancaire sur la conversion |
| Taux DA (282/275) | 282 | Conversion EUR → DA |
| Commission (EUR) | 850 | Commission par défaut par véhicule |
| Validité devis (jours) | 30 | Durée de validité des devis rapides |
| Portail client | Activé | Afficher le catalogue dans l'espace client |

## Désinstallation
Désactiver puis supprimer le module via **Configuration → Modules** : le fichier
`uninstall.php` purge alors les tables et options du module
(les devis Perfex créés depuis le catalogue ne sont **pas** supprimés).
Pour garder les données, désactiver seulement le module sans supprimer le dossier.

---

### Fichiers du module
```
aae_importer/
├── aae_importer.php          (initialisation : menu, hooks, permissions, helpers)
├── install.php               (schéma base de données + options)
├── uninstall.php             (purge données)
├── controllers/
│   ├── Aae_importer.php      (back-end admin : catalogue, opérations, devis, réglages)
│   └── Catalogue.php         (espace client)
├── models/Aae_importer_model.php
├── views/                    (dashboard, catalogue, opérations, formulaires, import, réglages, client)
├── language/french/ + language/english/
└── assets/css/ + assets/js/  (styles du module + DataTables/modaux/calculs)
```

Support : AAE / Fennec Vision — support@algerieautoexport.com
