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

                    <h4 class="mb-1"><?php echo _l('admin_auth_reset_password_heading'); ?></h4>
                    <p class="mb-6">Enter your new password below.</p>

                    <?php $this->load->view('authentication/includes/alerts'); ?>
                    <?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>

                    <?php echo form_open($this->uri->uri_string(), ['id' => 'formAuthentication', 'class' => 'mb-4']); ?>

                    <div class="mb-6 form-password-toggle form-control-validation">
                        <label class="form-label" for="password"><?php echo _l('admin_auth_reset_password'); ?></label>
                        <div class="input-group input-group-merge">
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                            <span class="input-group-text cursor-pointer">
                                <i class="icon-base ti tabler-eye-off"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-6 form-password-toggle form-control-validation">
                        <label class="form-label" for="passwordr"><?php echo _l('admin_auth_reset_password_repeat'); ?></label>
                        <div class="input-group input-group-merge">
                            <input type="password" id="passwordr" name="passwordr" class="form-control"
                                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                            <span class="input-group-text cursor-pointer">
                                <i class="icon-base ti tabler-eye-off"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-6">
                        <button type="submit" class="btn btn-primary d-grid w-100">
                            <?php echo _l('auth_reset_password_submit'); ?>
                        </button>
                    </div>

                    <?php echo form_close(); ?>

                    <p class="text-center mt-4">
                        <a href="<?php echo admin_url('authentication'); ?>">
                            Back to login
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
