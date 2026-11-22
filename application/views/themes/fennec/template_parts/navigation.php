<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<nav class="navbar navbar-default header">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed mobile-nav-toggle" aria-expanded="false">
                <i class="ti tabler-menu-2"></i>
            </button>
            <?php
            $company_name = get_option('companyname');
            $logo_light   = get_option('company_logo');
            $logo_dark    = get_option('company_logo_dark');
            $logo_url     = site_url('clients');
            if ($logo_light != '') {
                echo '<a href="' . $logo_url . '" class="logo img-responsive navbar-brand logo-light">';
                echo '<img src="' . base_url('uploads/company/' . $logo_light) . '" class="img-responsive" alt="' . e($company_name) . '">';
                echo '</a>';
            }
            if ($logo_dark != '') {
                echo '<a href="' . $logo_url . '" class="logo img-responsive navbar-brand logo-dark">';
                echo '<img src="' . base_url('uploads/company/' . $logo_dark) . '" class="img-responsive" alt="' . e($company_name) . '">';
                echo '</a>';
            } elseif ($logo_light != '') {
                echo '<a href="' . $logo_url . '" class="logo img-responsive navbar-brand logo-dark">';
                echo '<img src="' . base_url('uploads/company/' . $logo_light) . '" class="img-responsive" alt="' . e($company_name) . '">';
                echo '</a>';
            }
            ?>
        </div>
        <div class="navbar-actions">
            <button class="theme-toggle" title="Toggle theme">
                <i class="fa-regular fa-moon"></i>
            </button>
            <?php if (is_client_logged_in()) { ?>
            <div class="dropdown customers-nav-item-profile">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                    aria-expanded="false">
                    <img src="<?= e(contact_profile_image_url($contact->id, 'thumb')); ?>
" data-toggle="tooltip" data-title="<?= e($contact->firstname . ' ' . $contact->lastname); ?>"
                        data-placement="bottom" class="client-profile-image-small">
                </a>
                <ul class="dropdown-menu animated fadeIn">
                    <li class="customers-nav-item-edit-profile">
                        <a
                            href="<?= site_url('clients/profile'); ?>">
                            <?= _l('clients_nav_profile'); ?>
                        </a>
                    </li>
                    <?php if ($contact->is_primary == 1) { ?>
                    <?php if (can_loggged_in_user_manage_contacts()) { ?>
                    <li class="customers-nav-item-edit-profile">
                        <a
                            href="<?= site_url('contacts'); ?>">
                            <?= _l('clients_nav_contacts'); ?>
                        </a>
                    </li>
                    <?php } ?>
                    <li class="customers-nav-item-company-info">
                        <a
                            href="<?= site_url('clients/company'); ?>">
                            <?= _l('client_company_info'); ?>
                        </a>
                    </li>
                    <?php } ?>
                    <?php if (can_logged_in_contact_update_credit_card()) { ?>
                    <li class="customers-nav-item-stripe-card">
                        <a
                            href="<?= site_url('clients/credit_card'); ?>">
                            <?= _l('credit_card'); ?>
                        </a>
                    </li>
                    <?php } ?>
                    <?php if (is_gdpr() && get_option('show_gdpr_in_customers_menu') == '1') { ?>
                    <li class="customers-nav-item-announcements">
                        <a
                            href="<?= site_url('clients/gdpr'); ?>">
                            <?= _l('gdpr_short'); ?>
                        </a>
                    </li>
                    <?php } ?>
                    <li class="customers-nav-item-announcements">
                        <a
                            href="<?= site_url('clients/announcements'); ?>">
                            <?= _l('announcements'); ?>
                            <?php if ($total_undismissed_announcements != 0) { ?>
                            <span
                                class="badge"><?= e($total_undismissed_announcements); ?></span>
                            <?php } ?>
                        </a>
                    </li>
                    <?php if (! is_language_disabled()) {
                        ?>
                    <li class="dropdown-submenu pull-left customers-nav-item-languages">
                        <a href="#" tabindex="-1">
                            <?= _l('language'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-left">
                            <li class="<?php if (get_contact_language() == '') {
                                echo 'active';
                            } ?>">
                                <a
                                    href="<?= site_url('clients/change_language'); ?>">
                                    <?= _l('system_default_string'); ?>
                                </a>
                            </li>
                            <?php foreach ($this->app->get_available_languages() as $user_lang) { ?>
                            <li <?php if (get_contact_language() == $user_lang) {
                                echo 'class="active"';
                            } ?>>
                                <a
                                    href="<?= site_url('clients/change_language/' . $user_lang); ?>">
                                    <?= e(ucfirst($user_lang)); ?>
                                </a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>
                    <?php
                    } ?>
                    <?= hooks()->do_action('customers_navigation_before_logout'); ?>
                    <li class="customers-nav-item-logout">
                        <a
                            href="<?= site_url('authentication/logout'); ?>">
                            <?= _l('clients_nav_logout'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            <?php } ?>
            <?php hooks()->do_action('customers_navigation_after_profile'); ?>
        </div>
        <!-- Mobile slide-in menu -->
        <div class="collapse navbar-collapse" id="theme-navbar-collapse">
            <div class="mobile-menu-header">
                <button type="button" class="mobile-menu-close mobile-nav-toggle">
                    <i class="ti tabler-x"></i>
                </button>
            </div>
            <ul class="nav navbar-nav navbar-right">
                <?php hooks()->do_action('customers_navigation_start'); ?>
                <?php foreach ($menu as $item_id => $item) { ?>
                <li class="customers-nav-item-<?= e($item_id); ?><?= $item['href'] === current_full_url() ? ' active' : ''; ?>"
                    <?= _attributes_to_string($item['li_attributes'] ?? []); ?>>
                    <a href="<?= e($item['href']); ?>"
                        <?= _attributes_to_string($item['href_attributes'] ?? []); ?>>
                        <?php
                     if (! empty($item['icon'])) {
                         echo '<i class="' . $item['icon'] . '"></i> ';
                     }
                    echo e($item['name']);
                    ?>
                    </a>
                </li>
                <?php } ?>
                <?php hooks()->do_action('customers_navigation_end'); ?>
                <?php if (! is_language_disabled()) { ?>
                <li class="dropdown-submenu customers-nav-item-languages mobile-languages-nav">
                    <a href="#" tabindex="-1">
                        <i class="ti tabler-language"></i> <?= _l('language'); ?> <i class="ti tabler-chevron-down language-arrow"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-left">
                        <li class="<?php if (get_contact_language() == '') { echo 'active'; } ?>">
                            <a href="<?= site_url('clients/change_language'); ?>">
                                <?= _l('system_default_string'); ?>
                            </a>
                        </li>
                        <?php foreach ($this->app->get_available_languages() as $user_lang) { ?>
                        <li <?php if (get_contact_language() == $user_lang) { echo 'class="active"'; } ?>>
                            <a href="<?= site_url('clients/change_language/' . $user_lang); ?>">
                                <?= e(ucfirst($user_lang)); ?>
                            </a>
                        </li>
                        <?php } ?>
                    </ul>
                </li>
                <?php } ?>
            </ul>
            <?php if (is_client_logged_in()) { ?>
            <div class="mobile-menu-footer">
                <a href="<?= site_url('authentication/logout'); ?>" class="mobile-logout">
                    <i class="ti tabler-logout"></i> <?= _l('clients_nav_logout'); ?>
                </a>
            </div>
            <?php } ?>
        </div>
        <div class="mobile-menu-overlay"></div>
    </div>
</nav>
<script>
(function(){
    var nav = document.getElementById('theme-navbar-collapse');
    var overlay = document.querySelector('.mobile-menu-overlay');
    var toggles = document.querySelectorAll('.mobile-nav-toggle');
    if (!nav) return;
    toggles.forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            nav.classList.toggle('in');
            if (overlay) overlay.style.display = nav.classList.contains('in') ? 'block' : '';
        });
    });
    if (overlay) overlay.addEventListener('click', function() {
        nav.classList.remove('in');
        overlay.style.display = 'none';
    });
})();
</script>
