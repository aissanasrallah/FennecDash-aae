<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div id="header">
    <nav>
        <div class="tw-flex tw-justify-between">

            <div class="tw-flex tw-flex-1 sm:tw-flex-initial tw-items-center">
                <button type="button"
                    class="visible-xs visible-sm tw-inline-flex tw-items-center tw-justify-center tw-bg-transparent tw-border-0 tw-ml-1.5 tw-text-neutral-600 hover:tw-text-neutral-800 sidebar-toggle-mobile"
                    onclick="if (window.matchMedia('(max-width: 768px)').matches && document.getElementById('menu')) { $('body').removeClass('hide-sidebar').addClass('show-sidebar'); } else { $('.hide-menu').click(); } return false;">
                    <i class="fa fa-bars fa-lg"></i>
                </button>
                <div id="top_search"
                    class="tw-inline-flex tw-relative dropdown sm:tw-ml-1.5 sm:tw-mr-3 tw-max-w-xl tw-flex-auto tw-group/top-search"
                    data-toggle="tooltip" data-placement="bottom"
                    data-title="<?= _l('search_by_tags'); ?>">
                    <input type="search" id="search_input"
                        class="ltr:tw-pr-4 ltr:tw-pl-9 rtl:tw-pr-9 rtl:tw-pl-4 tw-ml-1 tw-mt-2 focus:!tw-ring-0 tw-w-full !tw-placeholder-neutral-500 !tw-shadow-none tw-text-neutral-800 focus:!tw-placeholder-neutral-600 hover:!tw-placeholder-neutral-600 sm:tw-w-[350px] tw-h-[38px] tw-border-0 tw-border-solid !tw-border-white !tw-bg-neutral-100 !tw-rounded-lg"
                        placeholder="<?= _l('top_search_placeholder'); ?>"
                        autocomplete="off">
                    <div id="top_search_button" class="tw-absolute rtl:tw-right-2 ltr:tw-left-2 tw-top-2.5">
                        <button
                            class="tw-outline-none tw-border-0 tw-p-2 tw-text-neutral-400 group-focus-within/top-search:tw-text-neutral-600 tw-bg-transparent">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                    <div id="search_results">
                    </div>
                    <ul class="dropdown-menu search-results animated fadeIn search-history" id="search-history">
                    </ul>

                </div>
            </div>

            <div class="mobile-menu tw-shrink-0 ltr:tw-ml-4 rtl:tw-mr-4">
                <button type="button"
                    class="navbar-toggle visible-md visible-sm visible-xs mobile-menu-toggle collapsed tw-ml-1.5 tw-text-neutral-600 hover:tw-text-neutral-800"
                    data-toggle="collapse" data-target="#mobile-collapse" aria-expanded="false">
                    <i class="fa fa-chevron-down fa-lg"></i>
                </button>
                <div class="mobile-navbar collapse" id="mobile-collapse" aria-expanded="false" style="height: 0px;"
                    role="navigation">
                    <ul class="nav navbar-nav">
                        <li class="header-my-profile"><a
                                href="<?= admin_url('profile'); ?>">
                                <?= _l('nav_my_profile'); ?>
                            </a>
                        </li>
                        <li class="header-my-timesheets"><a
                                href="<?= admin_url('staff/timesheets'); ?>">
                                <?= _l('my_timesheets'); ?>
                            </a>
                        </li>
                        <li class="header-edit-profile"><a
                                href="<?= admin_url('staff/edit_profile'); ?>">
                                <?= _l('nav_edit_profile'); ?>
                            </a>
                        </li>
                        <?php if (is_staff_member()) { ?>
                        <li class="header-newsfeed">
                            <a href="#" class="open_newsfeed mobile">
                                <?= _l('whats_on_your_mind'); ?>
                            </a>
                        </li>
                        <?php } ?>
                        <li class="header-logout">
                            <a href="#" onclick="logout(); return false;">
                                <?= _l('nav_logout'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <ul class="nav navbar-nav navbar-right -tw-mt-px">
                <?php do_action_deprecated('after_render_top_search', [], '3.0.0', 'admin_navbar_start'); ?>
                <?php hooks()->do_action('admin_navbar_start'); ?>

                <?php $quickActions = collect($this->app->get_quick_actions_links())->reject(function ($action) {
                    return isset($action['permission']) && staff_cant('create', $action['permission']);
                }); ?>
                <?php if ($quickActions->isNotEmpty()) { ?>
                <li class="icon tw-relative ltr:tw-mr-1.5 rtl:tw-ml-1.5"
                    title="<?= _l('quick_create'); ?>"
                    data-toggle="tooltip" data-placement="bottom">
                    <a href="#" class="tw-group" data-toggle="dropdown">
                        <i class="fa fa-th-large fa-lg"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right animated fadeIn tw-text-base">
                        <li class="dropdown-header tw-mb-1">
                            <?= _l('quick_create'); ?>
                        </li>
                        <?php foreach ($quickActions as $key => $item) {
                            $url = '';
                            if (isset($item['permission'])) {
                                if (staff_cant('create', $item['permission'])) {
                                    continue;
                                }
                            }
                            if (isset($item['custom_url'])) {
                                $url = $item['url'];
                            } else {
                                $url = admin_url('' . $item['url']);
                            }
                            $href_attributes = '';
                            if (isset($item['href_attributes'])) {
                                foreach ($item['href_attributes'] as $key => $val) {
                                    $href_attributes .= $key . '="' . $val . '"';
                                }
                            } ?>
                        <li>
                            <a href="<?= e($url); ?>"
                                <?= $href_attributes; ?>
                                class="tw-group tw-inline-flex tw-space-x-0.5 tw-text-neutral-700">
                                <?php if (isset($item['icon'])) { ?>
                                <i class="<?= e($item['icon']); ?> tw-text-neutral-400 group-hover:tw-text-neutral-600 tw-h-5 tw-w-5"></i>
                                <?php } ?>
                                <span><?= e($item['name']); ?></span>
                            </a>
                        </li>
                        <?php } ?>
                    </ul>
                </li>
                <?php } ?>

                <?php if (staff_can('view', 'settings')) { ?>
                <li class="icon">
                    <a href="<?= admin_url('settings'); ?>"
                        data-toggle="tooltip"
                        title="<?= _l('settings'); ?>"
                        data-placement="bottom">
                        <i class="fa-regular fa-sliders"></i>
                    </a>
                </li>
                <?php } ?>

                <?php if (is_staff_member()) { ?>
                <li class="icon header-newsfeed -tw-mr-1.5">
                    <a href="#" class="open_newsfeed desktop" data-toggle="tooltip"
                        title="<?= _l('whats_on_your_mind'); ?>"
                        data-placement="bottom">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor"
                            class="tw-w-[calc(theme(spacing.5)-1px)] tw-h-[calc(theme(spacing.5)-1px)]">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" />
                        </svg>
                    </a>
                </li>
                <?php } ?>

                <li class="icon header-todo">
                    <a href="<?= admin_url('todo'); ?>"
                        data-toggle="tooltip"
                        title="<?= _l('nav_todo_items'); ?>"
                        data-placement="bottom" class="">
                        <i class="fa-regular fa-square-check fa-lg tw-shrink-0"></i>
                        <span
                            class="tw-leading-none tw-px-1 tw-py-0.5 tw-text-xs bg-warning tw-z-10 tw-absolute tw-rounded-full -tw-right-0.5 tw-top-2 tw-min-w-[18px] tw-min-h-[18px] tw-inline-flex tw-items-center tw-justify-center nav-total-todos<?= $current_user->total_unfinished_todos == 0 ? ' hide' : ''; ?>">
                            <?= e($current_user->total_unfinished_todos); ?>
                        </span>
                    </a>
                </li>

                <li class="icon header-timers timer-button tw-relative ltr:tw-mr-1.5 rtl:tw-ml-1.5"
                    data-placement="bottom" data-toggle="tooltip"
                    data-title="<?= _l('my_timesheets'); ?>">
                    <a href="#" id="top-timers" class="top-timers !tw-px-0 tw-group" data-toggle="dropdown">
                        <span class="tw-inline-flex tw-items-center tw-justify-center tw-h-8 tw-w-9 -tw-mt-1.5">
                            <i
                                class="fa-regular fa-clock fa-lg tw-text-neutral-500 group-hover:tw-text-neutral-800 tw-shrink-0<?= count($startedTimers) > 0 ? ' tw-animate-spin-slow' : ''; ?>"></i>
                        </span>
                        <span
                            class="tw-leading-none tw-px-1 tw-py-0.5 tw-text-xs bg-success tw-z-10 tw-absolute tw-rounded-full -tw-right-1.5 tw-top-2 tw-min-w-[18px] tw-min-h-[18px] tw-inline-flex tw-items-center tw-justify-center icon-started-timers<?= $totalTimers = count($startedTimers) == 0 ? ' hide' : ''; ?>">
                            <?= count($startedTimers); ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu animated fadeIn started-timers-top width300" id="started-timers-top">
                        <?php $this->load->view('admin/tasks/started_timers', ['startedTimers' => $startedTimers]); ?>
                    </ul>
                </li>

                <li class="icon dropdown tw-relative tw-block notifications-wrapper header-notifications rtl:tw-ml-3"
                    data-toggle="tooltip"
                    title="<?= _l('nav_notifications'); ?>"
                    data-placement="bottom">
                    <?php $this->load->view('admin/includes/notifications'); ?>
                </li>

                <li class="icon dropdown sidebar-user-profile">
                    <a href="#" class="dropdown-toggle profile tw-flex tw-items-center tw-gap-x-2 tw-py-1.5 tw-px-2 tw-rounded-lg hover:tw-bg-neutral-900/5 focus:tw-bg-neutral-900/5 tw-text-neutral-700 hover:tw-text-neutral-800 focus:tw-text-neutral-800"
                        data-toggle="dropdown" aria-expanded="false">
                        <?= staff_profile_image($current_user->staffid, ['img', 'img-responsive', 'staff-profile-image-small', 'tw-rounded-full', 'tw-h-7', 'tw-w-7']); ?>
                        <span class="tw-hidden sm:tw-inline tw-text-sm tw-font-medium"><?= get_staff_full_name(); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li class="header-my-profile"><a href="<?= admin_url('profile'); ?>"><?= _l('nav_my_profile'); ?></a></li>
                        <li class="header-my-timesheets"><a href="<?= admin_url('staff/timesheets'); ?>"><?= _l('my_timesheets'); ?></a></li>
                        <li class="header-edit-profile"><a href="<?= admin_url('staff/edit_profile'); ?>"><?= _l('nav_edit_profile'); ?></a></li>
                        <?php if (! is_language_disabled()) { ?>
                        <li class="dropdown-submenu pull-left header-languages">
                            <a href="#" tabindex="-1"><?= _l('language'); ?></a>
                            <ul class="dropdown-menu">
                                <li class="<?= $current_user->default_language == '' ? 'active' : ''; ?>">
                                    <a href="<?= admin_url('staff/change_language'); ?>"><?= _l('system_default_string'); ?></a>
                                </li>
                                <?php foreach ($this->app->get_available_languages() as $user_lang) { ?>
                                <li class="<?= $current_user->default_language == $user_lang ? 'active' : ''; ?>">
                                    <a href="<?= admin_url('staff/change_language/' . $user_lang); ?>"><?= e(ucfirst($user_lang)); ?></a>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php } ?>
                        <li class="header-logout">
                            <a href="#" onclick="logout(); return false;"><?= _l('nav_logout'); ?></a>
                        </li>
                    </ul>
                </li>
                <?php hooks()->do_action('admin_navbar_end'); ?>
            </ul>
        </div>
    </nav>
</div>