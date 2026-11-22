<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('authentication/includes/head.php'); ?>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
            <div class="card">
                <div class="card-body">
                    <div class="app-brand justify-content-center mb-6">
                        <a href="<?php echo admin_url(); ?>" class="app-brand-link">
                            <span class="app-brand-logo demo">
                                <?php get_dark_company_logo(); ?>
                            </span>
                        </a>
                    </div>

                    <?php if (defined('DEMO_MODE') && DEMO_MODE) { ?>
                    <div class="border rounded p-3 mb-4 bg-light" style="border-color: #dee2e6 !important;">
                      <div class="d-flex align-items-center gap-2 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted" style="flex-shrink:0"><path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1-4.069 0l-.301-.301l-6.297 6.297a2 2 0 0 1-1.414.586H4.5a.5.5 0 0 1-.5-.5v-1.5a2 2 0 0 1 .586-1.414l6.297-6.297l-.301-.301a2.877 2.877 0 0 1 0-4.069l2.643-2.643a2.877 2.877 0 0 1 4.069 0z"/><path d="M15 9l3-3M18.414 5.586a2 2 0 0 1 0 2.828l-1.5 1.5a2 2 0 0 1-2.828 0l-1.5-1.5a2 2 0 0 1 0-2.828l1.5-1.5a2 2 0 0 1 2.828 0z"/>
                        <span class="fw-semibold small">Demo Access</span>
                      </div>
                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          <div class="d-flex align-items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary" style="flex-shrink:0"><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1-8.5 15A12 12 0 0 1 3.5 6a12 12 0 0 0 8.5-3"/><path d="M9 12l2 2l4-4"/>
                            <span class="fw-medium small">Admin</span>
                          </div>
                          <div class="text-muted small" style="font-size: 0.8rem;">
                            admin@fennec.com / admin@fennec
                          </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="fillDemoAdmin()">
                          Fill
                        </button>
                      </div>
                    </div>
                    <?php } ?>

                    <h4 class="mb-1"><?php echo _l('admin_auth_login_heading'); ?></h4>
                    <p class="mb-6"><?php echo _l('welcome_back_sign_in'); ?></p>

                    <?php $this->load->view('authentication/includes/alerts'); ?>
                    <?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>

                    <?php echo form_open($this->uri->uri_string(), ['id' => 'formAuthentication', 'class' => 'mb-4']); ?>
                    <?php hooks()->do_action('after_admin_login_form_start'); ?>

                    <div class="mb-6 form-control-validation">
                        <label for="email" class="form-label"><?php echo _l('admin_auth_login_email'); ?></label>
                        <input type="email" id="email" name="email" class="form-control" autofocus>
                    </div>

                    <div class="mb-6 form-password-toggle form-control-validation">
                        <label class="form-label" for="password"><?php echo _l('admin_auth_login_password'); ?></label>
                        <div class="input-group input-group-merge">
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                            <span class="input-group-text cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828"/><path d="M16.681 16.673A8.717 8.717 0 0 1 12 18.75c-4.33 0-7.75-3-9.5-6.75a14.2 14.2 0 0 1 2.857-4.006m3.193-1.807A8.7 8.7 0 0 1 12 5.25c4.33 0 7.75 3 9.5 6.75a14.1 14.1 0 0 1-1.7 2.5"/><path d="M3 3l18 18"/>
                            </span>
                        </div>
                    </div>

                    <?php if (show_recaptcha()) { ?>
                    <div class="mb-6">
                        <div class="g-recaptcha"
                            data-sitekey="<?php echo get_option('recaptcha_site_key'); ?>">
                        </div>
                    </div>
                    <?php } ?>

                    <div class="my-8">
                        <div class="d-flex justify-content-between">
                            <div class="form-check mb-0 ms-2">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    <?php echo _l('admin_auth_login_remember_me'); ?>
                                </label>
                            </div>
                            <a href="<?php echo admin_url('authentication/forgot_password'); ?>">
                                <p class="mb-0"><?php echo _l('admin_auth_login_fp'); ?></p>
                            </a>
                        </div>
                    </div>

                    <div class="mb-6">
                        <button type="submit" class="btn btn-primary d-grid w-100">
                            <?php echo _l('admin_auth_login_button'); ?>
                        </button>
                    </div>

                    <?php hooks()->do_action('before_admin_login_form_close'); ?>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if (defined('DEMO_MODE') && DEMO_MODE) { ?>
<script>
function fillDemoAdmin() {
  document.getElementById('email').value = 'admin@fennec.com';
  document.getElementById('password').value = 'admin@fennec';
}
</script>
<?php } ?>
</body>
</html>
