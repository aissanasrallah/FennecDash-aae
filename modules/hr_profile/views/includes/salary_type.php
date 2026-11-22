<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div>
	<div class="_buttons">
		<?php if (has_permission('hrm_setting', '', 'create') || is_admin()) { ?>
			<a href="#" onclick="new_salary_form(); return false;" class="btn btn-primary pull-left display-block">
				<?php echo _l('hr_hr_add'); ?>
			</a>
		<?php } ?>
	</div>
	<div class="clearfix"></div>
	<br>

	<table class="table dt-table">
		<thead>
			<th><?php echo _l('hr_salary_form_name'); ?></th>
			<th><?php echo _l('hr_salary_calc_mode'); ?></th>
			<th><?php echo _l('hr_amount_of_money'); ?> / %</th>
			<th><?php echo _l('hr_linked_invoiced_goal'); ?></th>
			<th><?php echo _l('hr_revenue_attribution'); ?></th>
			<th><?php echo _l('options'); ?></th>
		</thead>
		<tbody>
			<?php foreach ($salary_form as $c) {
				$calcMode = $c['salary_calc_mode'] ?? 'fixed';
				$isPct    = ($calcMode === 'percentage');
				$attrMode = ($c['revenue_attribution'] ?? 'individual') === 'shared' ? _l('hr_attr_shared') : _l('hr_attr_individual');
			?>
				<tr>
					<td><?php echo e($c['form_name']); ?></td>
					<td>
						<span class="label label-<?php echo $isPct ? 'info' : 'default'; ?>">
							<?php echo $isPct ? _l('hr_salary_mode_percentage') : _l('hr_salary_mode_fixed'); ?>
						</span>
					</td>
					<td>
						<?php if ($isPct) { ?>
							<strong><?php echo number_format((float) ($c['percentage_rate'] ?? 0), 2, '.', ''); ?> %</strong>
						<?php } else { ?>
							<?php echo app_format_money((float) $c['salary_val'], ''); ?>
						<?php } ?>
					</td>
					<td>
						<?php if ($isPct && !empty($c['goal_id'])) { ?>
							<a href="<?php echo admin_url('goals/goal/' . (int) $c['goal_id']); ?>" target="_blank">
								#<?php echo (int) $c['goal_id']; ?> — <?php echo e($c['goal_subject'] ?? ''); ?>
							</a>
						<?php } else { ?>
							—
						<?php } ?>
					</td>
					<td><?php echo $isPct ? e($attrMode) : '—'; ?></td>
					<td>
						<?php if (has_permission('hrm_setting', '', 'edit') || is_admin()) { ?>
							<a href="#" onclick="edit_salary_form(this,<?php echo (int) $c['form_id']; ?>); return false"
								data-taxable="<?php echo (int) $c['tax']; ?>"
								data-name="<?php echo e($c['form_name']); ?>"
								data-amount="<?php echo e($c['salary_val']); ?>"
								data-calc-mode="<?php echo e($calcMode); ?>"
								data-percentage-rate="<?php echo e($c['percentage_rate'] ?? '0'); ?>"
								data-goal-id="<?php echo (int) ($c['goal_id'] ?? 0); ?>"
								data-attribution="<?php echo e($c['revenue_attribution'] ?? 'individual'); ?>"
								data-min-pct="<?php echo e($c['min_achievement_pct'] ?? '0'); ?>"
								class="btn btn-default btn-icon"><i class="fa-regular fa-pen-to-square"></i></a>
						<?php } ?>

						<?php if (has_permission('hrm_setting', '', 'delete') || is_admin()) { ?>
							<a href="<?php echo admin_url('hr_profile/delete_salary_form/' . (int) $c['form_id']); ?>" class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
						<?php } ?>
					</td>
				</tr>
			<?php } ?>
		</tbody>
	</table>

	<hr class="hr-panel-separator">
	<h4 class="bold mtop20"><?php echo _l('hr_commission_calculator_title'); ?></h4>
	<p class="text-muted">
		Formule : <code>Commission = CA net éligible HT (Factures hors annulées/brouillons − Avoirs HT) × Pourcentage / 100</code>.
		Aucune écriture comptable automatique n'est générée. Les doublons sont bloqués par employé, règle et période.
	</p>

	<?php
	$pctSalaryForms = array_values(array_filter($salary_form, function ($sf) {
		return ($sf['salary_calc_mode'] ?? 'fixed') === 'percentage';
	}));
	?>

	<?php if (has_permission('hrm_setting', '', 'edit') || has_permission('hrm_setting', '', 'create') || is_admin()) { ?>
		<?php echo form_open(admin_url('hr_profile/calculate_percentage_salary_commission'), ['id' => 'calc_commission_form', 'class' => 'well']); ?>
		<div class="row">
			<div class="col-md-3">
				<div class="form-group">
					<label for="comm_staff_id"><?php echo _l('staff_member'); ?></label>
					<select name="staff_id" id="comm_staff_id" class="selectpicker" data-width="100%" data-live-search="true" required>
						<option value=""></option>
						<?php foreach (($active_staff ?? []) as $st) { ?>
							<option value="<?php echo (int) $st['staffid']; ?>"><?php echo e($st['firstname'] . ' ' . $st['lastname']); ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="comm_salary_form_id"><?php echo _l('hr_salary_mode_percentage'); ?></label>
					<select name="salary_form_id" id="comm_salary_form_id" class="selectpicker" data-width="100%" data-live-search="true" required>
						<option value=""></option>
						<?php foreach ($pctSalaryForms as $psf) { ?>
							<option value="<?php echo (int) $psf['form_id']; ?>" data-goal-id="<?php echo (int) ($psf['goal_id'] ?? 0); ?>">
								<?php echo e($psf['form_name']); ?> (<?php echo number_format((float) $psf['percentage_rate'], 2); ?>% — <?php echo e($psf['goal_subject'] ?? ('Objectif #' . (int) $psf['goal_id'])); ?>)
							</option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label for="comm_goal_period_id"><?php echo _l('hr_goal_period'); ?></label>
					<select name="goal_period_id" id="comm_goal_period_id" class="form-control" required>
						<option value="">— Sélectionner d'abord une règle (%) —</option>
					</select>
				</div>
			</div>
			<div class="col-md-2 mtop25">
				<button type="submit" class="btn btn-success btn-block"><?php echo _l('hr_calculate_commission_btn'); ?></button>
			</div>
		</div>
		<?php echo form_close(); ?>
	<?php } ?>

	<?php if (!empty($commission_entries)) { ?>
		<div class="table-responsive mtop15">
			<table class="table table-bordered table-striped dt-table">
				<thead>
					<tr>
						<th><?php echo _l('staff_member'); ?></th>
						<th><?php echo _l('hr_salary_form_name'); ?></th>
						<th><?php echo _l('hr_goal_period'); ?></th>
						<th class="text-right"><?php echo _l('hr_gross_invoiced_ht'); ?></th>
						<th class="text-right"><?php echo _l('hr_credit_notes_ht'); ?></th>
						<th class="text-right"><?php echo _l('hr_eligible_invoiced_ht'); ?></th>
						<th class="text-right">%</th>
						<th class="text-right"><?php echo _l('hr_calculated_commission'); ?></th>
						<th><?php echo _l('options'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($commission_entries as $ce) { ?>
						<tr>
							<td><?php echo e(trim(($ce['firstname'] ?? '') . ' ' . ($ce['lastname'] ?? ''))); ?></td>
							<td>
								<?php echo e($ce['form_name'] ?? ''); ?><br>
								<small class="text-muted"><?php echo e($ce['goal_subject'] ?? ''); ?> (<?php echo e($ce['attribution_mode']); ?>)</small>
							</td>
							<td><?php echo e($ce['period_label'] ?: (_d($ce['period_start']) . ' - ' . _d($ce['period_end']))); ?></td>
							<td class="text-right"><?php echo number_format((float) $ce['gross_invoiced_ht'], 2, '.', ' '); ?> (<?php echo (int) $ce['invoice_count']; ?>)</td>
							<td class="text-right">-<?php echo number_format((float) $ce['credit_notes_ht'], 2, '.', ' '); ?> (<?php echo (int) $ce['credit_note_count']; ?>)</td>
							<td class="text-right"><strong><?php echo number_format((float) $ce['eligible_invoiced_ht'], 2, '.', ' '); ?></strong></td>
							<td class="text-right"><?php echo number_format((float) $ce['percentage_rate'], 2, '.', ''); ?>%</td>
							<td class="text-right"><span class="label label-success" style="font-size:12px;"><?php echo number_format((float) $ce['commission_amount'], 2, '.', ' '); ?> DA</span></td>
							<td>
								<button type="button" class="btn btn-default btn-xs"
									onclick='showCommissionBreakdown(<?php echo htmlspecialchars((string) ($ce['breakdown_json'] ?: '{}'), ENT_QUOTES, 'UTF-8'); ?>)'>
									<?php echo _l('hr_commission_breakdown'); ?>
								</button>
								<?php if ($ce['status'] !== 'paid' && (has_permission('hrm_setting', '', 'edit') || is_admin())) { ?>
									<?php echo form_open(admin_url('hr_profile/calculate_percentage_salary_commission'), ['style' => 'display:inline;']); ?>
									<input type="hidden" name="staff_id" value="<?php echo (int) $ce['staff_id']; ?>">
									<input type="hidden" name="salary_form_id" value="<?php echo (int) $ce['salary_form_id']; ?>">
									<input type="hidden" name="goal_period_id" value="<?php echo (int) $ce['goal_period_id']; ?>">
									<button type="submit" class="btn btn-info btn-xs"><?php echo _l('hr_recalculate_btn'); ?></button>
									<?php echo form_close(); ?>
								<?php } ?>
								<?php if ($ce['status'] !== 'paid' && (has_permission('hrm_setting', '', 'delete') || is_admin())) { ?>
									<a href="<?php echo admin_url('hr_profile/delete_percentage_salary_commission/' . (int) $ce['id']); ?>" class="btn btn-danger btn-xs _delete"><i class="fa fa-remove"></i></a>
								<?php } ?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	<?php } ?>

	<div class="modal" id="salary_form" tabindex="-1" role="dialog">
		<div class="modal-dialog">
			<?php echo form_open(admin_url('hr_profile/salary_form'), ['id' => 'add_salary_form']); ?>
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title">
						<span class="edit-title"><?php echo _l('hr_edit_salary_form'); ?></span>
						<span class="add-title"><?php echo _l('hr_new_salary_form'); ?></span>
					</h4>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-md-12">
							<div id="additional_salary_form"></div>
							<div class="form">
								<?php echo render_input('form_name', 'hr_salary_form_name'); ?>

								<div class="form-group">
									<label for="salary_calc_mode" class="control-label"><?php echo _l('hr_salary_calc_mode'); ?></label>
									<select name="salary_calc_mode" id="salary_calc_mode" class="form-control">
										<option value="fixed"><?php echo _l('hr_salary_mode_fixed'); ?></option>
										<option value="percentage"><?php echo _l('hr_salary_mode_percentage'); ?></option>
									</select>
								</div>

								<div id="fixed_salary_fields">
									<?php
									$arrAtt = ['data-type' => 'currency'];
									echo render_input('salary_val', 'hr_amount_of_money', '', 'text', $arrAtt);
									?>
								</div>

								<div id="percentage_salary_fields" class="hide">
									<div class="row">
										<div class="col-md-6">
											<?php echo render_input('percentage_rate', 'hr_percentage_rate', '', 'number', ['step' => '0.01', 'min' => '0.01', 'max' => '100']); ?>
										</div>
										<div class="col-md-6">
											<?php echo render_input('min_achievement_pct', 'hr_min_achievement_pct', '0', 'number', ['step' => '0.01', 'min' => '0']); ?>
										</div>
									</div>

									<div class="form-group">
										<label for="goal_id" class="control-label"><?php echo _l('hr_linked_invoiced_goal'); ?></label>
										<select name="goal_id" id="salary_goal_id" class="selectpicker" data-width="100%" data-live-search="true">
											<option value=""></option>
											<?php foreach (($invoiced_goals ?? []) as $ig) { ?>
												<option value="<?php echo (int) $ig['id']; ?>">
													#<?php echo (int) $ig['id']; ?> — <?php echo e($ig['subject']); ?> (<?php echo e($ig['start_date'] . ' → ' . $ig['end_date']); ?> | <?php echo e($ig['recurrence_type'] ?? 'one_time'); ?>)
												</option>
											<?php } ?>
										</select>
										<?php if (empty($invoiced_goals)) { ?>
											<small class="text-warning"><?php echo _l('hr_commission_no_invoiced_goals'); ?></small>
										<?php } ?>
									</div>

									<div class="form-group">
										<label for="revenue_attribution" class="control-label"><?php echo _l('hr_revenue_attribution'); ?></label>
										<select name="revenue_attribution" id="revenue_attribution" class="form-control">
											<option value="individual"><?php echo _l('hr_attr_individual'); ?></option>
											<option value="shared"><?php echo _l('hr_attr_shared'); ?></option>
										</select>
									</div>
								</div>

								<div class="form-group hide">
									<label for="tax" class="control-label"><?php echo _l('hr_taxable'); ?></label>
									<select name="tax" class="selectpicker" id="taxable" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
										<option value="0"><?php echo _l('no'); ?></option>
										<option value="1" selected><?php echo _l('yes'); ?></option>
									</select>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_close'); ?></button>
					<button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
				</div>
			</div>
			<?php echo form_close(); ?>
		</div>
	</div>

	<div class="modal fade" id="commission_breakdown_modal" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title"><?php echo _l('hr_commission_breakdown'); ?></h4>
				</div>
				<div class="modal-body" id="commission_breakdown_body"></div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_close'); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
