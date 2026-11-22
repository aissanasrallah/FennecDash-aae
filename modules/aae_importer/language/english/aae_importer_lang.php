<?php
defined('BASEPATH') or exit('No direct script access allowed');

$lang['aae_module_name'] = 'AAE Import Manager';
$lang['aae_menu_dashboard'] = 'Dashboard';
$lang['aae_menu_catalogue'] = 'Vehicle catalogue';
$lang['aae_menu_operations'] = 'Import operations';
$lang['aae_menu_import_csv'] = 'CSV import / export';
$lang['aae_menu_settings'] = 'Settings';

// Permissions
$lang['aae_perm_view'] = 'View';
$lang['aae_perm_create'] = 'Create';
$lang['aae_perm_edit'] = 'Edit';
$lang['aae_perm_delete'] = 'Delete';

// Catalogue
$lang['aae_vehicle'] = 'Vehicle';
$lang['aae_vehicles'] = 'Vehicles';
$lang['aae_marque'] = 'Brand';
$lang['aae_modele'] = 'Model';
$lang['aae_moteur'] = 'Engine';
$lang['aae_annee'] = 'Model year';
$lang['aae_couleur'] = 'Colour';
$lang['aae_fob_usd'] = 'FOB price $';
$lang['aae_doc_translation'] = 'Documents translation';
$lang['aae_vehicle_translation'] = 'Vehicle translation';
$lang['aae_shipping_usd'] = 'Delivery fees';
$lang['aae_total_usd'] = 'Total price $';
$lang['aae_eur_rate'] = 'EUR rate (1 USD →)';
$lang['aae_eur_fee_percent'] = 'USD→EUR markup %';
$lang['aae_price_eur'] = 'Price in €';
$lang['aae_commission'] = 'Commission';
$lang['aae_total_ttc_eur'] = 'Total TTC €';
$lang['aae_dzd_rate'] = 'DZD rate (282/275)';
$lang['aae_ttc_dzd'] = 'TTC in DZD';
$lang['aae_ttc_millions'] = 'TTC millions';
$lang['aae_customs_rate'] = 'Customs estimate';
$lang['aae_lien'] = 'Vehicle link';
$lang['aae_statut'] = 'Status';
$lang['aae_notes'] = 'Notes';
$lang['aae_actions'] = 'Actions';

$lang['aae_vstat_catalogue'] = 'Study';
$lang['aae_vstat_disponible'] = 'Available';
$lang['aae_vstat_commande'] = 'Ordered';
$lang['aae_vstat_reserve'] = 'Reserved';
$lang['aae_vstat_vendu'] = 'Sold';

$lang['aae_vehicle_added'] = 'Vehicle added to the catalogue.';
$lang['aae_vehicle_updated'] = 'Vehicle updated.';
$lang['aae_vehicle_deleted'] = 'Vehicle deleted.';
$lang['aae_cannot_delete_vehicle_in_ops'] = 'Cannot delete: this vehicle is used by %s import operation(s).';
$lang['aae_confirm_delete_vehicle'] = 'Delete this vehicle from the catalogue?';
$lang['aae_new_vehicle'] = 'New vehicle';
$lang['aae_edit_vehicle'] = 'Edit vehicle';
$lang['aae_manual_input'] = 'Manual entry (amounts from the file)';
$lang['aae_auto_calculated'] = 'Automatic calculation';

// Quote bases
$lang['aae_qbase_ttc_dzd'] = 'TTC in DZD';
$lang['aae_qbase_total_usd'] = 'Total USD';
$lang['aae_qbase_ttc_eur'] = 'Total TTC €';
$lang['aae_qbase_fob'] = 'FOB $ (factory)';

// Operations
$lang['aae_operations'] = 'Operations';
$lang['aae_operation'] = 'Operation';
$lang['aae_new_operation'] = 'New operation';
$lang['aae_edit'] = 'Edit';
$lang['aae_ref'] = 'Reference';
$lang['aae_op_client'] = 'Client';
$lang['aae_op_owner'] = 'Managed by';
$lang['aae_op_qty'] = 'Quantity';
$lang['aae_op_eta'] = 'Expected arrival (ETA)';
$lang['aae_dates'] = 'Key dates';
$lang['aae_op_order_date'] = 'Order date';
$lang['aae_op_production_date'] = 'Production date';
$lang['aae_op_departure_date'] = 'Shipping date';
$lang['aae_op_arrival_date'] = 'Arrival in Algeria';
$lang['aae_op_customs_date'] = 'Customs clearing';
$lang['aae_op_delivery_date'] = 'Delivered to client';

$lang['aae_opstat_demande'] = 'Request / Enquiry';
$lang['aae_opstat_commande'] = 'Supplier order';
$lang['aae_opstat_production'] = 'In production';
$lang['aae_opstat_embarque'] = 'Boarding (port)';
$lang['aae_opstat_transit'] = 'In transit';
$lang['aae_opstat_arrive'] = 'Arrived in Algeria';
$lang['aae_opstat_douane'] = 'Customs clearing';
$lang['aae_opstat_disponible'] = 'Available';
$lang['aae_opstat_livre'] = 'Delivered / Closed';
$lang['aae_opstat_annule'] = 'Cancelled';

$lang['aae_costs'] = 'Actual costs';
$lang['aae_fob_paid'] = 'Supplier purchase';
$lang['aae_shipping_paid'] = 'Actual transport';
$lang['aae_translation_paid'] = 'Translations';
$lang['aae_commission_paid'] = 'Commission paid';
$lang['aae_customs_amount'] = 'Customs amount';
$lang['aae_other_costs'] = 'Other costs';
$lang['aae_total_cost'] = 'Total cost';
$lang['aae_sale_price'] = 'Sale price';
$lang['aae_margin'] = 'Margin';
$lang['aae_history'] = 'Operation history';
$lang['aae_advance_status'] = 'Advance to next step';
$lang['aae_status_note'] = 'Note (optional)';
$lang['aae_operation_added'] = 'Operation created.';
$lang['aae_operation_updated'] = 'Operation updated.';
$lang['aae_operation_deleted'] = 'Operation deleted.';
$lang['aae_estimate_link'] = 'Linked estimate';

// Quote
$lang['aae_quote'] = 'Quote';
$lang['aae_quick_quote'] = 'Quick quote';
$lang['aae_quote_from_catalogue'] = 'Create a quote from the catalogue';
$lang['aae_quote_select_client'] = 'Client';
$lang['aae_quote_currency'] = 'Currency';
$lang['aae_quote_basis'] = 'Price basis';
$lang['aae_quote_qty'] = 'Quantity';
$lang['aae_quote_amount'] = 'Amount';
$lang['aae_quote_rate_override'] = 'Custom price (optional)';
$lang['aae_quote_create_operation'] = 'Also create a tracking operation';
$lang['aae_quote_preview_total'] = 'Quote total';
$lang['aae_estimate_created'] = 'Estimate created successfully.';
$lang['aae_estimate_failed'] = 'Unable to create the estimate.';
$lang['aae_client_not_found'] = 'Client not found.';
$lang['aae_vehicle_not_found'] = 'Vehicle not found.';
$lang['aae_quote_detail_line_fob'] = 'Factory price (FOB): %s $';
$lang['aae_quote_detail_line_trans'] = 'Translations: %s $';
$lang['aae_quote_detail_line_shipping'] = 'Delivery: %s $';
$lang['aae_quote_detail_line_usd'] = 'Total USD: %s $';
$lang['aae_quote_detail_line_customs'] = 'Estimated customs rate: %s %';

// Import
$lang['aae_import_csv'] = 'CSV import';
$lang['aae_import_hint'] = 'Upload your "CATALOGUE VOITURE - CHINOISES.csv" file as-is. Decorative header rows are ignored automatically.';
$lang['aae_import_select_file'] = 'CSV file to import';
$lang['aae_import_replace_all'] = 'Replace the whole existing catalogue (otherwise: match-update on brand/model/engine/year)';
$lang['aae_import_status'] = 'Default status for imported vehicles';
$lang['aae_import_success'] = 'Import done: %s vehicle(s) added, %s updated.';
$lang['aae_import_failed'] = 'Unable to read the CSV file. Please check the file format.';
$lang['aae_no_file_selected'] = 'No file selected.';
$lang['aae_export_csv'] = 'Export catalogue (CSV)';
$lang['aae_import_go'] = 'Run import';
$lang['aae_replace_warning'] = 'Warning: checking "Replace all" deletes the current catalogue from the CRM.';

// Settings
$lang['aae_settings'] = 'AAE Import settings';
$lang['aae_settings_hint'] = 'These values are used as defaults for new vehicles and calculations (USD amounts, EUR price and DZD value).';
$lang['aae_settings_portal'] = 'Show the "available vehicles" catalogue in the client area';

// Dashboard
$lang['aae_dash_total_vehicles'] = 'Vehicles in catalogue';
$lang['aae_dash_available'] = 'Available';
$lang['aae_dash_ongoing_ops'] = 'Operations in progress';
$lang['aae_dash_revenue'] = 'Revenue (closed files)';
$lang['aae_dash_margin'] = 'Accumulated margin';
$lang['aae_dash_operations_by_status'] = 'Operations by status';
$lang['aae_dash_latest_operations'] = 'Latest operations';
$lang['aae_dash_top_brands'] = 'Distribution by brand';

// Client area
$lang['aae_client_menu_item'] = 'Available vehicles';
$lang['aae_client_catalogue_title'] = 'Available vehicles';
$lang['aae_client_portal_disabled'] = 'The catalogue is currently disabled.';
$lang['aae_client_ask_quote'] = 'Request a quote';

$lang['aae_view_full'] = 'View details';

// Misc / filters / buttons
$lang['aae_filter_all'] = 'All';
$lang['aae_view_list'] = 'List';
$lang['aae_view_kanban'] = 'Kanban';
$lang['aae_search_placeholder'] = 'Search (brand, model, engine, colour...)';
$lang['aae_no_vehicles'] = 'No vehicles in the catalogue. Import your CSV file to get started.';
$lang['aae_no_operations'] = 'No import operations yet.';
$lang['aae_select_vehicle'] = 'Select a vehicle';
$lang['aae_select_client'] = 'No client';
$lang['aae_import_current_count'] = 'Vehicles currently in the catalogue: %s';
$lang['aae_none'] = '—';
$lang['aae_back_to_list'] = 'Back to the list';
$lang['aae_back'] = 'Back';
$lang['aae_client_price'] = 'Price';
$lang['aae_client_of_year'] = 'Year';
$lang['aae_client_engine'] = 'Engine';
$lang['aae_change_status'] = 'Change status';
$lang['aae_next_step'] = 'Next step';
$lang['aae_costs_hint'] = 'Leave unknown values empty: total cost and margin are recalculated automatically.';
$lang['aae_confirm_delete_operation'] = 'Delete this import operation?';
$lang['aae_client_contact_hint'] = 'Contact us for a detailed offer (quote, shipping times, options).';
$lang['aae_client_no_vehicles'] = 'No vehicles available right now. Come back soon!';
