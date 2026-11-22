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
                        <?php echo form_open($this->uri->uri_string(), ['id' => 'lg-vehicle-form']); ?>
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('vehicle_code', 'lg_vehicle_code', isset($vehicle) ? $vehicle->vehicle_code : '', 'text', ['required' => 'true']); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_input('registration_plate', 'lg_vehicle_license_plate', isset($vehicle) ? $vehicle->registration_plate : ''); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_input('vehicle_type', 'lg_vehicle_type', isset($vehicle) ? $vehicle->vehicle_type : 'Fourgon'); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_input('brand_model', 'lg_brand_model', isset($vehicle) ? $vehicle->brand_model : ''); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_input('capacity_kg', 'lg_capacity_kg', isset($vehicle) ? $vehicle->capacity_kg : '', 'number', ['step' => '0.01', 'min' => '0']); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_input('fuel_type', 'lg_fuel_type', isset($vehicle) ? $vehicle->fuel_type : ''); ?>
                            </div>
                            <div class="col-md-4">
                                <?php $curStatus = isset($vehicle) ? $vehicle->status : 'available'; ?>
                                <div class="form-group">
                                    <label for="status"><?php echo _l('lg_vehicle_status'); ?></label>
                                    <select name="status" id="status" class="selectpicker" data-width="100%">
                                        <option value="available" <?php if ($curStatus === 'available') echo 'selected'; ?>><?php echo _l('lg_vehicle_available'); ?></option>
                                        <option value="assigned" <?php if ($curStatus === 'assigned') echo 'selected'; ?>><?php echo _l('lg_vehicle_assigned'); ?></option>
                                        <option value="maintenance" <?php if ($curStatus === 'maintenance') echo 'selected'; ?>><?php echo _l('lg_vehicle_maintenance'); ?></option>
                                        <option value="inactive" <?php if ($curStatus === 'inactive') echo 'selected'; ?>><?php echo _l('lg_vehicle_inactive'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_select('office_group_id', $office_groups, ['id', 'office_name'], 'lg_assigned_depot', isset($vehicle) ? $vehicle->office_group_id : ''); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_select('assigned_driver_id', $drivers, ['staffid', 'full_name'], 'lg_assigned_driver', isset($vehicle) ? $vehicle->assigned_driver_id : ''); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_date_input('insurance_expiry', 'lg_insurance_expiry', isset($vehicle) && $vehicle->insurance_expiry ? _d($vehicle->insurance_expiry) : ''); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_date_input('technical_inspection_expiry', 'lg_technical_inspection_expiry', isset($vehicle) && $vehicle->technical_inspection_expiry ? _d($vehicle->technical_inspection_expiry) : ''); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_date_input('last_maintenance_date', 'lg_last_maintenance_date', isset($vehicle) && $vehicle->last_maintenance_date ? _d($vehicle->last_maintenance_date) : ''); ?>
                            </div>
                            <div class="col-md-12">
                                <?php echo render_textarea('maintenance_notes', 'lg_maintenance_notes', isset($vehicle) ? $vehicle->maintenance_notes : ''); ?>
                            </div>
                        </div>
                        <div class="btn-bottom-toolbar text-right">
                            <a href="<?php echo admin_url('logistic/vehicles'); ?>" class="btn btn-default"><?php echo _l('close'); ?></a>
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
</body>
</html>
