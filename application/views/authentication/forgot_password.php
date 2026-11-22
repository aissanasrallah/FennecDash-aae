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

                    <h4 class="mb-1"><?php echo _l('admin_auth_forgot_password_heading'); ?></h4>
                    <p class="mb-6">Enter your email address and we'll send you a link to reset your password.</p>

                    <?php $this->load->view('authentication/includes/alerts'); ?>
                    <?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>

                    <?php echo form_open($this->uri->uri_string(), ['id' => 'formAuthentication', 'class' => 'mb-4']); ?>

                    <div class="mb-6 form-control-validation">
                        <label for="email" class="form-label"><?php echo _l('admin_auth_forgot_password_email'); ?></label>
                        <input type="email" id="email" name="email" class="form-control"
                            value="<?php echo set_value('email'); ?>" autofocus>
                    </div>

                    <div class="mb-6">
                        <button type="submit" class="btn btn-primary d-grid w-100">
                            <?php echo _l('admin_auth_forgot_password_button'); ?>
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
