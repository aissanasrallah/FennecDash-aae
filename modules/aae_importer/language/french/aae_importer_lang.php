<?php
defined('BASEPATH') or exit('No direct script access allowed');

$lang['aae_module_name'] = 'AAE Import Manager';
$lang['aae_menu_dashboard'] = 'Tableau de bord';
$lang['aae_menu_catalogue'] = 'Catalogue véhicules';
$lang['aae_menu_operations'] = 'Opérations d\'import';
$lang['aae_menu_import_csv'] = 'Import / Export CSV';
$lang['aae_menu_settings'] = 'Réglages';

// Permissions
$lang['aae_perm_view'] = 'Voir';
$lang['aae_perm_create'] = 'Créer';
$lang['aae_perm_edit'] = 'Modifier';
$lang['aae_perm_delete'] = 'Supprimer';

// Catalogue
$lang['aae_vehicle'] = 'Véhicule';
$lang['aae_vehicles'] = 'Véhicules';
$lang['aae_marque'] = 'Marque';
$lang['aae_modele'] = 'Modèle';
$lang['aae_moteur'] = 'Moteur';
$lang['aae_annee'] = 'Année modèle';
$lang['aae_couleur'] = 'Couleur';
$lang['aae_fob_usd'] = 'FOB prix $';
$lang['aae_doc_translation'] = 'Traduction documents';
$lang['aae_vehicle_translation'] = 'Traduction du véhicule';
$lang['aae_shipping_usd'] = 'Frais de livraison';
$lang['aae_total_usd'] = 'Prix total $';
$lang['aae_eur_rate'] = 'Taux € (1 USD →)';
$lang['aae_eur_fee_percent'] = 'Majoration % → €';
$lang['aae_price_eur'] = 'Prix en €';
$lang['aae_commission'] = 'Commission';
$lang['aae_total_ttc_eur'] = 'Total TTC €';
$lang['aae_dzd_rate'] = 'Taux DA (282/275)';
$lang['aae_ttc_dzd'] = 'TTC en DA';
$lang['aae_ttc_millions'] = 'TTC millions';
$lang['aae_customs_rate'] = 'Estimation dédouanement';
$lang['aae_lien'] = 'Lien du véhicule';
$lang['aae_statut'] = 'Statut';
$lang['aae_notes'] = 'Notes';
$lang['aae_actions'] = 'Actions';

$lang['aae_vstat_catalogue'] = 'Étude';
$lang['aae_vstat_disponible'] = 'Disponible';
$lang['aae_vstat_commande'] = 'Commandé';
$lang['aae_vstat_reserve'] = 'Réservé';
$lang['aae_vstat_vendu'] = 'Vendu';

$lang['aae_vehicle_added'] = 'Véhicule ajouté au catalogue.';
$lang['aae_vehicle_updated'] = 'Véhicule mis à jour.';
$lang['aae_vehicle_deleted'] = 'Véhicule supprimé.';
$lang['aae_cannot_delete_vehicle_in_ops'] = 'Impossible de supprimer : ce véhicule est utilisé par %s opération(s).';
$lang['aae_confirm_delete_vehicle'] = 'Supprimer ce véhicule du catalogue ?';
$lang['aae_new_vehicle'] = 'Nouveau véhicule';
$lang['aae_edit_vehicle'] = 'Modifier le véhicule';
$lang['aae_manual_input'] = 'Saisie manuelle (montants du fichier)';
$lang['aae_auto_calculated'] = 'Calcul automatique';

// Devise / calculs
$lang['aae_qbase_ttc_dzd'] = 'TTC en DA (Bab el Oued)';
$lang['aae_qbase_total_usd'] = 'Prix total USD';
$lang['aae_qbase_ttc_eur'] = 'Total TTC €';
$lang['aae_qbase_fob'] = 'FOB $ (usine)';

// Opérations
$lang['aae_operations'] = 'Opérations';
$lang['aae_operation'] = 'Opération';
$lang['aae_new_operation'] = 'Nouvelle opération';
$lang['aae_edit'] = 'Modifier';
$lang['aae_ref'] = 'Référence';
$lang['aae_op_client'] = 'Client';
$lang['aae_op_owner'] = 'Suivi par';
$lang['aae_op_qty'] = 'Quantité';
$lang['aae_op_eta'] = 'Arrivée prévue (ETA)';
$lang['aae_dates'] = 'Dates principales';
$lang['aae_op_order_date'] = 'Date de commande';
$lang['aae_op_production_date'] = 'Date de production';
$lang['aae_op_departure_date'] = 'Date d\'embarquement';
$lang['aae_op_arrival_date'] = 'Arrivée en Algérie';
$lang['aae_op_customs_date'] = 'Dédouanement';
$lang['aae_op_delivery_date'] = 'Livraison au client';

$lang['aae_opstat_demande'] = 'Demande / Renseignement';
$lang['aae_opstat_commande'] = 'Commande fournisseur';
$lang['aae_opstat_production'] = 'En production';
$lang['aae_opstat_embarque'] = 'Embarquement (port)';
$lang['aae_opstat_transit'] = 'En transit';
$lang['aae_opstat_arrive'] = 'Arrivé en Algérie';
$lang['aae_opstat_douane'] = 'Dédouanement';
$lang['aae_opstat_disponible'] = 'Disponible';
$lang['aae_opstat_livre'] = 'Livré / Dossier clos';
$lang['aae_opstat_annule'] = 'Annulé';

$lang['aae_costs'] = 'Coûts (réels)';
$lang['aae_fob_paid'] = 'Achat fournisseur';
$lang['aae_shipping_paid'] = 'Transport réel';
$lang['aae_translation_paid'] = 'Traductions';
$lang['aae_commission_paid'] = 'Commission payée';
$lang['aae_customs_amount'] = 'Montant douane';
$lang['aae_other_costs'] = 'Autres coûts';
$lang['aae_total_cost'] = 'Coût total';
$lang['aae_sale_price'] = 'Prix de vente';
$lang['aae_margin'] = 'Marge';
$lang['aae_history'] = 'Historique du dossier';
$lang['aae_advance_status'] = 'Passer à l\'étape suivante';
$lang['aae_status_note'] = 'Note (optionnelle)';
$lang['aae_operation_added'] = 'Opération créée.';
$lang['aae_operation_updated'] = 'Opération mise à jour.';
$lang['aae_operation_deleted'] = 'Opération supprimée.';
$lang['aae_estimate_link'] = 'Devis lié';

// Devis
$lang['aae_quote'] = 'Devis';
$lang['aae_quick_quote'] = 'Devis rapide';
$lang['aae_quote_from_catalogue'] = 'Créer un devis depuis le catalogue';
$lang['aae_quote_select_client'] = 'Client';
$lang['aae_quote_currency'] = 'Devise';
$lang['aae_quote_basis'] = 'Base de prix';
$lang['aae_quote_qty'] = 'Quantité';
$lang['aae_quote_amount'] = 'Montant';
$lang['aae_quote_rate_override'] = 'Prix personnalisé (optionnel)';
$lang['aae_quote_create_operation'] = 'Crée aussi une opération de suivi';
$lang['aae_quote_preview_total'] = 'Total du devis';
$lang['aae_estimate_created'] = 'Devis créé avec succès.';
$lang['aae_estimate_failed'] = 'Impossible de créer le devis.';
$lang['aae_client_not_found'] = 'Client introuvable.';
$lang['aae_vehicle_not_found'] = 'Véhicule introuvable.';
$lang['aae_quote_detail_line_fob'] = 'Prix usine (FOB) : %s $';
$lang['aae_quote_detail_line_trans'] = 'Traductions : %s $';
$lang['aae_quote_detail_line_shipping'] = 'Livraison : %s $';
$lang['aae_quote_detail_line_usd'] = 'Total USD : %s $';
$lang['aae_quote_detail_line_customs'] = 'Taux de dédouanement estimé : %s %';

// Import
$lang['aae_import_csv'] = 'Import CSV';
$lang['aae_import_hint'] = 'Importez votre fichier "CATALOGUE VOITURE - CHINOISES.csv" tel quel. Les lignes MOTO, EN-TÊTE et tout autres lignes décorative sont ignorées automatiquement.';
$lang['aae_import_select_file'] = 'Fichier CSV à importer';
$lang['aae_import_replace_all'] = 'Remplacer tout le catalogue existant (sinon : mise à jour par correspondance marque/modèle/moteur/année)';
$lang['aae_import_status'] = 'Statut par défaut des véhicules importés';
$lang['aae_import_success'] = 'Import réussi : %s véhicule(s) ajouté(s), %s mis à jour.';
$lang['aae_import_failed'] = 'Impossible de lire le fichier CSV. Vérifiez le format du fichier.';
$lang['aae_no_file_selected'] = 'Aucun fichier sélectionné.';
$lang['aae_export_csv'] = 'Exporter le catalogue (CSV)';
$lang['aae_import_go'] = 'Lancer l\'import';
$lang['aae_replace_warning'] = 'Attention : cocher « Remplacer tout » supprime le catalogue actuel de la CRM.';

// Réglages
$lang['aae_settings'] = 'Réglages AAE Import';
$lang['aae_settings_hint'] = 'Ces valeurs sont utilisées comme valeur par défaut pour les nouveaux véhicules et les calculs (montants en USD, prix en € et valeur DA).';
$lang['aae_settings_portal'] = 'Afficher le catalogue « véhicules disponibles » dans l\'espace client';

// Dashboard
$lang['aae_dash_total_vehicles'] = 'Véhicules au catalogue';
$lang['aae_dash_available'] = 'Disponibles';
$lang['aae_dash_ongoing_ops'] = 'Opérations en cours';
$lang['aae_dash_revenue'] = 'CA encaissé (dossiers livrés)';
$lang['aae_dash_margin'] = 'Marge accumulée';
$lang['aae_dash_operations_by_status'] = 'Opérations par statut';
$lang['aae_dash_latest_operations'] = 'Dernières opérations';
$lang['aae_dash_top_brands'] = 'Répartition par marque';

// Espace client
$lang['aae_client_menu_item'] = 'Véhicules disponibles';
$lang['aae_client_catalogue_title'] = 'Véhicules disponibles';
$lang['aae_client_portal_disabled'] = 'Le catalogue est désactivé pour l\'instant.';
$lang['aae_client_ask_quote'] = 'Demander un devis';

$lang['aae_view_full'] = 'Voir la fiche';

// Divers / filtres / boutons
$lang['aae_filter_all'] = 'Tous';
$lang['aae_view_list'] = 'Liste';
$lang['aae_view_kanban'] = 'Kanban';
$lang['aae_search_placeholder'] = 'Rechercher (marque, modèle, moteur, couleur...)';
$lang['aae_no_vehicles'] = 'Aucun véhicule au catalogue. Importez votre fichier CSV pour commencer.';
$lang['aae_no_operations'] = 'Aucune opération d\'import pour le moment.';
$lang['aae_select_vehicle'] = 'Sélectionner un véhicule';
$lang['aae_select_client'] = 'Aucun client';
$lang['aae_import_current_count'] = 'Véhicules actuellement au catalogue : %s';
$lang['aae_none'] = '—';
$lang['aae_back_to_list'] = 'Retour à la liste';
$lang['aae_back'] = 'Retour';
$lang['aae_client_price'] = 'Prix';
$lang['aae_client_of_year'] = 'Année';
$lang['aae_client_engine'] = 'Moteur';
$lang['aae_change_status'] = 'Changer le statut';
$lang['aae_next_step'] = 'Étape suivante';
$lang['aae_costs_hint'] = 'Laissez vide ce qui n\'est pas encore connu : le coût total et la marge se recalculent automatiquement.';
$lang['aae_confirm_delete_operation'] = 'Supprimer cette opération d\'import ?';
$lang['aae_client_contact_hint'] = 'Contactez-nous pour obtenir une proposition détaillée (devis, délais d\'acheminement, options).';
$lang['aae_client_no_vehicles'] = 'Aucun véhicule disponible pour le moment. Revenez bientôt !';
