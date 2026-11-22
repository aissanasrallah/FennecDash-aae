<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="_buttons">
	<a href="#" class="btn btn-info pull-left" onclick="new_shipping_company(); return false;"><?php echo _l('add_shipping_company'); ?></a>
</div>
<div class="clearfix"></div>
<hr class="hr-panel-heading" />
<div class="clearfix"></div>
<table class="table dt-table">
	<thead>
		<th><?php echo _l('id'); ?></th>
		<th><?php echo _l('name'); ?></th>
		<th><?php echo _l('lg_contact_name'); ?></th>
		<th><?php echo _l('phone'); ?></th>
		<th><?php echo _l('lg_pricing_model'); ?></th>
		<th><?php echo _l('city'); ?></th>
		<th><?php echo _l('active'); ?></th>
		<th><?php echo _l('options'); ?></th>
	</thead>
	<tbody>
		<?php if(isset($shipping_companys) && count($shipping_companys) > 0){ ?>
			<?php foreach($shipping_companys as $shipping_company){ ?>
				<tr>
					<td><?php echo html_entity_decode($shipping_company['id']); ?></td>
					<td><?php echo html_entity_decode($shipping_company['shipping_company_name']); ?></td>
					<td><?php echo html_entity_decode($shipping_company['contact_name'] ?? ''); ?></td>
					<td><?php echo html_entity_decode($shipping_company['phone'] ?? ''); ?></td>
					<td><?php echo html_entity_decode(($shipping_company['pricing_model'] ?? 'fixed') . (is_numeric($shipping_company['default_price'] ?? null) ? ' (' . $shipping_company['default_price'] . ')' : '')); ?></td>
					<td><?php echo html_entity_decode($shipping_company['city'] ?? ''); ?></td>
					<td><?php echo ((int)($shipping_company['active'] ?? 1) === 1) ? '<span class="label label-success">'._l('yes').'</span>' : '<span class="label label-default">'._l('no').'</span>'; ?></td>

					<td>
						<a href="#" onclick="edit_shipping_company(this,<?php echo html_entity_decode($shipping_company['id']); ?>); return false"
						   data-shipping_company_name="<?php echo html_entity_decode($shipping_company['shipping_company_name']); ?>"
						   data-address="<?php echo html_entity_decode($shipping_company['address'] ?? ''); ?>"
						   data-city="<?php echo html_entity_decode($shipping_company['city'] ?? ''); ?>"
						   data-postcode="<?php echo html_entity_decode($shipping_company['postcode'] ?? ''); ?>"
						   data-country="<?php echo html_entity_decode($shipping_company['country'] ?? ''); ?>"
						   data-phone="<?php echo html_entity_decode($shipping_company['phone'] ?? ''); ?>"
						   data-contact_name="<?php echo html_entity_decode($shipping_company['contact_name'] ?? ''); ?>"
						   data-email="<?php echo html_entity_decode($shipping_company['email'] ?? ''); ?>"
						   data-service_areas="<?php echo html_entity_decode($shipping_company['service_areas'] ?? ''); ?>"
						   data-pricing_model="<?php echo html_entity_decode($shipping_company['pricing_model'] ?? 'fixed'); ?>"
						   data-default_price="<?php echo html_entity_decode($shipping_company['default_price'] ?? ''); ?>"
						   data-payment_terms="<?php echo html_entity_decode($shipping_company['payment_terms'] ?? ''); ?>"
						   data-invoice_ref_prefix="<?php echo html_entity_decode($shipping_company['invoice_ref_prefix'] ?? ''); ?>"
						   data-active="<?php echo (int)($shipping_company['active'] ?? 1); ?>"
						   data-notes="<?php echo html_entity_decode($shipping_company['notes'] ?? ''); ?>"
						   class="btn btn-default btn-icon"><i class="fa fa-pencil-square"></i></a>

          				<a href="<?php echo admin_url('logistic/delete_shipping_company/' . $shipping_company['id']); ?>" class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>

					</td>
				</tr>		

			<?php } ?>
		<?php } ?>
	</tbody>
</table>

<div class="modal fade" id="shipping_company_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog withd_1k" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel">
					<span class="edit-title"><?php echo _l('edit_shipping_company'); ?></span>
					<span class="add-title"><?php echo _l('new_shipping_company'); ?></span>
				</h4>
			</div>
			<?php echo form_open('logistic/shipping_company_form',array('id'=>'shipping_company-setting-form')); ?>
			<?php echo form_hidden('shipping_company_id'); ?>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<label for="name"><span class="text-danger">* </span><?php echo _l('lg_shipping_company'); ?></label>
						<?php echo render_input('shipping_company_name','','','text', ['required' => 'true']); ?>

						<?php echo render_textarea('address','address'); ?>

						<?php 
	                     $selected                 = '';
	                     echo render_select('country', $countries, [ 'id', [ 'country_name']], 'clients_country', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]);
	                    ?>

						<?php echo render_input('city','city','','text'); ?>
						<?php echo render_input('postcode','postcode','','text'); ?>
						<?php echo render_input('phone','phone','','text'); ?>
						<?php echo render_input('contact_name','lg_contact_name','','text'); ?>
						<?php echo render_input('email','client_email','','email'); ?>
						<?php echo render_input('service_areas','lg_service_areas','','text'); ?>
						<div class="form-group">
							<label for="pricing_model"><?php echo _l('lg_pricing_model'); ?></label>
							<select name="pricing_model" id="pricing_model" class="selectpicker" data-width="100%">
								<option value="fixed"><?php echo _l('lg_pricing_fixed'); ?></option>
								<option value="distance"><?php echo _l('lg_pricing_distance'); ?></option>
								<option value="negotiated"><?php echo _l('lg_pricing_negotiated'); ?></option>
							</select>
						</div>
						<?php echo render_input('default_price','lg_default_price','','number',['step'=>'0.01','min'=>'0']); ?>
						<?php echo render_input('payment_terms','payment_terms','','text'); ?>
						<?php echo render_input('invoice_ref_prefix','lg_carrier_invoice_prefix','','text'); ?>
						<?php echo render_textarea('notes','note'); ?>
						<div class="checkbox checkbox-primary">
							<input type="checkbox" name="active" id="carrier_active" value="1" checked>
							<label for="carrier_active"><?php echo _l('active'); ?></label>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
				<button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
				<?php echo form_close(); ?>
			</div>
		</div>
	</div>
</div>