<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('authentication/includes/head.php'); ?>
<style>
.authentication-inner { min-height: 100vh !important; }
@media (max-width: 420px) {
  .p-6 { padding: 1rem !important; }
}
</style>
<body>
    <div class="authentication-wrapper authentication-cover">
        <div class="authentication-inner row m-0">
            <div class="d-none d-xl-flex col-xl-8 p-0">
                <div class="auth-cover-bg d-flex justify-content-center align-items-center">
                    <img src="<?php echo base_url('assets/img/illustrations/auth-login-illustration-light.png'); ?>"
                        alt="auth-login-cover" class="my-5 auth-illustration"
                        data-app-light-img="illustrations/auth-login-illustration-light.png"
                        data-app-dark-img="illustrations/auth-login-illustration-dark.png">
                    <img src="<?php echo base_url('assets/img/illustrations/bg-shape-image-light.png'); ?>"
                        alt="auth-login-cover" class="platform-bg"
                        data-app-light-img="illustrations/bg-shape-image-light.png"
                        data-app-dark-img="illustrations/bg-shape-image-dark.png">
                </div>
            </div>
            <div class="d-flex col-12 col-xl-4 align-items-center authentication-bg p-sm-12 p-6" style="overflow-y: auto;">
                    <div class="w-px-400 mx-auto mt-12 pt-5">
                    <div class="app-brand justify-content-center mb-6">
                        <a href="<?php echo site_url(); ?>" class="app-brand-link">
                            <span class="app-brand-logo demo">
                                <?php get_dark_company_logo(); ?>
                            </span>
                        </a>
                    </div>
                    <?php if (defined('DEMO_MODE') && DEMO_MODE) { ?>
                    <div class="border rounded p-3 mb-4 bg-light" style="border-color: #dee2e6 !important;">
                      <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="ti ti-key text-muted"></i>
                        <span class="fw-semibold small">Demo Access</span>
                      </div>
                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-user text-primary"></i>
                            <span class="fw-medium small">Client</span>
                          </div>
                          <div class="text-muted small" style="font-size: 0.8rem;">
                            client@fennec.com / client@fennec
                          </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="fillDemoClient()">
                          Fill
                        </button>
                      </div>
                    </div>
                    <?php } ?>
                    <h4 class="mb-1"><?php echo _l(get_option('allow_registration') == 1 ? 'clients_login_heading_register' : 'clients_login_heading_no_register'); ?></h4>
                    <p class="mb-6">Please sign in to your account.</p>

                    <?php if (!is_language_disabled()) { ?>
                    <div class="mb-6">
                        <label for="language" class="form-label"><?php echo _l('language'); ?></label>
                        <select name="language" id="language" class="form-select"
                            onchange="change_contact_language(this)"
                            data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                            <?php $selected = (get_contact_language() != '') ? get_contact_language() : get_option('active_language'); ?>
                            <?php foreach ($this->app->get_available_languages() as $availableLanguage) { ?>
                            <option value="<?php echo e($availableLanguage); ?>"
                                <?php echo ($availableLanguage == $selected) ? 'selected' : ''; ?>>
                                <?php echo e(ucfirst($availableLanguage)); ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <?php } ?>

                    <?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>

                    <?php echo form_open($this->uri->uri_string(), ['id' => 'formAuthentication', 'class' => 'mb-6']); ?>
                    <?php hooks()->do_action('clients_login_form_start'); ?>

                    <div class="mb-6 form-control-validation">
                        <label for="email" class="form-label"><?php echo _l('clients_login_email'); ?></label>
                        <input type="text" class="form-control" id="email" name="email" autofocus>
                        <?php echo form_error('email'); ?>
                    </div>

                    <div class="mb-6 form-password-toggle form-control-validation">
                        <label class="form-label" for="password"><?php echo _l('clients_login_password'); ?></label>
                        <div class="input-group input-group-merge">
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                            <span class="input-group-text cursor-pointer">
                                <i class="icon-base ti tabler-eye-off"></i>
                            </span>
                        </div>
                        <?php echo form_error('password'); ?>
                    </div>

                    <?php if (show_recaptcha_in_customers_area()) { ?>
                    <div class="mb-6">
                        <div class="g-recaptcha"
                            data-sitekey="<?php echo get_option('recaptcha_site_key'); ?>">
                        </div>
                        <?php echo form_error('g-recaptcha-response'); ?>
                    </div>
                    <?php } ?>

                    <div class="my-8">
                        <div class="d-flex justify-content-between">
                            <div class="form-check mb-0 ms-2">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    <?php echo _l('clients_login_remember'); ?>
                                </label>
                            </div>
                            <a href="<?php echo site_url('authentication/forgot_password'); ?>">
                                <p class="mb-0"><?php echo _l('customer_forgot_password'); ?></p>
                            </a>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary d-grid w-100 mb-4">
                        <?php echo _l('clients_login_login_string'); ?>
                    </button>

                    <?php if (get_option('allow_registration') == 1) { ?>
                    <p class="text-center">
                        <span>New on our platform?</span>
                        <a href="<?php echo site_url('authentication/register'); ?>">
                            <span><?php echo _l('clients_register_string'); ?></span>
                        </a>
                    </p>
                    <?php } ?>

                    <?php hooks()->do_action('clients_login_form_end'); ?>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>
<?php if (defined('DEMO_MODE') && DEMO_MODE) { ?>
<script>
function fillDemoClient() {
  document.getElementById('email').value = 'client@fennec.com';
  document.getElementById('password').value = 'client@fennec';
}
</script>
<?php } ?>
</body>
</html>
