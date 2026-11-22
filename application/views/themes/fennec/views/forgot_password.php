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
                    <img src="<?php echo base_url('assets/img/illustrations/auth-forgot-password-illustration-light.png'); ?>"
                        alt="auth-forgot-password-cover" class="my-5 auth-illustration d-lg-block d-none"
                        data-app-light-img="illustrations/auth-forgot-password-illustration-light.png"
                        data-app-dark-img="illustrations/auth-forgot-password-illustration-dark.png">
                    <img src="<?php echo base_url('assets/img/illustrations/bg-shape-image-light.png'); ?>"
                        alt="auth-forgot-password-cover" class="platform-bg"
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
                    <h4 class="mb-1"><?php echo _l('customer_forgot_password_heading'); ?></h4>
                    <p class="mb-6">Enter your email address and we'll send you a link to reset your password.</p>

                    <?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>
                    <?php if ($this->session->flashdata('message-danger')) { ?>
                    <div class="alert alert-danger">
                        <?php echo $this->session->flashdata('message-danger'); ?>
                    </div>
                    <?php } ?>

                    <?php echo form_open($this->uri->uri_string(), ['id' => 'formAuthentication', 'class' => 'mb-6']); ?>

                    <div class="mb-6 form-control-validation">
                        <label for="email" class="form-label"><?php echo _l('customer_forgot_password_email'); ?></label>
                        <input type="email" id="email" name="email" class="form-control"
                            value="<?php echo set_value('email'); ?>" autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary d-grid w-100 mb-6">
                        <?php echo _l('customer_forgot_password_submit'); ?>
                    </button>

                    <?php echo form_close(); ?>

                    <div class="text-center">
                        <a href="<?php echo site_url('authentication/login'); ?>" class="d-flex justify-content-center align-items-center gap-1">
                            <i class="icon-base ti tabler-chevron-left scaleX-n1-rtl"></i>
                            Back to login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
