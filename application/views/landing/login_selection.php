<!DOCTYPE html>
<html lang="<?= $lang_code ?>" dir="<?= $dir ?>" class="" style="overflow-x: hidden; max-width: 100vw;">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= _l('landing_login_select_subtitle') ?>">
<meta property="og:title" content="<?= _l('landing_login_select_title') ?> - <?= _l('landing_brand') ?>">
<meta property="og:description" content="<?= _l('landing_login_select_subtitle') ?>">
<meta property="og:type" content="website">
<meta property="og:image" content="https://dash.fennec-vision.com/assets/images/landing/og-image.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:type" content="image/png">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= _l('landing_login_select_title') ?> - <?= _l('landing_brand') ?>">
<meta name="twitter:description" content="<?= _l('landing_login_select_subtitle') ?>">
<meta name="twitter:image" content="https://dash.fennec-vision.com/assets/images/landing/og-image.png">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#6B21A8" id="themeColorMeta">
<script>
// Apply the saved theme before first paint to avoid a flash.
(function () {
  try {
    if (localStorage.getItem('landing-theme') === 'dark') {
      document.documentElement.classList.add('dark');
      var meta = document.getElementById('themeColorMeta');
      if (meta) meta.setAttribute('content', '#0F0F12');
    }
  } catch (e) {}
})();
</script>
<link rel="icon" type="image/png" href="<?= base_url('uploads/company/favicon.png') ?>">
<title><?= _l('landing_login_select_title') ?> - <?= _l('landing_brand') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/tabler/tabler-icons.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/builds/landing-tailwind.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/landing.css') ?>">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "WebSite",
      "@id": "https://dash.fennec-vision.com/#website",
      "url": "https://dash.fennec-vision.com/",
      "name": "Fennec Dash - Login Selection",
      "description": "Choose your account type to sign in to Fennec Dash",
      "publisher": { "@type": "Organization", "@id": "https://dash.fennec-vision.com/#organization" },
      "potentialAction": {
        "@type": "SearchAction",
        "target": { "@type": "EntryPoint", "urlTemplate": "https://dash.fennec-vision.com/?q={search_term_string}" },
        "query-input": "required name=search_term_string"
      }
    },
    {
      "@type": "Organization",
      "@id": "https://dash.fennec-vision.com/#organization",
      "name": "Fennec Vision",
      "legalName": "Fennec Vision",
      "url": "https://dash.fennec-vision.com/",
      "logo": "https://dash.fennec-vision.com/assets/images/landing/logo-light.png",
      "description": "Publisher of Fennec Dash - an all-in-one CRM & ERP business platform with 21 integrated modules."
    }
  ]
}
</script>
</head>
<body class="login-selection-page" style="overflow-x: hidden; max-width: 100vw;">
<a href="#main-content" class="skip-link">Skip to main content</a>

<div class="login-selection-container">
  <header class="login-selection-header">
    <div class="header-content">
      <a href="<?= site_url() ?>" class="logo" aria-label="<?= _l('landing_brand') ?>">
        <?php
        // logo-dark.png = dark wordmark for light mode; logo-light.png = light wordmark for dark mode.
        $has_light_logo = file_exists(FCPATH . 'assets/images/landing/logo-dark.png');
        $has_dark_logo  = file_exists(FCPATH . 'assets/images/landing/logo-light.png');
        ?>
        <?php if ($has_light_logo || $has_dark_logo): ?>
          <?php if ($has_light_logo): ?>
            <img src="<?= base_url('assets/images/landing/logo-dark.png') ?>" alt="<?= _l('landing_brand') ?>"
                 class="logo-img logo-img--light<?= $has_dark_logo ? '' : ' logo-img--only' ?>" width="331" height="97">
          <?php endif; ?>
          <?php if ($has_dark_logo): ?>
            <img src="<?= base_url('assets/images/landing/logo-light.png') ?>" alt="<?= _l('landing_brand') ?>"
                 class="logo-img logo-img--dark<?= $has_light_logo ? '' : ' logo-img--only' ?>" width="331" height="97">
          <?php endif; ?>
        <?php else: ?>
          <span class="logo-text"><?= _l('landing_brand') ?></span>
        <?php endif; ?>
      </a>
      <div class="header-actions">
        <div class="lang-switcher" role="group" aria-label="Language selection">
          <i class="ti ti-world lang-switcher-icon" aria-hidden="true"></i>
          <a href="<?= site_url('?lang=en') ?>" class="lang-btn <?= $lang_code === 'en' ? 'active' : '' ?>" aria-label="English" hreflang="en">EN</a>
          <a href="<?= site_url('?lang=fr') ?>" class="lang-btn <?= $lang_code === 'fr' ? 'active' : '' ?>" aria-label="Français" hreflang="fr">FR</a>
          <a href="<?= site_url('?lang=ar') ?>" class="lang-btn <?= $lang_code === 'ar' ? 'active' : '' ?>" aria-label="العربية" hreflang="ar">AR</a>
        </div>
        <button type="button" class="theme-toggle" id="themeToggle"
                aria-label="<?= _l('landing_theme_switch_dark') ?>"
                title="<?= _l('landing_theme_switch_dark') ?>"
                aria-pressed="false">
          <i class="ti ti-sun" id="themeIcon" aria-hidden="true"></i>
        </button>
      </div>
    </div>
  </header>

  <main id="main-content" class="login-selection-main">
    <div class="login-selection-card">
      <div class="card-header">
        <div class="brand-badge">
          <i class="ti ti-shield-lock" aria-hidden="true"></i>
          <span><?= _l('landing_brand') ?></span>
        </div>
        <h1 class="card-title"><?= _l('landing_login_select_title') ?></h1>
        <p class="card-subtitle"><?= _l('landing_login_select_subtitle') ?></p>
      </div>

      <div class="card-body">
        <div class="login-options" role="list" aria-label="Login options">
          
          <!-- Admin Login -->
          <article class="login-option" role="listitem">
            <div class="option-icon admin">
              <i class="ti ti-user-shield" aria-hidden="true"></i>
            </div>
            <div class="option-content">
              <h2 class="option-title"><?= _l('landing_login_select_admin') ?></h2>
              <p class="option-description"><?= _l('landing_login_select_admin_desc') ?></p>
            </div>
            <a href="<?= $admin_login_url ?>" class="option-cta" role="button">
              <span><?= _l('landing_login_select_cta_admin') ?></span>
              <i class="ti ti-login" aria-hidden="true"></i>
            </a>
          </article>

          <!-- Client Login -->
          <article class="login-option" role="listitem">
            <div class="option-icon client">
              <i class="ti ti-users" aria-hidden="true"></i>
            </div>
            <div class="option-content">
              <h2 class="option-title"><?= _l('landing_login_select_client') ?></h2>
              <p class="option-description"><?= _l('landing_login_select_client_desc') ?></p>
            </div>
            <a href="<?= $client_login_url ?>" class="option-cta" role="button">
              <span><?= _l('landing_login_select_cta_client') ?></span>
              <i class="ti ti-login" aria-hidden="true"></i>
            </a>
          </article>
        </div>
      </div>

      <div class="card-footer">
        <p class="powered-by">
          <i class="ti ti-shield-check" aria-hidden="true"></i>
          <span><?= _l('landing_login_select_powered_by') ?></span>
        </p>
      </div>
    </div>
  </main>

  <footer class="login-selection-footer">
    <p class="copyright">&copy; <?= date('Y') ?> <?= _l('landing_brand') ?>. All rights reserved.</p>
  </footer>
</div>

<script>
// Light / dark theme switch (shared key with the public landing page)
(function () {
  var root = document.documentElement;
  var toggle = document.getElementById('themeToggle');
  var icon = document.getElementById('themeIcon');
  var meta = document.getElementById('themeColorMeta');
  var labels = {
    dark: <?= json_encode(_l('landing_theme_switch_dark')) ?>,
    light: <?= json_encode(_l('landing_theme_switch_light')) ?>
  };

  function apply(theme) {
    var dark = theme === 'dark';
    root.classList.toggle('dark', dark);
    if (icon) icon.className = dark ? 'ti ti-moon' : 'ti ti-sun';
    if (meta) meta.setAttribute('content', dark ? '#0F0F12' : '#6B21A8');
    if (toggle) {
      toggle.setAttribute('aria-pressed', dark ? 'true' : 'false');
      toggle.setAttribute('aria-label', dark ? labels.light : labels.dark);
      toggle.setAttribute('title', dark ? labels.light : labels.dark);
    }
  }

  var saved = 'light';
  try { saved = localStorage.getItem('landing-theme') === 'dark' ? 'dark' : 'light'; } catch (e) {}
  apply(saved);

  if (toggle) {
    toggle.addEventListener('click', function () {
      var next = root.classList.contains('dark') ? 'light' : 'dark';
      try { localStorage.setItem('landing-theme', next); } catch (e) {}
      apply(next);
    });
  }
})();

// Add subtle entrance animations
document.addEventListener('DOMContentLoaded', function() {
  const options = document.querySelectorAll('.login-option');
  options.forEach(function(option, index) {
    option.style.opacity = '0';
    option.style.transform = 'translateY(20px)';
    option.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    setTimeout(function() {
      option.style.opacity = '1';
      option.style.transform = 'translateY(0)';
    }, 100 + (index * 100));
  });
});
</script>
</body>
</html>