<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('authentication/includes/head.php'); ?>
<body>
    <div class="authentication-wrapper authentication-cover">
        <div class="authentication-inner row m-0">
            <div class="d-none d-xl-flex col-xl-8 p-0">
                <div class="auth-cover-bg d-flex justify-content-center align-items-center">
                    <img src="<?php echo base_url('assets/img/illustrations/auth-reset-password-illustration-light.png'); ?>"
                        alt="auth-reset-password-cover" class="my-5 auth-illustration"
                        data-app-light-img="illustrations/auth-reset-password-illustration-light.png"
                        data-app-dark-img="illustrations/auth-reset-password-illustration-dark.png">
                    <img src="<?php echo base_url('assets/img/illustrations/bg-shape-image-light.png'); ?>"
                        alt="auth-reset-password-cover" class="platform-bg"
                        data-app-light-img="illustrations/bg-shape-image-light.png"
                        data-app-dark-img="illustrations/bg-shape-image-dark.png">
                </div>
            </div>
            <div class="d-flex col-12 col-xl-4 align-items-center authentication-bg p-6 p-sm-12">
                <div class="w-px-400 mx-auto mt-12 pt-5">
                    <div class="app-brand justify-content-center mb-6">
                        <a href="<?php echo site_url(); ?>" class="app-brand-link">
                            <span class="app-brand-logo demo">
                                <?php get_dark_company_logo(); ?>
                            </span>
                        </a>
                    </div>
                    <h4 class="mb-1"><?php echo _l('customer_reset_password_heading'); ?></h4>
                    <p class="mb-6">Your new password must be different from previously used passwords.</p>

                    <?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>
                    <?php if ($this->session->flashdata('message-danger')) { ?>
                    <div class="alert alert-danger">
                        <?php echo $this->session->flashdata('message-danger'); ?>
                    </div>
                    <?php } ?>

                    <?php echo form_open($this->uri->uri_string(), ['id' => 'formAuthentication', 'class' => 'mb-6']); ?>

                    <div class="mb-6 form-password-toggle form-control-validation">
                        <label class="form-label" for="password"><?php echo _l('customer_reset_password'); ?></label>
                        <div class="input-group input-group-merge">
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                            <span class="input-group-text cursor-pointer">
                                <i class="icon-base ti tabler-eye-off"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-6 form-password-toggle form-control-validation">
                        <label class="form-label" for="passwordr"><?php echo _l('customer_reset_password_repeat'); ?></label>
                        <div class="input-group input-group-merge">
                            <input type="password" id="passwordr" name="passwordr" class="form-control"
                                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                            <span class="input-group-text cursor-pointer">
                                <i class="icon-base ti tabler-eye-off"></i>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary d-grid w-100 mb-6">
                        <?php echo _l('customer_reset_action'); ?>
                    </button>

                    <?php echo form_close(); ?>

                    <p class="text-center">
                        <a href="<?php echo site_url('authentication/login'); ?>" class="d-flex justify-content-center">
                            <i class="icon-base ti tabler-chevron-left scaleX-n1-rtl me-1_5"></i>
                            Back to login
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
