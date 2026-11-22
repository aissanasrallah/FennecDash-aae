<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="<?= e($locale); ?>">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="generator" content="Fennec Dash" />
	<title><?= $title ?? ''; ?></title>
	<script>
	(function(){
		var theme = localStorage.getItem('fennec-client-theme') || localStorage.getItem('fennec-theme');
		if (theme === 'dark') {
			document.documentElement.setAttribute('data-bs-theme', 'dark');
		}
	})();
	</script>
	<?= compile_theme_css(); ?>
	<link href="<?= base_url('assets/css/tabler/tabler-icons.min.css'); ?>" rel="stylesheet">
	<style>
	/* Guaranteed fixes — inline to avoid cascade conflicts (style.min.css + tailwind.css override) */
	.navbar-brand.logo-light,
	.fennec-doc-logo-light { display: none !important; }
	[data-bs-theme="dark"] .navbar-brand.logo-light { display: flex !important; }
	[data-bs-theme="dark"] .fennec-doc-logo-light { display: inline-block !important; }
	[data-bs-theme="dark"] .navbar-brand.logo-dark,
	[data-bs-theme="dark"] .fennec-doc-logo-dark { display: none !important; }
	[data-bs-theme="dark"] .preview-sticky-header { background: var(--v-navbar-bg, #2F3349) !important; border-bottom: 1px solid var(--v-border, #434968); }
	[data-bs-theme="dark"] .tw-text-neutral-600,
	[data-bs-theme="dark"] .tw-text-neutral-700,
	[data-bs-theme="dark"] .tw-text-neutral-800,
	[data-bs-theme="dark"] .tw-text-neutral-900,
	[data-bs-theme="dark"] .\!tw-text-neutral-700,
	[data-bs-theme="dark"] .panel-body h1,
	[data-bs-theme="dark"] .panel-body h2,
	[data-bs-theme="dark"] .panel-body h3,
	[data-bs-theme="dark"] .panel-body h4,
	[data-bs-theme="dark"] .panel-body strong,
	[data-bs-theme="dark"] .panel-body .bold { color: var(--v-text-heading, #CFD3EC) !important; }
	[data-bs-theme="dark"] .tw-text-neutral-500,
	[data-bs-theme="dark"] .panel-body address,
	[data-bs-theme="dark"] .panel-body p,
	[data-bs-theme="dark"] .panel-body .table > tbody > tr > td,
	[data-bs-theme="dark"] .panel-body .table > thead > tr > th { color: var(--v-text, #B6BEE3) !important; border-color: var(--v-border, #434968) !important; }
	[data-bs-theme="dark"] .panel-body .table > thead > tr { background: rgba(47, 51, 73, 0.75) !important; }
	[data-bs-theme="dark"] .panel-body .text-danger { color: #EA5455 !important; }
	.navbar-actions .dropdown-menu { right: 10px !important; left: auto !important; min-width: 200px !important; }
	/* Suppress style.min.css Font Awesome chevron double-icon on language items */
	.customers-nav-item-languages > a::after,
	.collapse .customers-nav-item-languages > a::after,
	.dropdown-menu .customers-nav-item-languages > a::after {
		display: none !important;
	}
	/* Suppress tailwind.css border caret on dropdown-submenu in collapsed nav */
	.collapse .dropdown-submenu > a::after {
		display: none !important;
	}
	@media (max-width: 767px) {
		.navbar-actions .dropdown-menu .customers-nav-item-languages { display: none !important; }
		/* Mobile profile dropdown language gets Font Awesome chevron via style.css (not suppressed) */
		.navbar-actions .dropdown-menu .customers-nav-item-languages > a::after {
			display: inline-block !important;
			content: "\f0d7" !important;
			font-family: "Font Awesome 6 Free" !important;
			font-weight: 900 !important;
			border: none !important;
			margin-top: 2px !important;
		}
	}
	@media (min-width: 1200px) {
		.navbar-header .navbar-toggle { display: none !important; }
		.navbar-default .container { position: relative !important; }
		.navbar-actions { position: absolute !important; right: 15px !important; top: 50% !important; transform: translateY(-50%) !important; height: auto !important; z-index: 1 !important; }
		.navbar-default .navbar-nav.navbar-right { margin-right: 140px !important; float: none !important; }
		.navbar-default .navbar-nav > li > a { padding: 8px 6px !important; font-size: 0.82rem !important; }
		.navbar-default .navbar-nav li.mobile-languages-nav { display: none !important; }
	}
	@media (max-width: 1199px) {
		.navbar-header .navbar-toggle { display: block !important; }
	}
	</style>
	<script
		src="<?= base_url('assets/plugins/jquery/jquery.min.js'); ?>">
	</script>
	<?php app_customers_head(); ?>
	<script>window.FennecCRM = {version: '<?= e(get_option('installed_version')); ?>'};</script>
</head>

<body
	class="customers <?= strtolower($this->agent->browser()); ?><?= is_mobile() ? ' mobile' : ''; ?><?= isset($bodyclass) ? ' ' . $bodyclass : ''; ?>"
	<?= $isRTL == 'true' ? 'dir="rtl"' : ''; ?>>

	<?php hooks()->do_action('customers_after_body_start'); ?>