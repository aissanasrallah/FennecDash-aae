<!DOCTYPE html>
<html lang="<?= $lang_code ?>" dir="<?= $dir ?>" class="" style="overflow-x: hidden; max-width: 100vw;">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= _l('landing_hero_subtitle') ?>">
<meta property="og:title" content="<?= _l('landing_hero_title') ?> - <?= _l('landing_brand') ?>">
<meta property="og:description" content="<?= _l('landing_hero_subtitle') ?>">
<meta property="og:type" content="website">
<meta property="og:image" content="https://dash.fennec-vision.com/assets/images/landing/og-image.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:type" content="image/png">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= _l('landing_hero_title') ?> - <?= _l('landing_brand') ?>">
<meta name="twitter:description" content="<?= _l('landing_hero_subtitle') ?>">
<meta name="twitter:image" content="https://dash.fennec-vision.com/assets/images/landing/og-image.png">
<meta name="robots" content="index, follow">
<link rel="preload" as="image" href="<?= base_url('assets/images/landing/light.webp') ?>" fetchpriority="high">
<meta name="theme-color" content="#6B21A8">
<link rel="icon" type="image/png" href="<?= base_url('uploads/company/favicon.png') ?>">
<link rel="canonical" href="https://dash.fennec-vision.com/">
<link rel="alternate" hreflang="en" href="https://dash.fennec-vision.com/landing/en">
<link rel="alternate" hreflang="fr" href="https://dash.fennec-vision.com/landing/fr">
<link rel="alternate" hreflang="ar" href="https://dash.fennec-vision.com/landing/ar">
<link rel="alternate" hreflang="x-default" href="https://dash.fennec-vision.com/">
<title><?= _l('landing_hero_title') ?> - <?= _l('landing_brand') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
<link rel="preload" as="style" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css"></noscript>
<link rel="stylesheet" href="<?= base_url('assets/builds/landing-tailwind.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/landing.css') ?>">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "SoftwareApplication",
      "@id": "https://dash.fennec-vision.com/#software",
      "name": "Fennec Dash",
      "alternateName": "FennecDash",
      "applicationCategory": "BusinessApplication",
      "applicationSubCategory": "CRM, ERP, CMS",
      "operatingSystem": "Web, PHP 8, MySQL",
      "description": "<?= str_replace('"', '\"', _l('landing_hero_subtitle')) ?>",
      "url": "https://dash.fennec-vision.com/",
      "image": "https://dash.fennec-vision.com/assets/images/landing/logo-light.png",
      "screenshot": "https://dash.fennec-vision.com/assets/images/landing/light.png",
      "author": { "@type": "Organization", "@id": "https://dash.fennec-vision.com/#organization" },
      "offers": [
        { "@type": "Offer", "name": "Free Trial", "price": "0", "priceCurrency": "USD", "description": "14-day free trial" },
        { "@type": "Offer", "name": "Standard", "price": "550000", "priceCurrency": "DZD", "description": "For small teams" },
        { "@type": "Offer", "name": "Professional", "price": "1650000", "priceCurrency": "DZD", "description": "For growing companies" },
        { "@type": "Offer", "name": "Enterprise", "price": "2450000", "priceCurrency": "DZD", "description": "For large organizations & enterprises" }
      ]
    },
    {
      "@type": "Organization",
      "@id": "https://dash.fennec-vision.com/#organization",
      "name": "Fennec Vision",
      "legalName": "Fennec Vision",
      "url": "https://dash.fennec-vision.com/",
      "logo": "https://dash.fennec-vision.com/assets/images/landing/logo-light.png",
      "description": "Publisher of Fennec Dash — an all-in-one CRM & ERP business platform with 21 integrated modules."
    },
    {
      "@type": "WebSite",
      "@id": "https://dash.fennec-vision.com/#website",
      "url": "https://dash.fennec-vision.com/",
      "name": "CRM \u00b7 ERP \u00b7 CMS \u00b7 MAP \u00b7 PIM \u2014 All-in-One Business Platform",
      "description": "21 integrated modules to manage your entire business \u2014 from sales and accounting to manufacturing and logistics.",
      "publisher": { "@id": "https://dash.fennec-vision.com/#organization" },
      "potentialAction": {
        "@type": "SearchAction",
        "target": { "@type": "EntryPoint", "urlTemplate": "https://dash.fennec-vision.com/?q={search_term_string}" },
        "query-input": "required name=search_term_string"
      }
    },
    {
      "@type": "FAQPage",
      "@id": "https://dash.fennec-vision.com/#faq",
      "mainEntity": [
        {
          "@type": "Question", "name": "How does migration from my old CRM work?",
          "acceptedAnswer": { "@type": "Answer", "text": "Our engineering team supports you throughout the process. The CSV Export Manager module allows you to extract, transform, and load your historical data. A personalized mapping is established to guarantee the integrity of your customer information, transactions, and stock." }
        },
        {
          "@type": "Question", "name": "Are my data secure and GDPR-compliant?",
          "acceptedAnswer": { "@type": "Answer", "text": "Absolutely. Fennec Dash is hosted on ISO 27001 certified infrastructure. All data is encrypted in transit (TLS 1.3) and at rest (AES-256). The Database Backup module ensures automatic backups. We are fully GDPR compliant." }
        },
        {
          "@type": "Question", "name": "What is the average deployment time?",
          "acceptedAnswer": { "@type": "Answer", "text": "For the Standard plan, your instance is operational in less than 24 hours. Professional and Enterprise plans benefit from personalized onboarding with an average of 3 to 5 business days." }
        },
        {
          "@type": "Question", "name": "Can I try Fennec Dash without commitment?",
          "acceptedAnswer": { "@type": "Answer", "text": "Yes, all our plans include a 14-day trial period without requiring a credit card. You get full access to all features of the chosen plan." }
        },
        {
          "@type": "Question", "name": "What types of support are available?",
          "acceptedAnswer": { "@type": "Answer", "text": "Email support is included in all plans. Professional and Enterprise plans also include 24/7 live chat and a dedicated account manager. Our average response time is 15 minutes for priority tickets and 2 hours for standard requests." }
        }
      ]
    }
  ]
}
</script>
<style>
body { max-width: 100vw; overflow-x: hidden; }
@media (max-width: 640px) {
  .compare-container { max-width: 100% !important; min-height: 200px !important; }
  .compare-container .compare-slider { display: none !important; }
  .compare-container img:last-child { display: none !important; }
  .geo-bg, .geo-line { display: none !important; }
  [class*="text-3xl"][class*="lg:text-2xl"] { font-size: 1.5rem !important; line-height: 2rem !important; }
  #moduleGrid .group { padding: 0.75rem !important; }
  #moduleGrid .group .w-10 { width: 2rem !important; height: 2rem !important; }
  #moduleGrid .group .w-10 i { font-size: 1rem !important; }
  .lg\\:max-w-xl { max-width: 100% !important; }
}
</style>
</head>
<body class="antialiased transition-colors duration-300">

<!-- NAVBAR -->
<nav class="glass-nav fixed top-0 left-0 right-0 z-50 transition-all duration-300" id="navbar">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">
            <!-- Logo -->
            <a href="<?= site_url() ?>" class="flex items-center gap-2.5 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <img src="<?= base_url('assets/images/landing/logo-dark.png') ?>" alt="Fennec Dash" class="h-12 w-auto block dark:hidden">
                    <img src="<?= base_url('assets/images/landing/logo-light.png') ?>" alt="Fennec Dash" class="h-12 w-auto hidden dark:block">
                </div>
            </a>

            <!-- Center Links -->
            <div class="hidden md:flex items-center gap-8">
                <a href="#modules" class="text-sm font-medium text-[#6B7280] hover:text-[#6B21A8] dark:text-gray-400 dark:hover:text-[#A855F7] transition-colors"><?= _l('landing_nav_modules') ?></a>
                <a href="#features" class="text-sm font-medium text-[#6B7280] hover:text-[#6B21A8] dark:text-gray-400 dark:hover:text-[#A855F7] transition-colors"><?= _l('landing_nav_features') ?></a>
                <a href="#advantages" class="text-sm font-medium text-[#6B7280] hover:text-[#6B21A8] dark:text-gray-400 dark:hover:text-[#A855F7] transition-colors"><?= _l('landing_nav_advantages') ?></a>
                <a href="#pricing" class="text-sm font-medium text-[#6B7280] hover:text-[#6B21A8] dark:text-gray-400 dark:hover:text-[#A855F7] transition-colors"><?= _l('landing_nav_pricing') ?></a>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-3">
                <!-- Lang Switcher -->
                <div class="relative hidden sm:block" id="langSwitcher">
                    <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium text-[#6B7280] hover:bg-[#E5E7EB] dark:hover:bg-gray-800 transition-all border border-transparent hover:border-[#E5E7EB] dark:hover:border-gray-700" id="langBtn">
                        <i class="ti ti-language text-base"></i>
                        <span><?= strtoupper(substr($language, 0, 2)) ?></span>
                    </button>
                    <div class="absolute top-full right-0 mt-1 min-w-[130px] bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-700 rounded-lg shadow-xl opacity-0 invisible translate-y-[-4px] transition-all z-50 overflow-hidden" id="langDropdown">
                        <a href="<?= site_url('landing/en') ?>" class="block px-4 py-2.5 text-sm font-medium text-[#6B7280] dark:text-gray-400 hover:bg-[#F3F4F6] dark:hover:bg-gray-800 <?= $language == 'english' ? 'text-[#6B21A8] dark:text-[#A855F7] font-bold' : '' ?>"><?= _l('landing_lang_en') ?></a>
                        <a href="<?= site_url('landing/fr') ?>" class="block px-4 py-2.5 text-sm font-medium text-[#6B7280] dark:text-gray-400 hover:bg-[#F3F4F6] dark:hover:bg-gray-800 <?= $language == 'french' ? 'text-[#6B21A8] dark:text-[#A855F7] font-bold' : '' ?>"><?= _l('landing_lang_fr') ?></a>
                        <a href="<?= site_url('landing/ar') ?>" class="block px-4 py-2.5 text-sm font-medium text-[#6B7280] dark:text-gray-400 hover:bg-[#F3F4F6] dark:hover:bg-gray-800 <?= $language == 'arabic' ? 'text-[#6B21A8] dark:text-[#A855F7] font-bold' : '' ?>" lang="ar"><?= _l('landing_lang_ar') ?></a>
                    </div>
                </div>

                <!-- Theme Toggle -->
                <button id="themeToggle" class="w-10 h-10 rounded-lg flex items-center justify-center text-[#6B7280] hover:bg-[#E5E7EB] dark:hover:bg-gray-800 transition-all" aria-label="<?= _l('landing_theme_light') ?>">
                    <i id="themeIcon" class="ti ti-sun text-lg"></i>
                </button>

                <!-- Client Login -->
                <a href="<?= site_url('login') ?>" class="hidden sm:inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-[#1F1F1F] dark:text-white border border-[#E5E7EB] dark:border-gray-700 hover:border-[#6B21A8] dark:hover:border-[#A855F7] transition-all gap-2">
                    <i class="ti ti-user text-base"></i>
                    Client Login
                </a>

                <!-- Admin Login -->
                <a href="<?= admin_url('authentication') ?>" class="hidden sm:inline-flex items-center px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-[#6B21A8] hover:bg-[#581C87] transition-all shadow-lg shadow-purple-500/20 hover:shadow-purple-500/30 gap-2">
                    <i class="ti ti-shield-lock text-base"></i>
                    Admin Login
                </a>

                <!-- Mobile Menu Button -->
                <button id="mobileMenuBtn" class="md:hidden w-10 h-10 rounded-lg flex items-center justify-center text-[#1F1F1F] dark:text-white">
                    <i class="ti ti-menu-2 text-xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="hidden md:hidden glass-nav border-t border-[#E5E7EB] dark:border-gray-800">
        <div class="px-4 py-4 space-y-3">
            <a href="#modules" class="block px-3 py-2 rounded-lg text-sm font-medium text-[#6B7280] dark:text-gray-400 hover:bg-[#E5E7EB] dark:hover:bg-gray-800"><?= _l('landing_nav_modules') ?></a>
            <a href="#features" class="block px-3 py-2 rounded-lg text-sm font-medium text-[#6B7280] dark:text-gray-400 hover:bg-[#E5E7EB] dark:hover:bg-gray-800"><?= _l('landing_nav_features') ?></a>
            <a href="#advantages" class="block px-3 py-2 rounded-lg text-sm font-medium text-[#6B7280] dark:text-gray-400 hover:bg-[#E5E7EB] dark:hover:bg-gray-800"><?= _l('landing_nav_advantages') ?></a>
            <a href="#pricing" class="block px-3 py-2 rounded-lg text-sm font-medium text-[#6B7280] dark:text-gray-400 hover:bg-[#E5E7EB] dark:hover:bg-gray-800"><?= _l('landing_nav_pricing') ?></a>
            <div class="border-t border-[#E5E7EB] dark:border-gray-800 pt-3 flex gap-2">
                <a href="<?= site_url('login') ?>" class="flex-1 text-center px-3 py-2.5 rounded-lg text-sm font-semibold text-[#1F1F1F] dark:text-white border border-[#E5E7EB] dark:border-gray-700">Client Login</a>
                <a href="<?= admin_url('authentication') ?>" class="flex-1 text-center px-3 py-2.5 rounded-lg text-sm font-semibold text-white bg-[#6B21A8]">Admin Login</a>
            </div>
        </div>
    </div>
</nav>

<!-- HERO SECTION -->
<section class="relative pt-32 lg:pt-40 pb-20 overflow-hidden">
    <div class="absolute inset-0 pointer-events-none">
        <div class="geo-bg bg-[#6B21A8] -top-40 -right-40 blur-3xl"></div>
        <div class="geo-bg bg-[#F9BC3B] -bottom-40 -left-40 blur-3xl"></div>
        <div class="geo-line top-1/4 left-0 right-0"></div>
        <div class="geo-line top-3/4 left-0 right-0"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-4xl mx-auto">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#6B21A8]/10 text-[#6B21A8] dark:bg-[#6B21A8]/20 dark:text-[#C084FC] text-sm font-semibold mb-8 animate-fade-in-up">
                <i class="ti ti-sparkles"></i>
                <span><?= _l('landing_hero_badge') ?></span>
            </div>

            <!-- Title -->
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-[#1F1F1F] dark:text-white leading-tight mb-6 animate-fade-in-up delay-100">
                <?= _l('landing_hero_title') ?>
            </h1>

            <!-- Subtitle -->
            <p class="text-lg sm:text-xl text-[#6B7280] dark:text-gray-400 max-w-2xl mx-auto mb-10 animate-fade-in-up delay-200">
                <?= _l('landing_hero_subtitle') ?>
            </p>

            <!-- CTAs -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16 animate-fade-in-up delay-300">
                <a href="#modules" class="w-full sm:w-auto px-8 py-3.5 rounded-lg text-base font-semibold text-white bg-[#6B21A8] hover:bg-[#581C87] transition-all shadow-xl shadow-purple-500/20 hover:shadow-purple-500/30 flex items-center justify-center gap-2">
                    <?= _l('landing_hero_cta_modules') ?>
                    <i class="ti ti-arrow-right"></i>
                </a>
                <a href="#features" class="w-full sm:w-auto px-8 py-3.5 rounded-lg text-base font-semibold text-[#1F1F1F] dark:text-white border-2 border-[#E5E7EB] dark:border-gray-700 hover:border-[#6B21A8] dark:hover:border-[#A855F7] transition-all flex items-center justify-center gap-2">
                    <i class="ti ti-player-play"></i>
                    <?= _l('landing_hero_cta_demo') ?>
                </a>
            </div>
        </div>

        <!-- Dashboard Mockup with Compare Slider -->
        <div class="relative max-w-5xl mx-auto animate-fade-in-up delay-300">
            <div class="compare-container rounded-xl shadow-2xl border border-[#E5E7EB] dark:border-gray-800 bg-[#F7F7F9] mx-auto w-full" id="compareContainer" style="aspect-ratio: 1920/903">
                <picture>
                    <source srcset="<?= base_url('assets/images/landing/light.webp') ?>" type="image/webp">
                    <img src="<?= base_url('assets/images/landing/light.png') ?>" alt="Fennec Dash Light Mode" width="1920" height="903" class="w-full h-full object-cover block" id="lightImage" fetchpriority="high">
                </picture>
                <picture>
                    <source srcset="<?= base_url('assets/images/landing/dark.webp') ?>" type="image/webp">
                    <img src="<?= base_url('assets/images/landing/dark.png') ?>" alt="Fennec Dash Dark Mode" width="1908" height="899" class="w-full h-full object-cover absolute top-0 left-0 z-10" id="darkImage" style="clip-path: inset(0 50% 0 0);">
                </picture>
                <div class="compare-slider" id="compareSlider">
                    <div class="compare-handle">
                        <i class="ti ti-arrows-left-right text-sm font-bold"></i>
                    </div>
                </div>
                <div class="absolute top-4 left-4 px-3 py-1 rounded bg-[#F9BC3B] text-[#1F1F1F] text-xs font-bold z-20 pointer-events-none"><?= _l('landing_theme_dark') ?></div>
                <div class="absolute top-4 right-4 px-3 py-1 rounded bg-[#6B21A8] text-white text-xs font-bold z-20 pointer-events-none"><?= _l('landing_theme_light') ?></div>
            </div>
        </div>
    </div>
</section>

<!-- STATS BANNER -->
<section class="py-12 border-y border-[#E5E7EB] dark:border-gray-800 bg-white/50 dark:bg-[#0F0F12]/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="text-3xl lg:text-2xl font-bold text-[#6B21A8] mb-1"><?= _l('landing_stat_companies') ?></div>
                <div class="text-sm text-[#6B7280] dark:text-gray-400 font-medium"><?= _l('landing_stat_companies_label') ?></div>
            </div>
            <div class="text-center">
                <div class="text-3xl lg:text-2xl font-bold text-[#6B21A8] mb-1"><?= _l('landing_stat_modules') ?></div>
                <div class="text-sm text-[#6B7280] dark:text-gray-400 font-medium"><?= _l('landing_stat_modules_label') ?></div>
            </div>
            <div class="text-center">
                <div class="text-3xl lg:text-2xl font-bold text-[#6B21A8] mb-1"><?= _l('landing_stat_uptime') ?></div>
                <div class="text-sm text-[#6B7280] dark:text-gray-400 font-medium"><?= _l('landing_stat_uptime_label') ?></div>
            </div>
            <div class="text-center">
                <div class="text-3xl lg:text-2xl font-bold text-[#6B21A8] mb-1"><?= _l('landing_stat_support') ?></div>
                <div class="text-sm text-[#6B7280] dark:text-gray-400 font-medium"><?= _l('landing_stat_support_label') ?></div>
            </div>
        </div>
    </div>
</section>

<!-- TRUST BAR -->
<section class="py-10 border-b border-[#E5E7EB] dark:border-gray-800 bg-white dark:bg-[#0F0F12]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap justify-center items-center gap-8 md:gap-16">
            <div class="flex items-center gap-3 text-sm font-semibold text-[#6B7280] dark:text-gray-400">
                <div class="w-9 h-9 rounded-lg bg-[#F9BC3B]/10 flex items-center justify-center text-[#F9BC3B]">
                    <i class="ti ti-code"></i>
                </div>
                <span><?= _l('landing_trust_oss') ?> — <?= _l('landing_trust_oss_desc') ?></span>
            </div>
            <div class="flex items-center gap-3 text-sm font-semibold text-[#6B7280] dark:text-gray-400">
                <div class="w-9 h-9 rounded-lg bg-[#F9BC3B]/10 flex items-center justify-center text-[#F9BC3B]">
                    <i class="ti ti-server"></i>
                </div>
                <span><?= _l('landing_trust_hosted') ?> — <?= _l('landing_trust_hosted_desc') ?></span>
            </div>
            <div class="flex items-center gap-3 text-sm font-semibold text-[#6B7280] dark:text-gray-400">
                <div class="w-9 h-9 rounded-lg bg-[#F9BC3B]/10 flex items-center justify-center text-[#F9BC3B]">
                    <i class="ti ti-wallet"></i>
                </div>
                <span><?= _l('landing_trust_nofees') ?> — <?= _l('landing_trust_nofees_desc') ?></span>
            </div>
        </div>
    </div>
</section>

<!-- APPS & MODULES SECTION -->
<section id="modules" class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl lg:text-4xl font-bold text-[#1F1F1F] dark:text-white mb-4"><?= _l('landing_modules_title') ?></h2>
            <p class="text-lg text-[#6B7280] dark:text-gray-400 max-w-2xl mx-auto"><?= _l('landing_modules_subtitle') ?></p>
        </div>

        <div class="grid lg:grid-cols-5 gap-8 items-start">
            <!-- Tab Group -->
            <div class="lg:col-span-2 space-y-3">
                <button class="module-tab active w-full text-left p-5 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:shadow-md transition-all" data-tab="finance">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center flex-shrink-0">
                            <i class="ti ti-currency-euro text-2xl text-[#6B21A8] dark:text-[#A855F7]"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-[#1F1F1F] dark:text-white mb-1"><?= _l('landing_tab_finance') ?></h3>
                            <p class="text-sm text-[#6B7280] dark:text-gray-400"><?= _l('landing_module_purchase') ?>, <?= _l('landing_module_einvoice') ?>, <?= _l('landing_module_accounting') ?>, <?= _l('landing_module_exports') ?>, <?= _l('landing_module_goals') ?></p>
                        </div>
                    </div>
                </button>
                <button class="module-tab w-full text-left p-5 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:shadow-md transition-all" data-tab="operations">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0">
                            <i class="ti ti-package text-2xl text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-[#1F1F1F] dark:text-white mb-1"><?= _l('landing_tab_operations') ?></h3>
                            <p class="text-sm text-[#6B7280] dark:text-gray-400"><?= _l('landing_module_logistic') ?>, <?= _l('landing_module_manufacturing') ?>, <?= _l('landing_module_warehouse') ?>, <?= _l('landing_module_warranty') ?></p>
                        </div>
                    </div>
                </button>
                <button class="module-tab w-full text-left p-5 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:shadow-md transition-all" data-tab="people">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center flex-shrink-0">
                            <i class="ti ti-users text-2xl text-green-600 dark:text-green-400"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-[#1F1F1F] dark:text-white mb-1"><?= _l('landing_tab_people') ?></h3>
                            <p class="text-sm text-[#6B7280] dark:text-gray-400"><?= _l('landing_module_hr') ?>, <?= _l('landing_module_surveys') ?>, <?= _l('landing_module_quick_share') ?>, <?= _l('landing_module_report_builder') ?></p>
                        </div>
                    </div>
                </button>
                <button class="module-tab w-full text-left p-5 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:shadow-md transition-all" data-tab="system">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-lg bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center flex-shrink-0">
                            <i class="ti ti-settings text-2xl text-orange-600 dark:text-orange-400"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-[#1F1F1F] dark:text-white mb-1"><?= _l('landing_tab_system') ?></h3>
                            <p class="text-sm text-[#6B7280] dark:text-gray-400"><?= _l('landing_module_backup') ?>, <?= _l('landing_module_menu_setup') ?>, <?= _l('landing_module_theme_style') ?>, <?= _l('landing_module_openai') ?></p>
                        </div>
                    </div>
                </button>
                <button class="module-tab w-full text-left p-5 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:shadow-md transition-all" data-tab="growth">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-lg bg-teal-50 dark:bg-teal-900/20 flex items-center justify-center flex-shrink-0">
                            <i class="ti ti-trending-up text-2xl text-teal-600 dark:text-teal-400"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-[#1F1F1F] dark:text-white mb-1"><?= _l('landing_tab_growth') ?></h3>
                            <p class="text-sm text-[#6B7280] dark:text-gray-400"><?= _l('landing_module_real_estate') ?>, <?= _l('landing_module_meta_leads') ?>, <?= _l('landing_module_fleet') ?>, <?= _l('landing_module_marketing') ?></p>
                        </div>
                    </div>
                </button>
            </div>

            <!-- Dynamic Workspace -->
            <div class="lg:col-span-3 relative">
                <div class="rounded-lg overflow-hidden border border-[#E5E7EB] dark:border-gray-800 shadow-xl bg-white dark:bg-[#1E1E2D]">
                    <div id="moduleContent" class="p-6 lg:p-8">
                        <!-- Rendered by JS on load (default: finance) -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- LIGHT VS DARK SLIDER SECTION -->
<section class="py-20 lg:py-28 bg-white dark:bg-[#0F0F12]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
            <div class="lg:pr-8 lg:max-w-xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#F9BC3B]/10 text-[#F9BC3B] text-xs font-bold uppercase tracking-wider mb-4">
                    <i class="ti ti-adjustments"></i>
                    <?= _l('landing_theme_badge') ?>
                </div>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#1F1F1F] dark:text-white mb-4"><?= _l('landing_theme_title') ?></h2>
                <p class="text-lg text-[#6B7280] dark:text-gray-400 mb-8"><?= _l('landing_theme_desc') ?></p>
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-[#6B21A8]/10 flex items-center justify-center flex-shrink-0">
                            <i class="ti ti-sun text-[#6B21A8] dark:text-[#A855F7]"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-[#1F1F1F] dark:text-white"><?= _l('landing_theme_light_title') ?></div>
                            <div class="text-sm text-[#6B7280] dark:text-gray-400"><?= _l('landing_theme_light_desc') ?></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-[#1E1E2D] flex items-center justify-center flex-shrink-0">
                            <i class="ti ti-moon text-[#A855F7]"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-[#1F1F1F] dark:text-white"><?= _l('landing_theme_dark_title') ?></div>
                            <div class="text-sm text-[#6B7280] dark:text-gray-400"><?= _l('landing_theme_dark_desc') ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative">
                <div class="compare-container rounded-xl shadow-2xl border border-[#E5E7EB] dark:border-gray-800 bg-[#F7F7F9] mx-auto w-full" id="compareContainer2" style="aspect-ratio: 1920/903">
                    <picture>
                        <source srcset="<?= base_url('assets/images/landing/light.webp') ?>" type="image/webp">
                        <img src="<?= base_url('assets/images/landing/light.png') ?>" alt="Fennec Dash Light Mode" width="1920" height="903" class="w-full h-full object-cover block" id="lightImage2" loading="lazy">
                    </picture>
                    <picture>
                        <source srcset="<?= base_url('assets/images/landing/dark.webp') ?>" type="image/webp">
                        <img src="<?= base_url('assets/images/landing/dark.png') ?>" alt="Fennec Dash Dark Mode" width="1908" height="899" class="w-full h-full object-cover absolute top-0 left-0 z-10" id="darkImage2" style="clip-path: inset(0 50% 0 0);" loading="lazy">
                    </picture>
                    <div class="compare-slider" id="compareSlider2">
                        <div class="compare-handle">
                            <i class="ti ti-arrows-left-right text-sm font-bold"></i>
                        </div>
                    </div>
                    <div class="absolute top-4 left-4 px-3 py-1 rounded bg-[#F9BC3B] text-[#1F1F1F] text-xs font-bold z-20 pointer-events-none"><?= _l('landing_theme_dark') ?></div>
                    <div class="absolute top-4 right-4 px-3 py-1 rounded bg-[#6B21A8] text-white text-xs font-bold z-20 pointer-events-none"><?= _l('landing_theme_light') ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CORE FEATURES GRID -->
<section id="features" class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl lg:text-4xl font-bold text-[#1F1F1F] dark:text-white mb-4"><?= _l('landing_features_section_title') ?></h2>
            <p class="text-lg text-[#6B7280] dark:text-gray-400 max-w-2xl mx-auto"><?= _l('landing_features_section_subtitle') ?></p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800">
                <div class="w-12 h-12 rounded-lg bg-[#F9BC3B]/10 flex items-center justify-center mb-4">
                    <i class="ti ti-adjustments text-2xl text-[#F9BC3B]"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_feature_customization') ?></h3>
                <p class="text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_feature_customization_desc') ?></p>
            </div>
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800">
                <div class="w-12 h-12 rounded-lg bg-[#F9BC3B]/10 flex items-center justify-center mb-4">
                    <i class="ti ti-bolt text-2xl text-[#F9BC3B]"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_feature_speed') ?></h3>
                <p class="text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_feature_speed_desc') ?></p>
            </div>
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800">
                <div class="w-12 h-12 rounded-lg bg-[#F9BC3B]/10 flex items-center justify-center mb-4">
                    <i class="ti ti-device-mobile text-2xl text-[#F9BC3B]"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_feature_mobile') ?></h3>
                <p class="text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_feature_mobile_desc') ?></p>
            </div>
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800">
                <div class="w-12 h-12 rounded-lg bg-[#F9BC3B]/10 flex items-center justify-center mb-4">
                    <i class="ti ti-shield-lock text-2xl text-[#F9BC3B]"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_feature_security') ?></h3>
                <p class="text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_feature_security_desc') ?></p>
            </div>
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800">
                <div class="w-12 h-12 rounded-lg bg-[#F9BC3B]/10 flex items-center justify-center mb-4">
                    <i class="ti ti-file-spreadsheet text-2xl text-[#F9BC3B]"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_feature_import') ?></h3>
                <p class="text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_feature_import_desc') ?></p>
            </div>
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800">
                <div class="w-12 h-12 rounded-lg bg-[#F9BC3B]/10 flex items-center justify-center mb-4">
                    <i class="ti ti-brain text-2xl text-[#F9BC3B]"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_feature_ai') ?></h3>
                <p class="text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_feature_ai_desc') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- ADVANTAGES -->
<section id="advantages" class="py-20 lg:py-28 bg-white dark:bg-[#0F0F12] reveal">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal">
            <h2 class="text-3xl lg:text-4xl font-bold text-[#1F1F1F] dark:text-white mb-4"><?= _l('landing_advantages_title') ?></h2>
            <p class="text-lg text-[#6B7280] dark:text-gray-400 max-w-2xl mx-auto"><?= _l('landing_advantages_subtitle') ?></p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 text-center reveal" style="transition-delay:0ms">
                <div class="w-14 h-14 rounded-full bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center mx-auto mb-4">
                    <i class="ti ti-rocket text-2xl text-[#6B21A8] dark:text-[#A855F7]"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_adv_fast_title') ?></h3>
                <p class="text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_adv_fast_desc') ?></p>
            </div>
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 text-center reveal" style="transition-delay:100ms">
                <div class="w-14 h-14 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mx-auto mb-4">
                    <i class="ti ti-shield-lock text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_adv_control_title') ?></h3>
                <p class="text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_adv_control_desc') ?></p>
            </div>
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 text-center reveal" style="transition-delay:200ms">
                <div class="w-14 h-14 rounded-full bg-green-50 dark:bg-green-900/20 flex items-center justify-center mx-auto mb-4">
                    <i class="ti ti-layers-intersect text-2xl text-green-600 dark:text-green-400"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_adv_integrated_title') ?></h3>
                <p class="text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_adv_integrated_desc') ?></p>
            </div>
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 text-center reveal" style="transition-delay:300ms">
                <div class="w-14 h-14 rounded-full bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center mx-auto mb-4">
                    <i class="ti ti-map-pin text-2xl text-amber-600 dark:text-amber-400"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_adv_local_title') ?></h3>
                <p class="text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_adv_local_desc') ?></p>
            </div>
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 text-center reveal" style="transition-delay:400ms">
                <div class="w-14 h-14 rounded-full bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center mx-auto mb-4">
                    <i class="ti ti-refresh text-2xl text-orange-600 dark:text-orange-400"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_adv_updates_title') ?></h3>
                <p class="text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_adv_updates_desc') ?></p>
            </div>
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 text-center reveal" style="transition-delay:500ms">
                <div class="w-14 h-14 rounded-full bg-teal-50 dark:bg-teal-900/20 flex items-center justify-center mx-auto mb-4">
                    <i class="ti ti-headset text-2xl text-teal-600 dark:text-teal-400"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_adv_support_title') ?></h3>
                <p class="text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_adv_support_desc') ?></p>
            </div>
        </div>
        <div class="text-center mt-10">
            <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-gradient-to-r from-purple-50 to-amber-50 dark:from-purple-900/20 dark:to-amber-900/20 border border-purple-100 dark:border-purple-900/30">
                <i class="ti ti-sparkles text-[#6B21A8] dark:text-[#A855F7]"></i>
                <span class="text-sm font-medium text-[#6B21A8] dark:text-[#A855F7]"><?= _l('landing_adv_tagline') ?></span>
            </div>
        </div>
    </div>
</section>

<!-- COMPLIANCE & STANDARDS -->
<section id="compliance" class="py-20 lg:py-28 bg-white dark:bg-[#0F0F12] reveal">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#6B21A8]/10 dark:bg-[#6B21A8]/20 text-[#6B21A8] dark:text-[#A855F7] text-xs font-bold uppercase tracking-wider mb-4">
                <i class="ti ti-shield-check"></i>
                <?= _l('landing_compliance_badge') ?>
            </div>
            <h2 class="text-3xl lg:text-4xl font-bold text-[#1F1F1F] dark:text-white mb-4"><?= _l('landing_compliance_title') ?></h2>
            <p class="text-lg text-[#6B7280] dark:text-gray-400 max-w-4xl mx-auto"><?= _l('landing_compliance_subtitle') ?></p>
        </div>
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Pillar 1 -->
            <div class="feature-card p-8 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 relative overflow-hidden reveal" style="transition-delay:0ms">
                <div class="absolute top-0 right-0 w-32 h-32 bg-purple-50 dark:bg-purple-900/10 rounded-bl-full -z-0"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 rounded-xl bg-[#6B21A8]/10 dark:bg-[#6B21A8]/20 flex items-center justify-center mb-5">
                        <i class="ti ti-shield-lock text-2xl text-[#6B21A8] dark:text-[#A855F7]"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-4"><?= _l('landing_compliance_pillar1_title') ?></h3>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-sm text-[#6B7280] dark:text-gray-400">
                            <i class="ti ti-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
                            <?= _l('landing_compliance_pillar1_1') ?>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-[#6B7280] dark:text-gray-400">
                            <i class="ti ti-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
                            <?= _l('landing_compliance_pillar1_2') ?>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-[#6B7280] dark:text-gray-400">
                            <i class="ti ti-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
                            <?= _l('landing_compliance_pillar1_3') ?>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Pillar 2: ISO -->
            <div class="feature-card p-8 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 relative overflow-hidden reveal" style="transition-delay:150ms">
                <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 dark:bg-blue-900/10 rounded-bl-full -z-0"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-5">
                        <i class="ti ti-certificate text-2xl text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-4"><?= _l('landing_compliance_pillar2_title') ?></h3>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-sm text-[#6B7280] dark:text-gray-400">
                            <i class="ti ti-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
                            <?= _l('landing_compliance_pillar2_1') ?>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-[#6B7280] dark:text-gray-400">
                            <i class="ti ti-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
                            <?= _l('landing_compliance_pillar2_2') ?>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-[#6B7280] dark:text-gray-400">
                            <i class="ti ti-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
                            <?= _l('landing_compliance_pillar2_3') ?>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Pillar 3: Copyright -->
            <div class="feature-card p-8 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 relative overflow-hidden reveal" style="transition-delay:300ms">
                <div class="absolute top-0 right-0 w-32 h-32 bg-amber-50 dark:bg-amber-900/10 rounded-bl-full -z-0"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center mb-5">
                        <i class="ti ti-copyright text-2xl text-amber-600 dark:text-amber-400"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white mb-4"><?= _l('landing_compliance_pillar3_title') ?></h3>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-sm text-[#6B7280] dark:text-gray-400">
                            <i class="ti ti-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
                            <?= _l('landing_compliance_pillar3_1') ?>
                        </li>
                        <li class="flex items-start gap-3 text-sm text-[#6B7280] dark:text-gray-400">
                            <i class="ti ti-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
                            <?= _l('landing_compliance_pillar3_2') ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="text-center mt-12">
            <div class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-gradient-to-r from-[#6B21A8]/10 to-[#F9BC3B]/10 border border-[#6B21A8]/20 dark:border-[#6B21A8]/30">
                <i class="ti ti-flag-filled text-[#F9BC3B]"></i>
                <span class="text-sm font-medium text-[#1F1F1F] dark:text-white"><?= _l('landing_compliance_footer') ?></span>
            </div>
        </div>
    </div>
</section>

<!-- ALL MODULES GRID -->
<section id="all-modules" class="py-20 lg:py-28 bg-white dark:bg-[#0F0F12] reveal">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#6B21A8]/10 dark:bg-[#6B21A8]/20 text-[#6B21A8] dark:text-[#A855F7] text-xs font-bold uppercase tracking-wider mb-4">
                <i class="ti ti-apps"></i>
                <?= _l('landing_modules_title') ?>
            </div>
            <h2 class="text-3xl lg:text-4xl font-bold text-[#1F1F1F] dark:text-white mb-4"><?= _l('landing_all_modules_title') ?></h2>
            <p class="text-lg text-[#6B7280] dark:text-gray-400 max-w-2xl mx-auto"><?= _l('landing_all_modules_subtitle') ?></p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 max-w-5xl mx-auto" id="moduleGrid">
            <!-- Purchase -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-purple-300 dark:hover:border-purple-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-shopping-cart text-xl text-[#6B21A8] dark:text-[#A855F7]"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_purchase') ?></h4>
            </div>
            <!-- E-Invoice -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-purple-300 dark:hover:border-purple-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-file-invoice text-xl text-[#6B21A8] dark:text-[#A855F7]"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_einvoice') ?></h4>
            </div>
            <!-- Accounting -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-purple-300 dark:hover:border-purple-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-calculator text-xl text-[#6B21A8] dark:text-[#A855F7]"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_accounting') ?></h4>
            </div>
            <!-- Exports -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-purple-300 dark:hover:border-purple-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-file-export text-xl text-[#6B21A8] dark:text-[#A855F7]"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_exports') ?></h4>
            </div>
            <!-- Goals -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-purple-300 dark:hover:border-purple-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-trending-up text-xl text-[#6B21A8] dark:text-[#A855F7]"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_goals') ?></h4>
            </div>
            <!-- Logistic -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-blue-300 dark:hover:border-blue-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-truck text-xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_logistic') ?></h4>
            </div>
            <!-- Manufacturing -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-blue-300 dark:hover:border-blue-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-settings-cog text-xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_manufacturing') ?></h4>
            </div>
            <!-- Warehouse -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-blue-300 dark:hover:border-blue-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-building-warehouse text-xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_warehouse') ?></h4>
            </div>
            <!-- Warranty -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-blue-300 dark:hover:border-blue-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-shield-check text-xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_warranty') ?></h4>
            </div>
            <!-- HR Profile -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-green-300 dark:hover:border-green-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-users text-xl text-green-600 dark:text-green-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_hr') ?></h4>
            </div>
            <!-- Surveys -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-green-300 dark:hover:border-green-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-chart-bar text-xl text-green-600 dark:text-green-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_surveys') ?></h4>
            </div>
            <!-- Quick Share -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-green-300 dark:hover:border-green-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-share text-xl text-green-600 dark:text-green-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_quick_share') ?></h4>
            </div>
            <!-- Report Builder -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-green-300 dark:hover:border-green-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-report text-xl text-green-600 dark:text-green-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_report_builder') ?></h4>
            </div>
            <!-- Backup -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-orange-300 dark:hover:border-orange-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-cloud-upload text-xl text-orange-600 dark:text-orange-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_backup') ?></h4>
            </div>
            <!-- Menu Setup -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-orange-300 dark:hover:border-orange-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-menu-2 text-xl text-orange-600 dark:text-orange-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_menu_setup') ?></h4>
            </div>
            <!-- Theme Style -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-orange-300 dark:hover:border-orange-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-adjustments text-xl text-orange-600 dark:text-orange-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_theme_style') ?></h4>
            </div>
            <!-- OpenAI -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-orange-300 dark:hover:border-orange-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-brain text-xl text-orange-600 dark:text-orange-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_openai') ?></h4>
            </div>
            <!-- Real Estate -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-teal-300 dark:hover:border-teal-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-teal-50 dark:bg-teal-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-building text-xl text-teal-600 dark:text-teal-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_real_estate') ?></h4>
            </div>
            <!-- Meta Leads -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-teal-300 dark:hover:border-teal-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-teal-50 dark:bg-teal-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-ad text-xl text-teal-600 dark:text-teal-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_meta_leads') ?></h4>
            </div>
            <!-- Fleet Management -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-teal-300 dark:hover:border-teal-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-teal-50 dark:bg-teal-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-truck-delivery text-xl text-teal-600 dark:text-teal-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_fleet') ?></h4>
            </div>
            <!-- Marketing Management -->
            <div class="group p-4 rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 hover:border-teal-300 dark:hover:border-teal-700 transition-all cursor-default">
                <div class="w-10 h-10 rounded-lg bg-teal-50 dark:bg-teal-900/20 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="ti ti-speakerphone text-xl text-teal-600 dark:text-teal-400"></i>
                </div>
                <h4 class="font-semibold text-sm text-[#1F1F1F] dark:text-white"><?= _l('landing_module_marketing') ?></h4>
            </div>
        </div>
        <div class="text-center mt-10">
            <p class="text-sm text-[#6B7280] dark:text-gray-400"><?= sprintf(_l('landing_stats_modules'), 21) ?></p>
        </div>
    </div>
</section>

<!-- PRICING SECTION -->
<section id="pricing" class="py-20 lg:py-28 bg-white dark:bg-[#0F0F12]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 reveal">
            <h2 class="text-3xl lg:text-4xl font-bold text-[#1F1F1F] dark:text-white mb-4"><?= _l('landing_pricing_section_title') ?></h2>
            <p class="text-lg text-[#6B7280] dark:text-gray-400 max-w-2xl mx-auto mb-4"><?= _l('landing_pricing_section_subtitle') ?></p>
            <div class="flex items-center justify-center gap-4 mb-4">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#6B21A8]/10 border border-[#6B21A8]/20 dark:bg-[#6B21A8]/20 dark:border-[#6B21A8]/30">
                    <i class="ti ti-shield-check text-[#6B21A8] dark:text-[#A855F7]"></i>
                    <span class="text-sm font-medium text-[#6B21A8] dark:text-[#A855F7]"><?= _l('landing_pricing_brand') ?></span>
                </div>
                <div class="inline-flex items-center rounded-lg border border-[#E5E7EB] dark:border-gray-700 overflow-hidden text-sm">
                    <button id="currency-dzd" class="px-3 py-1.5 font-medium bg-[#6B21A8] text-white transition-colors" onclick="setCurrency('dzd')">DA</button>
                    <button id="currency-eur" class="px-3 py-1.5 font-medium text-[#6B7280] dark:text-gray-400 hover:bg-[#F3F4F6] dark:hover:bg-gray-800 transition-colors" onclick="setCurrency('eur')">€</button>
                    <button id="currency-usd" class="px-3 py-1.5 font-medium text-[#6B7280] dark:text-gray-400 hover:bg-[#F3F4F6] dark:hover:bg-gray-800 transition-colors" onclick="setCurrency('usd')">$</button>
                </div>
            </div>
        </div>
        <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            <!-- Standard -->
            <div class="rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 p-6 lg:p-8 relative overflow-hidden reveal" style="transition-delay:0ms">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-400 to-purple-600"></div>
                <div class="mb-6 pt-1">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center flex-shrink-0">
                            <i class="ti ti-building-store text-xl text-[#6B21A8] dark:text-[#A855F7]"></i>
                        </div>
                        <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white"><?= _l('landing_pricing_standard_name') ?></h3>
                    </div>
                    <p class="text-sm text-[#6B7280] dark:text-gray-400 ml-[3.25rem]"><?= _l('landing_pricing_standard_desc') ?></p>
                </div>
                <div class="mb-6 flex items-baseline gap-1">
                    <span class="text-2xl font-bold text-[#1F1F1F] dark:text-white" id="price-standard" data-dzd="550000"><?= _l('landing_pricing_standard_price') ?></span>
                    <span class="text-[#6B7280] dark:text-gray-400 text-sm" id="currency-label-standard">DA</span>
                    <span class="text-[#6B7280] dark:text-gray-400 text-sm">/<?= _l('landing_pricing_license') ?></span>
                </div>
                <a href="https://wa.me/213773955598" target="_blank" rel="noopener" class="block w-full py-3 rounded-lg font-semibold text-center text-[#6B21A8] border-2 border-[#6B21A8] hover:bg-[#6B21A8] hover:text-white transition-all"><?= _l('landing_pricing_standard_cta') ?></a>
            </div>
            <!-- Professional -->
            <div class="pricing-highlight rounded-lg bg-white dark:bg-[#1E1E2D] p-6 lg:p-8 relative shadow-xl reveal" style="transition-delay:100ms">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 z-10">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-[#F9BC3B] text-[#1F1F1F] text-xs font-bold uppercase tracking-wider animate-pulse"><?= _l('landing_pricing_popular') ?></span>
                </div>
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-400 to-amber-600"></div>
                <div class="mb-6 pt-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center flex-shrink-0">
                            <i class="ti ti-crown text-xl text-amber-600 dark:text-amber-400"></i>
                        </div>
                        <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white"><?= _l('landing_pricing_pro_name') ?></h3>
                    </div>
                    <p class="text-sm text-[#6B7280] dark:text-gray-400 ml-[3.25rem]"><?= _l('landing_pricing_pro_desc') ?></p>
                </div>
                <div class="mb-6 flex items-baseline gap-1">
                    <span class="text-2xl font-bold text-[#1F1F1F] dark:text-white" id="price-pro" data-dzd="1650000"><?= _l('landing_pricing_pro_price') ?></span>
                    <span class="text-[#6B7280] dark:text-gray-400 text-sm" id="currency-label-pro">DA</span>
                    <span class="text-[#6B7280] dark:text-gray-400 text-sm">/<?= _l('landing_pricing_license') ?></span>
                </div>
                <a href="https://wa.me/213773955598" target="_blank" rel="noopener" class="block w-full py-3 rounded-lg font-semibold text-center text-white bg-gradient-to-r from-[#6B21A8] to-[#7C3AED] hover:from-[#581C87] hover:to-[#6D28D9] transition-all shadow-lg shadow-purple-500/20 hover:shadow-purple-500/30"><?= _l('landing_pricing_pro_cta') ?></a>
            </div>
            <!-- Enterprise -->
            <div class="rounded-lg bg-[#F7F7F9] dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 p-6 lg:p-8 relative overflow-hidden reveal" style="transition-delay:200ms">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-400 to-blue-600"></div>
                <div class="mb-6 pt-1">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0">
                            <i class="ti ti-building-skyscraper text-xl text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <h3 class="text-lg font-bold text-[#1F1F1F] dark:text-white"><?= _l('landing_pricing_enterprise_name') ?></h3>
                    </div>
                    <p class="text-sm text-[#6B7280] dark:text-gray-400 ml-[3.25rem]"><?= _l('landing_pricing_enterprise_desc') ?></p>
                </div>
                <div class="mb-6 flex items-baseline gap-1">
                    <span class="text-2xl font-bold text-[#1F1F1F] dark:text-white" id="price-enterprise" data-dzd="2450000"><?= _l('landing_pricing_enterprise_price') ?></span>
                    <span class="text-[#6B7280] dark:text-gray-400 text-sm" id="currency-label-enterprise">DA</span>
                    <span class="text-[#6B7280] dark:text-gray-400 text-sm">/<?= _l('landing_pricing_license') ?></span>
                </div>
                <a href="https://wa.me/213773955598" target="_blank" rel="noopener" class="block w-full py-3 rounded-lg font-semibold text-center text-[#6B21A8] border-2 border-[#6B21A8] hover:bg-[#6B21A8] hover:text-white transition-all"><?= _l('landing_pricing_enterprise_cta') ?></a>
            </div>
        </div>
    </div>
</section>

<!-- FAQ SECTION -->
<section class="py-20 lg:py-28 bg-white dark:bg-[#0F0F12] reveal">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 reveal">
            <h2 class="text-3xl lg:text-4xl font-bold text-[#1F1F1F] dark:text-white mb-4"><?= _l('landing_faq_title') ?></h2>
            <p class="text-lg text-[#6B7280] dark:text-gray-400"><?= _l('landing_faq_subtitle') ?></p>
        </div>
        <div class="space-y-4">
            <div class="faq-item rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 overflow-hidden">
                <button class="faq-trigger w-full flex items-center justify-between p-5 text-left">
                    <span class="font-semibold text-[#1F1F1F] dark:text-white"><?= _l('landing_faq_q1') ?></span>
                    <i class="ti ti-chevron-down faq-icon text-[#6B7280] dark:text-gray-400 flex-shrink-0"></i>
                </button>
                <div class="faq-content px-5">
                    <p class="pb-5 text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_faq_a1') ?></p>
                </div>
            </div>
            <div class="faq-item rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 overflow-hidden">
                <button class="faq-trigger w-full flex items-center justify-between p-5 text-left">
                    <span class="font-semibold text-[#1F1F1F] dark:text-white"><?= _l('landing_faq_q2') ?></span>
                    <i class="ti ti-chevron-down faq-icon text-[#6B7280] dark:text-gray-400 flex-shrink-0"></i>
                </button>
                <div class="faq-content px-5">
                    <p class="pb-5 text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_faq_a2') ?></p>
                </div>
            </div>
            <div class="faq-item rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 overflow-hidden">
                <button class="faq-trigger w-full flex items-center justify-between p-5 text-left">
                    <span class="font-semibold text-[#1F1F1F] dark:text-white"><?= _l('landing_faq_q3') ?></span>
                    <i class="ti ti-chevron-down faq-icon text-[#6B7280] dark:text-gray-400 flex-shrink-0"></i>
                </button>
                <div class="faq-content px-5">
                    <p class="pb-5 text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_faq_a3') ?></p>
                </div>
            </div>
            <div class="faq-item rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 overflow-hidden">
                <button class="faq-trigger w-full flex items-center justify-between p-5 text-left">
                    <span class="font-semibold text-[#1F1F1F] dark:text-white"><?= _l('landing_faq_q4') ?></span>
                    <i class="ti ti-chevron-down faq-icon text-[#6B7280] dark:text-gray-400 flex-shrink-0"></i>
                </button>
                <div class="faq-content px-5">
                    <p class="pb-5 text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_faq_a4') ?></p>
                </div>
            </div>
            <div class="faq-item rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 overflow-hidden">
                <button class="faq-trigger w-full flex items-center justify-between p-5 text-left">
                    <span class="font-semibold text-[#1F1F1F] dark:text-white"><?= _l('landing_faq_q5') ?></span>
                    <i class="ti ti-chevron-down faq-icon text-[#6B7280] dark:text-gray-400 flex-shrink-0"></i>
                </button>
                <div class="faq-content px-5">
                    <p class="pb-5 text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed"><?= _l('landing_faq_a5') ?></p>
                </div>
            </div>
        </div>
        <div class="mt-12 pt-8 border-t border-[#E5E7EB] dark:border-gray-800">
            <div class="text-center mb-8">
                <h3 class="text-xl font-bold text-[#1F1F1F] dark:text-white"><?= _l('landing_docs_title') ?></h3>
                <p class="text-[#6B7280] dark:text-gray-400 text-sm mt-1"><?= _l('landing_faq_docs_subtitle') ?></p>
            </div>
            <div class="space-y-4">
                <div class="faq-item rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 overflow-hidden">
                    <button class="faq-trigger w-full flex items-center justify-between p-5 text-left">
                        <span class="font-semibold text-[#1F1F1F] dark:text-white"><?= _l('landing_faq_docs_q1') ?></span>
                        <i class="ti ti-chevron-down faq-icon text-[#6B7280] dark:text-gray-400 flex-shrink-0"></i>
                    </button>
                    <div class="faq-content px-5">
                        <div class="pb-5 text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed whitespace-pre-line"><?= _l('landing_faq_docs_a1') ?></div>
                    </div>
                </div>
                <div class="faq-item rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 overflow-hidden">
                    <button class="faq-trigger w-full flex items-center justify-between p-5 text-left">
                        <span class="font-semibold text-[#1F1F1F] dark:text-white"><?= _l('landing_faq_docs_q2') ?></span>
                        <i class="ti ti-chevron-down faq-icon text-[#6B7280] dark:text-gray-400 flex-shrink-0"></i>
                    </button>
                    <div class="faq-content px-5">
                        <div class="pb-5 text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed whitespace-pre-line"><?= _l('landing_faq_docs_a2') ?></div>
                    </div>
                </div>
                <div class="faq-item rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 overflow-hidden">
                    <button class="faq-trigger w-full flex items-center justify-between p-5 text-left">
                        <span class="font-semibold text-[#1F1F1F] dark:text-white"><?= _l('landing_faq_docs_q3') ?></span>
                        <i class="ti ti-chevron-down faq-icon text-[#6B7280] dark:text-gray-400 flex-shrink-0"></i>
                    </button>
                    <div class="faq-content px-5">
                        <div class="pb-5 text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed whitespace-pre-line"><?= _l('landing_faq_docs_a3') ?></div>
                    </div>
                </div>
                <div class="faq-item rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 overflow-hidden">
                    <button class="faq-trigger w-full flex items-center justify-between p-5 text-left">
                        <span class="font-semibold text-[#1F1F1F] dark:text-white"><?= _l('landing_faq_docs_q4') ?></span>
                        <i class="ti ti-chevron-down faq-icon text-[#6B7280] dark:text-gray-400 flex-shrink-0"></i>
                    </button>
                    <div class="faq-content px-5">
                        <div class="pb-5 text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed whitespace-pre-line"><?= _l('landing_faq_docs_a4') ?></div>
                    </div>
                </div>
                <div class="faq-item rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 overflow-hidden">
                    <button class="faq-trigger w-full flex items-center justify-between p-5 text-left">
                        <span class="font-semibold text-[#1F1F1F] dark:text-white"><?= _l('landing_faq_docs_q5') ?></span>
                        <i class="ti ti-chevron-down faq-icon text-[#6B7280] dark:text-gray-400 flex-shrink-0"></i>
                    </button>
                    <div class="faq-content px-5">
                        <div class="pb-5 text-[#6B7280] dark:text-gray-400 text-sm leading-relaxed whitespace-pre-line"><?= _l('landing_faq_docs_a5') ?></div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-6">
                <a href="<?= site_url('knowledge-base') ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-[#6B21A8] dark:text-[#A855F7] hover:underline">
                    <?= _l('landing_docs_cta') ?>
                    <i class="ti ti-arrow-right text-xs"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- DOCUMENTATION SECTION -->
<section id="docs" class="py-20 lg:py-28 bg-white dark:bg-[#0F0F12] reveal">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#6B21A8]/10 dark:bg-[#6B21A8]/20 text-[#6B21A8] dark:text-[#A855F7] text-xs font-bold uppercase tracking-wider mb-4">
                <i class="ti ti-book-2"></i>
                <?= _l('landing_docs_title') ?>
            </div>
            <h2 class="text-3xl lg:text-4xl font-bold text-[#1F1F1F] dark:text-white mb-4"><?= _l('landing_docs_title') ?></h2>
            <p class="text-lg text-[#6B7280] dark:text-gray-400 max-w-2xl mx-auto"><?= _l('landing_docs_subtitle') ?></p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-5xl mx-auto">
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 text-center">
                <div class="w-14 h-14 rounded-full bg-[#6B21A8]/10 dark:bg-[#6B21A8]/20 flex items-center justify-center mx-auto mb-4">
                    <i class="ti ti-compass text-2xl text-[#6B21A8] dark:text-[#A855F7]"></i>
                </div>
                <h3 class="font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_docs_feature_guide') ?></h3>
                <p class="text-sm text-[#6B7280] dark:text-gray-400 leading-relaxed"><?= _l('landing_docs_feature_guide_desc') ?></p>
            </div>
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 text-center">
                <div class="w-14 h-14 rounded-full bg-[#6B21A8]/10 dark:bg-[#6B21A8]/20 flex items-center justify-center mx-auto mb-4">
                    <i class="ti ti-template text-2xl text-[#6B21A8] dark:text-[#A855F7]"></i>
                </div>
                <h3 class="font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_docs_feature_modules') ?></h3>
                <p class="text-sm text-[#6B7280] dark:text-gray-400 leading-relaxed"><?= _l('landing_docs_feature_modules_desc') ?></p>
            </div>
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 text-center">
                <div class="w-14 h-14 rounded-full bg-[#6B21A8]/10 dark:bg-[#6B21A8]/20 flex items-center justify-center mx-auto mb-4">
                    <i class="ti ti-api text-2xl text-[#6B21A8] dark:text-[#A855F7]"></i>
                </div>
                <h3 class="font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_docs_feature_api') ?></h3>
                <p class="text-sm text-[#6B7280] dark:text-gray-400 leading-relaxed"><?= _l('landing_docs_feature_api_desc') ?></p>
            </div>
            <div class="feature-card p-6 rounded-lg bg-white dark:bg-[#1E1E2D] border border-[#E5E7EB] dark:border-gray-800 text-center">
                <div class="w-14 h-14 rounded-full bg-[#6B21A8]/10 dark:bg-[#6B21A8]/20 flex items-center justify-center mx-auto mb-4">
                    <i class="ti ti-video text-2xl text-[#6B21A8] dark:text-[#A855F7]"></i>
                </div>
                <h3 class="font-bold text-[#1F1F1F] dark:text-white mb-2"><?= _l('landing_docs_feature_videos') ?></h3>
                <p class="text-sm text-[#6B7280] dark:text-gray-400 leading-relaxed"><?= _l('landing_docs_feature_videos_desc') ?></p>
            </div>
        </div>
        <div class="text-center mt-10">
            <a href="<?= site_url('knowledge-base') ?>" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold text-white bg-[#6B21A8] hover:bg-[#581C87] transition-all shadow-lg shadow-purple-500/20">
                <?= _l('landing_docs_cta') ?>
                <i class="ti ti-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- FREE PERSONALIZED THEME -->
<section class="py-20 lg:py-28 bg-white dark:bg-[#0F0F12] reveal">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
            <div class="reveal" style="transition-delay:0ms">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#F9BC3B]/10 text-[#F9BC3B] text-xs font-bold uppercase tracking-wider mb-4">
                    <i class="ti ti-adjustments"></i>
                    <?= _l('landing_theme_free_title') ?>
                </div>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#1F1F1F] dark:text-white mb-4"><?= _l('landing_theme_free_title') ?></h2>
                <p class="text-lg text-[#6B7280] dark:text-gray-400 mb-8"><?= _l('landing_theme_free_subtitle') ?></p>
                <ul class="space-y-3 mb-8">
                    <li class="flex items-center gap-3 text-[#1F1F1F] dark:text-white"><i class="ti ti-check-circle text-green-500"></i><?= _l('landing_theme_free_feature1') ?></li>
                    <li class="flex items-center gap-3 text-[#1F1F1F] dark:text-white"><i class="ti ti-check-circle text-green-500"></i><?= _l('landing_theme_free_feature2') ?></li>
                    <li class="flex items-center gap-3 text-[#1F1F1F] dark:text-white"><i class="ti ti-check-circle text-green-500"></i><?= _l('landing_theme_free_feature3') ?></li>
                    <li class="flex items-center gap-3 text-[#1F1F1F] dark:text-white"><i class="ti ti-check-circle text-green-500"></i><?= _l('landing_theme_free_feature4') ?></li>
                </ul>
                <a href="mailto:support@fennec-vision.com" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold text-white bg-[#6B21A8] hover:bg-[#581C87] transition-all shadow-lg shadow-purple-500/20">
                    <?= _l('landing_theme_free_cta') ?>
                    <i class="ti ti-arrow-right"></i>
                </a>
            </div>
            <div class="relative">
                <div class="rounded-xl overflow-hidden shadow-2xl border border-[#E5E7EB] dark:border-gray-800">
                    <div class="bg-gradient-to-br from-[#6B21A8] to-[#D97706] p-8 text-center">
                        <div class="w-16 h-16 rounded-xl bg-white/20 flex items-center justify-center mx-auto mb-4">
                            <i class="ti ti-adjustments text-3xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2"><?= _l('landing_theme_free_title') ?></h3>
                        <div class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white/20 text-white text-xs font-bold">
                            <i class="ti ti-sparkles"></i>
                            <?= _l('landing_pricing_note') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-20 lg:py-28 bg-gradient-to-br from-[#6B21A8] via-[#7C3AED] to-[#D97706] text-center">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl lg:text-4xl font-bold text-white mb-4"><?= _l('landing_cta_title') ?></h2>
        <p class="text-lg text-white/80 max-w-xl mx-auto mb-8"><?= _l('landing_cta_subtitle') ?></p>
        <a href="<?= site_url('register') ?>" class="inline-flex items-center px-8 py-3.5 rounded-lg text-base font-bold text-[#1F1F1F] bg-[#F9BC3B] hover:bg-[#FCD34D] transition-all shadow-xl shadow-black/20">
            <?= _l('landing_cta_button') ?>
            <i class="ti ti-arrow-right ml-2"></i>
        </a>
    </div>
</section>

<!-- FOOTER -->
<footer class="bg-[#1F1F1F] dark:bg-[#050508] text-white pt-16 pb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
            <!-- Brand -->
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <img src="<?= base_url('assets/images/landing/logo-light.png') ?>" alt="<?= _l('landing_brand') ?>" class="h-10 w-auto">
                </div>
                <span class="text-xl font-bold">Fennec Vision</span>
                <p class="text-gray-400 text-sm leading-relaxed mb-4">VOTRE PARTENAIRE DIGITAL À 360°</p>
                <div class="flex flex-wrap gap-3 mb-4">
                    <a href="https://web.facebook.com/VisionFennec" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center hover:bg-[#6B21A8] transition-colors"><i class="ti ti-brand-facebook"></i></a>
                    <a href="https://www.fennec-vision.com/" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center hover:bg-[#6B21A8] transition-colors"><i class="ti ti-world"></i></a>
                    <a href="https://www.instagram.com/fennec_vision/" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center hover:bg-[#6B21A8] transition-colors"><i class="ti ti-brand-instagram"></i></a>
                    <a href="https://www.linkedin.com/company/fennec-vision/" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center hover:bg-[#6B21A8] transition-colors"><i class="ti ti-brand-linkedin"></i></a>
                    <a href="https://www.tiktok.com/@fennec.vision" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center hover:bg-[#6B21A8] transition-colors"><i class="ti ti-brand-tiktok"></i></a>
                </div>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="tel:+213779355598" class="hover:text-white transition-colors">+213 779 355 598</a></li>
                    <li><a href="mailto:support@fennec-vision.com" class="hover:text-white transition-colors">support@fennec-vision.com</a></li>
                </ul>
            </div>
            <!-- Modules -->
            <div>
                <h4 class="font-semibold mb-4 text-white"><?= _l('landing_footer_modules') ?></h4>
                <ul class="space-y-3">
                    <li><a href="#modules" class="text-gray-400 hover:text-white text-sm transition-colors"><?= _l('landing_module_accounting') ?></a></li>
                    <li><a href="#modules" class="text-gray-400 hover:text-white text-sm transition-colors"><?= _l('landing_module_logistic') ?></a></li>
                    <li><a href="#modules" class="text-gray-400 hover:text-white text-sm transition-colors"><?= _l('landing_module_warehouse') ?></a></li>
                    <li><a href="#modules" class="text-gray-400 hover:text-white text-sm transition-colors"><?= _l('landing_module_manufacturing') ?></a></li>
                    <li><a href="#modules" class="text-gray-400 hover:text-white text-sm transition-colors"><?= _l('landing_module_hr') ?></a></li>
                </ul>
            </div>
            <!-- Company -->
            <div>
                <h4 class="font-semibold mb-4 text-white"><?= _l('landing_footer_company') ?></h4>
                <ul class="space-y-3">
                    <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors"><?= _l('landing_footer_about') ?></a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors"><?= _l('landing_footer_blog') ?></a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors"><?= _l('landing_footer_contact') ?></a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors"><?= _l('landing_footer_support') ?></a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors"><?= _l('landing_footer_docs') ?></a></li>
                </ul>
            </div>
            <!-- Legal -->
            <div>
                <h4 class="font-semibold mb-4 text-white"><?= _l('landing_footer_resources') ?></h4>
                <ul class="space-y-3">
                    <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors"><?= _l('landing_lang_en') ?></a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors"><?= _l('landing_lang_fr') ?></a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors" lang="ar"><?= _l('landing_lang_ar') ?></a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-gray-500 text-sm"><?= sprintf(_l('landing_footer_copyright'), date('Y')) ?> — <a href="https://www.fennec-vision.com" target="_blank" rel="noopener" class="hover:text-white transition-colors">Fennec Vision</a></p>
            <div class="flex items-center gap-2 text-gray-500 text-sm">
                <span><?= _l('landing_brand') ?> v3.0</span>
            </div>
        </div>
    </div>
</footer>

<!-- Scroll to top -->
<button class="fixed bottom-6 right-6 w-11 h-11 rounded-full bg-[#F9BC3B] text-[#1F1F1F] border-none cursor-pointer flex items-center justify-center text-lg opacity-0 invisible translate-y-2 transition-all z-[999] shadow-lg shadow-[#F9BC3B]/30 hover:bg-[#FCD34D] hover:-translate-y-1" id="scrollTop" aria-label="<?= _l('landing_scroll_top') ?>">
    <i class="ti ti-chevron-up"></i>
</button>

<script>
// ==================== MODULE DATA ====================
const moduleData = <?= json_encode([
    'finance' => [
        'badge' => _l('landing_tab_finance'),
        'badgeColor' => 'bg-purple-50 dark:bg-purple-900/20 text-[#6B21A8] dark:text-[#A855F7]',
        'title' => _l('landing_tab_finance_title'),
        'desc' => _l('landing_tab_finance_desc'),
        'modules' => [
            ['icon' => 'ti-calculator', 'name' => _l('landing_module_accounting'), 'desc' => _l('landing_module_accounting_desc')],
            ['icon' => 'ti-file-invoice', 'name' => _l('landing_module_einvoice'), 'desc' => _l('landing_module_einvoice_desc')],
            ['icon' => 'ti-shopping-cart', 'name' => _l('landing_module_purchase'), 'desc' => _l('landing_module_purchase_desc')],
            ['icon' => 'ti-file-export', 'name' => _l('landing_module_exports'), 'desc' => _l('landing_module_exports_desc')],
            ['icon' => 'ti-target', 'name' => _l('landing_module_goals'), 'desc' => _l('landing_module_goals_desc')],
        ],
        'mockup' => 'purchase',
    ],
    'operations' => [
        'badge' => _l('landing_tab_operations'),
        'badgeColor' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
        'title' => _l('landing_tab_operations_title'),
        'desc' => _l('landing_tab_operations_desc'),
        'modules' => [
            ['icon' => 'ti-truck-delivery', 'name' => _l('landing_module_logistic'), 'desc' => _l('landing_module_logistic_desc')],
            ['icon' => 'ti-building-factory', 'name' => _l('landing_module_manufacturing'), 'desc' => _l('landing_module_manufacturing_desc')],
            ['icon' => 'ti-building-warehouse', 'name' => _l('landing_module_warehouse'), 'desc' => _l('landing_module_warehouse_desc')],
            ['icon' => 'ti-shield-check', 'name' => _l('landing_module_warranty'), 'desc' => _l('landing_module_warranty_desc')],
        ],
        'mockup' => 'logistics',
    ],
    'people' => [
        'badge' => _l('landing_tab_people'),
        'badgeColor' => 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400',
        'title' => _l('landing_tab_people_title'),
        'desc' => _l('landing_tab_people_desc'),
        'modules' => [
            ['icon' => 'ti-users', 'name' => _l('landing_module_hr'), 'desc' => _l('landing_module_hr_desc')],
            ['icon' => 'ti-clipboard-check', 'name' => _l('landing_module_surveys'), 'desc' => _l('landing_module_surveys_desc')],
            ['icon' => 'ti-share', 'name' => _l('landing_module_quick_share'), 'desc' => _l('landing_module_quick_share_desc')],
            ['icon' => 'ti-report', 'name' => _l('landing_module_report_builder'), 'desc' => _l('landing_module_report_builder_desc')],
        ],
        'mockup' => 'hr',
    ],
    'system' => [
        'badge' => _l('landing_tab_system'),
        'badgeColor' => 'bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400',
        'title' => _l('landing_tab_system_title'),
        'desc' => _l('landing_tab_system_desc'),
        'modules' => [
            ['icon' => 'ti-database', 'name' => _l('landing_module_backup'), 'desc' => _l('landing_module_backup_desc')],
            ['icon' => 'ti-menu-2', 'name' => _l('landing_module_menu_setup'), 'desc' => _l('landing_module_menu_setup_desc')],
            ['icon' => 'ti-adjustments', 'name' => _l('landing_module_theme_style'), 'desc' => _l('landing_module_theme_style_desc')],
            ['icon' => 'ti-brain', 'name' => _l('landing_module_openai'), 'desc' => _l('landing_module_openai_desc')],
        ],
        'mockup' => 'system',
    ],
    'growth' => [
        'badge' => _l('landing_tab_growth'),
        'badgeColor' => 'bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400',
        'title' => _l('landing_tab_growth_title'),
        'desc' => _l('landing_tab_growth_desc'),
        'modules' => [
            ['icon' => 'ti-building', 'name' => _l('landing_module_real_estate'), 'desc' => _l('landing_module_real_estate_desc')],
            ['icon' => 'ti-ad', 'name' => _l('landing_module_meta_leads'), 'desc' => _l('landing_module_meta_leads_desc')],
            ['icon' => 'ti-truck-delivery', 'name' => _l('landing_module_fleet'), 'desc' => _l('landing_module_fleet_desc')],
            ['icon' => 'ti-speakerphone', 'name' => _l('landing_module_marketing'), 'desc' => _l('landing_module_marketing_desc')],
        ],
        'mockup' => 'growth',
    ],
]) ?>;

function getModuleListHTML(modules) {
    return modules.map(function(m) {
        return '<div class="module-list-item">' +
            '<div class="w-8 h-8 rounded bg-[#6B21A8]/10 dark:bg-[#6B21A8]/20 flex items-center justify-center flex-shrink-0">' +
            '<i class="ti ' + m.icon + ' text-[#6B21A8] dark:text-[#A855F7] text-sm"></i></div>' +
            '<div class="flex-1 min-w-0">' +
            '<div class="flex justify-between items-center">' +
            '<span class="text-sm font-semibold text-[#1F1F1F] dark:text-white">' + m.name + '</span></div>' +
            '<p class="text-xs text-[#6B7280] dark:text-gray-400 truncate">' + m.desc + '</p></div></div>';
    }).join('');
}

function getMockupHTML(type) {
    var headers = { purchase: ['PO Number', 'Vendor', 'Status'], logistics: ['Reference', 'Warehouse', 'Stock'], hr: ['Employee', 'Department', 'Status'], system: ['Task', 'Module', 'State'], growth: ['Campaign', 'Leads', 'Status'] };
    var h = headers[type] || headers.purchase;
    var rows = [
        { ref: type === 'purchase' ? 'PO-2025-00001' : type === 'logistics' ? 'REF-78432' : type === 'hr' ? 'EMP-001' : type === 'growth' ? 'CAMP-001' : 'TSK-101', name: type === 'purchase' ? "Carriere d'Argile" : type === 'logistics' ? 'Warehouse A' : type === 'hr' ? 'John Doe' : type === 'growth' ? 'Summer Sale 2025' : 'Database Backup', status: 'Active', sc: 'blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' },
        { ref: type === 'purchase' ? 'PO-2025-00002' : type === 'logistics' ? 'REF-78433' : type === 'hr' ? 'EMP-002' : type === 'growth' ? 'CAMP-002' : 'TSK-102', name: type === 'purchase' ? 'Emballages SPA' : type === 'logistics' ? 'Warehouse B' : type === 'hr' ? 'Jane Smith' : type === 'growth' ? 'Q2 Real Estate' : 'Theme Update', status: 'Done', sc: 'green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' },
        { ref: type === 'purchase' ? 'PO-2025-00003' : type === 'logistics' ? 'REF-78434' : type === 'hr' ? 'EMP-003' : type === 'growth' ? 'CAMP-003' : 'TSK-103', name: type === 'purchase' ? 'Fournitures Ind.' : type === 'logistics' ? 'Warehouse C' : type === 'hr' ? 'Bob Wilson' : type === 'growth' ? 'Meta Leads Oct' : 'AI Training', status: 'Pending', sc: 'yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' }
    ];
    var rowHtml = rows.map(function(r) {
        return '<div class="flex gap-2 text-[9px] text-[#1F1F1F] dark:text-gray-300 items-center">' +
            '<span class="w-24 font-mono">' + r.ref + '</span>' +
            '<span class="flex-1 truncate">' + r.name + '</span>' +
            '<span class="w-16 px-1 py-0.5 rounded bg-' + r.sc + ' text-[8px]">' + r.status + '</span></div>';
    }).join('');
    return '<div class="rounded-lg border border-[#E5E7EB] dark:border-gray-700 overflow-hidden">' +
        '<div class="bg-[#6B21A8] px-4 py-2 flex items-center justify-between">' +
        '<div class="flex items-center gap-2 text-white text-[10px] font-bold uppercase tracking-wider">' +
        '<i class="ti ti-table"></i> ' + (type === 'purchase' ? 'Purchase Orders' : type === 'logistics' ? 'Stock Overview' : type === 'hr' ? 'HR Directory' : type === 'growth' ? 'Marketing Campaigns' : 'System Tasks') + '</div>' +
        '<div class="flex gap-1"><div class="w-2 h-2 rounded-full bg-white/30"></div><div class="w-2 h-2 rounded-full bg-white/30"></div></div></div>' +
        '<div class="bg-[#F9FAFB] dark:bg-[#161618] p-3 space-y-2">' +
        '<div class="flex gap-2 text-[9px] font-bold text-[#6B7280] dark:text-gray-400 uppercase tracking-wider border-b border-[#E5E7EB] dark:border-gray-700 pb-1">' +
        '<span class="w-24">' + h[0] + '</span><span class="flex-1">' + h[1] + '</span><span class="w-16">' + h[2] + '</span></div>' +
        rowHtml + '</div></div>';
}

function updateModuleContent(data) {
    var el = document.getElementById('moduleContent');
    el.innerHTML = '<div class="mb-6"><div class="flex items-center gap-3 mb-3">' +
        '<span class="px-3 py-1 rounded-full ' + data.badgeColor + ' text-xs font-bold uppercase tracking-wider">' + data.badge + '</span></div>' +
        '<h3 class="text-2xl font-bold text-[#1F1F1F] dark:text-white mb-2">' + data.title + '</h3>' +
        '<p class="text-[#6B7280] dark:text-gray-400">' + data.desc + '</p></div>' +
        '<div class="space-y-0 mb-6">' + getModuleListHTML(data.modules) + '</div>' +
        getMockupHTML(data.mockup);
}

document.querySelectorAll('.module-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.module-tab').forEach(function(t) { t.classList.remove('active'); });
        tab.classList.add('active');
        var el = document.getElementById('moduleContent');
        el.style.opacity = '0';
        el.style.transform = 'translateY(10px)';
        el.style.transition = 'all 0.3s ease';
        setTimeout(function() {
            updateModuleContent(moduleData[tab.dataset.tab]);
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, 200);
    });
});

// Initialize first tab
updateModuleContent(moduleData.finance);

// ==================== COMPARE SLIDER ====================
function setupCompare(containerId, sliderId, darkId) {
    var container = document.getElementById(containerId);
    var slider = document.getElementById(sliderId);
    var dark = document.getElementById(darkId);
    if (!container) return;
    var isDragging = false;

    function update(x) {
        var rect = container.getBoundingClientRect();
        var pct = Math.max(0, Math.min(100, ((x - rect.left) / rect.width) * 100));
        dark.style.clipPath = 'inset(0 ' + (100 - pct) + '% 0 0)';
        slider.style.left = pct + '%';
    }

    function onMove(e) { if (!isDragging) return; update(e.touches ? e.touches[0].clientX : e.clientX); }
    function onStart(e) { isDragging = true; if (e.touches) update(e.touches[0].clientX); else update(e.clientX); }
    function onEnd() { isDragging = false; }

    container.addEventListener('mousedown', onStart);
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onEnd);
    container.addEventListener('touchstart', onStart, { passive: true });
    document.addEventListener('touchmove', onMove, { passive: true });
    document.addEventListener('touchend', onEnd);
    container.addEventListener('click', function(e) { update(e.clientX); });

    function rect() { return container.getBoundingClientRect(); }
    if (dark.complete) update(rect().left + rect().width / 2);
}

setupCompare('compareContainer', 'compareSlider', 'darkImage');
setupCompare('compareContainer2', 'compareSlider2', 'darkImage2');

// ==================== THEME ====================
(function() {
    var toggle = document.getElementById('themeToggle');
    var html = document.documentElement;
    var saved = localStorage.getItem('landing-theme') || 'light';
    var icon = document.getElementById('themeIcon');

    if (saved === 'system') saved = 'light';

    function updateThemeIcon(t) {
        icon.className = t === 'light' ? 'ti ti-sun text-lg' : 'ti ti-moon text-lg text-[#A855F7]';
    }

    function apply(t) {
        html.classList.toggle('dark', t === 'dark');
        updateThemeIcon(t);
    }
    apply(saved);

    var currentTheme = saved;

    toggle.addEventListener('click', function() {
        var next = currentTheme === 'light' ? 'dark' : 'light';
        currentTheme = next;
        localStorage.setItem('landing-theme', next);
        apply(next);
    });

    // Navbar scroll
    var navbar = document.getElementById('navbar');
    window.addEventListener('scroll', function() {
        navbar.classList.toggle('shadow-sm', window.scrollY > 50);
    });

    // Scroll to top
    var scrollTop = document.getElementById('scrollTop');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 400) {
            scrollTop.classList.add('opacity-100', 'visible');
            scrollTop.classList.remove('opacity-0', 'invisible');
        } else {
            scrollTop.classList.remove('opacity-100', 'visible');
            scrollTop.classList.add('opacity-0', 'invisible');
        }
    });
})();

// ==================== MOBILE MENU ====================
(function() {
    var btn = document.getElementById('mobileMenuBtn');
    var menu = document.getElementById('mobileMenu');
    btn.addEventListener('click', function() { menu.classList.toggle('hidden'); });
    menu.querySelectorAll('a').forEach(function(a) {
        a.addEventListener('click', function() { menu.classList.add('hidden'); });
    });
})();

// ==================== FAQ ACCORDION ====================
document.querySelectorAll('.faq-item').forEach(function(item) {
    var trigger = item.querySelector('.faq-trigger');
    var content = item.querySelector('.faq-content');
    trigger.addEventListener('click', function() {
        document.querySelectorAll('.faq-item').forEach(function(other) {
            if (other !== item) { other.classList.remove('open'); other.querySelector('.faq-content').style.maxHeight = '0'; }
        });
        if (item.classList.contains('open')) {
            item.classList.remove('open');
            content.style.maxHeight = '0';
        } else {
            item.classList.add('open');
            content.style.maxHeight = content.scrollHeight + 'px';
        }
    });
});

// ==================== SCROLL ANIMATIONS ====================
(function() {
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) { entry.target.style.opacity = '1'; entry.target.style.transform = 'translateY(0)'; }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.reveal').forEach(function(el, i) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(24px)';
        var d = el.getAttribute('style') || '';
        var match = d.match(/transition-delay:(\d+)ms/);
        var delay = match ? parseInt(match[1]) : i * 100;
        el.style.transition = 'all 0.7s cubic-bezier(0.4, 0, 0.2, 1) ' + delay + 'ms';
        observer.observe(el);
    });
    document.querySelectorAll('.feature-card:not(.reveal)').forEach(function(card, i) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.6s ease ' + (i * 0.1) + 's';
        observer.observe(card);
    });
    document.querySelectorAll('#pricing .grid > div').forEach(function(card, i) {
        card.style.opacity = '0'; card.style.transform = 'translateY(20px)'; card.style.transition = 'all 0.6s ease ' + (i * 0.15) + 's'; observer.observe(card);
    });
    document.querySelectorAll('#all-modules .grid > div').forEach(function(card, i) {
        card.style.opacity = '0'; card.style.transform = 'translateY(20px)'; card.style.transition = 'all 0.6s ease ' + (i * 0.05) + 's'; observer.observe(card);
    });
    document.querySelectorAll('#docs .grid > div').forEach(function(card, i) {
        card.style.opacity = '0'; card.style.transform = 'translateY(20px)'; card.style.transition = 'all 0.6s ease ' + (i * 0.1) + 's'; observer.observe(card);
    });
})();

// ==================== SMOOTH SCROLL ====================
document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (target) { e.preventDefault(); window.scrollTo({ top: target.getBoundingClientRect().top + window.pageYOffset - 80, behavior: 'smooth' }); }
    });
});

// ==================== LANG DROPDOWN ====================
(function() {
    var btn = document.getElementById('langBtn');
    var dd = document.getElementById('langDropdown');
    if (btn && dd) {
        btn.addEventListener('click', function(e) { e.stopPropagation(); dd.classList.toggle('opacity-0'); dd.classList.toggle('invisible'); dd.classList.toggle('opacity-100'); dd.classList.toggle('visible'); });
        document.addEventListener('click', function() { dd.classList.add('opacity-0'); dd.classList.add('invisible'); dd.classList.remove('opacity-100'); dd.classList.remove('visible'); });
        dd.addEventListener('click', function(e) { e.stopPropagation(); });
    }
})();

// ==================== CURRENCY TOGGLE ====================
var currentCurrency = 'dzd';
function setCurrency(currency) {
    currentCurrency = currency;
    var prices = [
        { id: 'standard', dzd: 550000 },
        { id: 'pro', dzd: 1650000 },
        { id: 'enterprise', dzd: 2450000 }
    ];
    var rates = { eur: 275, usd: 240 };
    var symbols = { dzd: { sym: 'DA', label: 'DA' }, eur: { sym: '€', label: '€' }, usd: { sym: '$', label: '$' } };
    ['dzd', 'eur', 'usd'].forEach(function(c) {
        var btn = document.getElementById('currency-' + c);
        if (c === currency) {
            btn.className = 'px-3 py-1.5 font-medium bg-[#6B21A8] text-white transition-colors';
        } else {
            btn.className = 'px-3 py-1.5 font-medium text-[#6B7280] dark:text-gray-400 hover:bg-[#F3F4F6] dark:hover:bg-gray-800 transition-colors';
        }
    });
    prices.forEach(function(p) {
        var el = document.getElementById('price-' + p.id);
        var label = document.getElementById('currency-label-' + p.id);
        if (currency === 'dzd') {
            el.textContent = Number(p.dzd).toLocaleString() + '.00 DA';
            label.textContent = 'DA';
        } else {
            var val = Math.round(p.dzd / rates[currency]);
            el.textContent = Number(val).toLocaleString() + ' ' + symbols[currency].sym;
            label.textContent = symbols[currency].label;
        }
    });
}
</script>
</body>
</html>
