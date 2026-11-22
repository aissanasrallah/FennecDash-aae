<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin font-bold"><?php echo lg_html_entity_decode($title); ?></h4>
                        <hr class="hr-panel-heading" />
                        <?php echo form_open($this->uri->uri_string(), ['id' => 'lg-cost-form']); ?>
                        <?php $tType = isset($cost) ? $cost->transport_type : 'internal'; ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transport_type"><span class="text-danger">* </span><?php echo _l('lg_transport_type'); ?></label>
                                    <select name="transport_type" id="cost_transport_type" class="selectpicker" data-width="100%">
                                        <option value="internal" <?php if ($tType === 'internal') echo 'selected'; ?>><?php echo _l('lg_internal_fleet'); ?></option>
                                        <option value="external" <?php if ($tType === 'external') echo 'selected'; ?>><?php echo _l('lg_external_carrier'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php $curCat = isset($cost) ? $cost->cost_category : 'fuel'; ?>
                                <div class="form-group">
                                    <label for="cost_category"><span class="text-danger">* </span><?php echo _l('lg_cost_category'); ?></label>
                                    <select name="cost_category" id="cost_category" class="selectpicker" data-width="100%">
                                        <option value="fuel" data-type="internal" <?php if ($curCat === 'fuel') echo 'selected'; ?>><?php echo _l('lg_cost_fuel'); ?></option>
                                        <option value="maintenance" data-type="internal" <?php if ($curCat === 'maintenance') echo 'selected'; ?>><?php echo _l('lg_cost_maintenance'); ?></option>
                                        <option value="driver_expense" data-type="internal" <?php if ($curCat === 'driver_expense') echo 'selected'; ?>><?php echo _l('lg_cost_driver_expense'); ?></option>
                                        <option value="other_operational" data-type="internal" <?php if ($curCat === 'other_operational') echo 'selected'; ?>><?php echo _l('lg_cost_other_operational'); ?></option>
                                        <option value="carrier_fee" data-type="external" <?php if ($curCat === 'carrier_fee') echo 'selected'; ?>><?php echo _l('lg_cost_carrier_fee'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <?php echo render_date_input('cost_date', 'lg_cost_date', isset($cost) ? _d($cost->cost_date) : _d(date('Y-m-d')), ['required' => 'true']); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_input('amount', 'lg_cost_amount', isset($cost) ? $cost->amount : '', 'number', ['step' => '0.01', 'min' => '0', 'required' => 'true']); ?>
                            </div>
                            <div class="col-md-4">
                                <?php $pSt = isset($cost) ? $cost->payment_status : 'unpaid'; ?>
                                <div class="form-group">
                                    <label for="payment_status"><?php echo _l('payment_status'); ?></label>
                                    <select name="payment_status" id="payment_status" class="selectpicker" data-width="100%">
                                        <option value="unpaid" <?php if ($pSt === 'unpaid') echo 'selected'; ?>><?php echo _l('unpaid'); ?></option>
                                        <option value="partial" <?php if ($pSt === 'partial') echo 'selected'; ?>><?php echo _l('partially_paid'); ?></option>
                                        <option value="paid" <?php if ($pSt === 'paid') echo 'selected'; ?>><?php echo _l('paid'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <?php
                                $shipOpts = [];
                                foreach ($shippings as $s) {
                                    $shipOpts[] = ['id' => $s['id'], 'label' => $s['shipping_prefix'] . $s['number_code']];
                                }
                                echo render_select('shipping_id', $shipOpts, ['id', 'label'], 'lg_shipping', isset($cost) ? $cost->shipping_id : '');
                                ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_select('office_group_id', $office_groups, ['id', 'office_name'], 'lg_assigned_depot', isset($cost) ? $cost->office_group_id : ''); ?>
                            </div>

                            <div id="cost_internal_box" class="<?php echo ($tType === 'external' ? 'hide' : ''); ?>">
                                <div class="col-md-6">
                                    <?php echo render_select('driver_id', $drivers, ['staffid', 'full_name'], 'lg_driver', isset($cost) ? $cost->driver_id : ''); ?>
                                </div>
                                <div class="col-md-6">
                                    <?php echo render_select('vehicle_id', $vehicles, ['id', 'vehicle_code'], 'lg_vehicle', isset($cost) ? $cost->vehicle_id : ''); ?>
                                </div>
                            </div>

                            <div id="cost_external_box" class="<?php echo ($tType === 'internal' ? 'hide' : ''); ?>">
                                <div class="col-md-6">
                                    <?php echo render_select('carrier_id', $carriers, ['id', 'shipping_company_name'], 'lg_courrier_company', isset($cost) ? $cost->carrier_id : ''); ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <?php echo render_input('reference_no', 'lg_reference_no', isset($cost) ? $cost->reference_no : ''); ?>
                            </div>
                            <div class="col-md-12">
                                <?php echo render_textarea('notes', 'lg_note', isset($cost) ? $cost->notes : ''); ?>
                            </div>
                        </div>
                        <div class="btn-bottom-toolbar text-right">
                            <a href="<?php echo admin_url('logistic/transport_costs'); ?>" class="btn btn-default"><?php echo _l('close'); ?></a>
                            <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
(function($){
  "use strict";
  function syncCostBoxes(){
    var t = $('#cost_transport_type').val();
    if(t === 'external'){
      $('#cost_internal_box').addClass('hide');
      $('#cost_external_box').removeClass('hide');
      $('#cost_category').val('carrier_fee').selectpicker('refresh');
    } else {
      $('#cost_external_box').addClass('hide');
      $('#cost_internal_box').removeClass('hide');
      if($('#cost_category').val() === 'carrier_fee'){
        $('#cost_category').val('fuel').selectpicker('refresh');
      }
    }
  }
  $('#cost_transport_type').on('change', syncCostBoxes);
})(jQuery);
</script>
</body>
</html>
