<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
  <title>About Findex – Egypt's Jewelry Recovery Platform | Trust & AI Matching</title>
  <!-- Google Fonts + Tailwind + Font Awesome -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #fef9f5 0%, #fff5eb 100%);
      color: #1e293b;
      overflow-x: hidden;
    }
    .font-playfair { font-family: 'Playfair Display', serif; }
    .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
    .gold-gradient { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    .text-orange-gradient { background: linear-gradient(135deg, #f97316, #ea580c); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
    .text-gold-gradient { background: linear-gradient(135deg, #f59e0b, #d97706); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
    .card-soft {
      background: rgba(255,255,255,0.96);
      border: 1px solid rgba(249,115,22,0.12);
      border-radius: 28px;
      transition: all 0.25s ease;
    }
    .card-soft:hover { transform: translateY(-3px); border-color: rgba(249,115,22,0.3); box-shadow: 0 20px 30px -12px rgba(0,0,0,0.08); }
    .trust-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(249,115,22,0.08);
      border: 1px solid rgba(249,115,22,0.2);
      border-radius: 100px;
      padding: 6px 18px;
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.03em;
      color: #c2410c;
      text-transform: uppercase;
    }
    .section-divider {
      width: 60px;
      height: 3px;
      background: linear-gradient(90deg, #f97316, #f59e0b);
      border-radius: 4px;
      margin: 0 auto 1.2rem;
    }
    .fade-up { opacity: 0; transform: translateY(28px); transition: opacity 0.65s cubic-bezier(0.2, 0.9, 0.4, 1.1), transform 0.65s ease; }
    .fade-up.visible { opacity: 1; transform: translateY(0); }
    .fade-left { opacity: 0; transform: translateX(-28px); transition: opacity 0.6s ease, transform 0.6s ease; }
    .fade-left.visible { opacity: 1; transform: translateX(0); }
    .fade-right { opacity: 0; transform: translateX(28px); transition: opacity 0.6s ease, transform 0.6s ease; }
    .fade-right.visible { opacity: 1; transform: translateX(0); }
    .delay-1 { transition-delay: 0.08s; }
    .delay-2 { transition-delay: 0.16s; }
    .delay-3 { transition-delay: 0.24s; }
    .delay-4 { transition-delay: 0.32s; }
    .delay-5 { transition-delay: 0.4s; }
    .icon-circle {
      width: 48px; height: 48px; border-radius: 28px;
      background: rgba(249,115,22,0.1); display: flex; align-items: center; justify-content: center;
    }
    .icon-gold-circle {
      width: 48px; height: 48px; border-radius: 28px;
      background: rgba(245,158,11,0.12); display: flex; align-items: center; justify-content: center;
    }
    .hotline-block {
      background: linear-gradient(115deg, #f97316, #ea580c);
      border-radius: 2rem;
      padding: 12px 32px;
      font-family: 'Playfair Display', serif;
      font-size: 2rem;
      font-weight: 800;
      letter-spacing: 2px;
      color: white;
      display: inline-block;
      box-shadow: 0 12px 28px rgba(249,115,22,0.3);
    }
    @media (max-width: 640px) {
      .hotline-block { font-size: 1.6rem; padding: 8px 24px; }
    }
    .ai-chat-container, .chat-widget, [class*="chat"], [id*="chat"] { display: block; }
    .nav-link { transition: all 0.2s ease; }
  </style>
</head>
<body>


<?php

if (!isset($_SESSION)) {
    if (session_status() === PHP_SESSION_NONE) {
        $simulated_logged_in = false;
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        if ($simulated_logged_in) {
            $_SESSION['user_id'] = 1;
            $_SESSION['user_type'] = 'user';
        } else {
            if (isset($_SESSION['user_id'])) unset($_SESSION['user_id']);
            if (isset($_SESSION['user_type'])) unset($_SESSION['user_type']);
        }
    }
}
$notif_count = 0;
if (isset($_SESSION['user_id'])) {
    $notif_count = 2;
}
?>

<nav class="bg-white/95 backdrop-blur-md shadow-lg sticky top-0 z-50 border-b border-orange-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4">
        <div class="flex justify-between items-center">
            <a href="index.php" class="flex items-center space-x-3 hover:opacity-90 transition">
                <div class="orange-gradient rounded-xl w-10 h-10 flex items-center justify-center shadow-md">
                    <i class="fas fa-gem text-white text-xl"></i>
                </div>
                <div>
                    <span class="font-bold text-xl sm:text-2xl text-gray-800">Findex</span>
                    <span class="text-[10px] sm:text-xs text-gray-400 block -mt-1">مصر | Luxury Recovery</span>
                </div>
            </a>
            
            <div class="hidden md:flex items-center space-x-6 lg:space-x-8">
                <a href="index.php" class="text-gray-600 hover:text-orange-600 transition font-medium nav-link">Home</a>
                <a href="search.php" class="text-gray-600 hover:text-orange-600 transition font-medium nav-link">Search</a>
                <a href="about.php" class="text-gray-600 hover:text-orange-600 transition font-medium nav-link text-orange-600 font-semibold">About</a>
                
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])): ?>
                    <?php 
                    $dashboard_page = 'dashboard_user.php';
                    if ($_SESSION['user_type'] === 'shop') {
                        $dashboard_page = 'dashboard_shop.php';
                    } elseif ($_SESSION['user_type'] === 'admin') {
                        $dashboard_page = 'dashboard_admin.php';
                    } elseif ($_SESSION['user_type'] === 'moderator') {
                        $dashboard_page = 'dashboard_moderator.php';
                    }
                    ?>
                    <a href="<?php echo $dashboard_page; ?>" class="text-gray-600 hover:text-orange-600 transition nav-link">Dashboard</a>
                    <a href="edit_profile.php" class="text-gray-600 hover:text-orange-600 transition nav-link">Profile</a>
                    <a href="notifications.php" class="relative text-gray-600 hover:text-orange-600 transition">
                        <i class="fas fa-bell text-xl"></i>
                        <?php if ($notif_count > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1">
                                <?php echo $notif_count > 9 ? '9+' : $notif_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="report_item.php" class="orange-gradient text-white px-4 py-2 rounded-full text-sm font-semibold shadow-md hover:shadow-lg transition">
                        <i class="fas fa-plus mr-1"></i> Report
                    </a>
                    <a href="logout.php" class="text-red-500 hover:text-red-600 transition">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-gray-600 hover:text-orange-600 transition nav-link">Login</a>
                    <a href="register.php" class="orange-gradient text-white px-5 py-2 rounded-full text-sm font-semibold shadow-md hover:shadow-lg transition">Sign Up</a>
                <?php endif; ?>
            </div>
            
            <button class="mobile-menu-btn md:hidden" id="menuBtn">
                <i class="fas fa-bars text-2xl text-orange-600"></i>
            </button>
        </div>
    </div>
</nav>

<div class="menu-overlay fixed inset-0 bg-black/50 z-40 hidden" id="menuOverlay"></div>
<div class="mobile-menu fixed top-0 right-[-100%] w-4/5 max-w-sm h-full bg-white z-50 shadow-2xl transition-all duration-300 p-6 overflow-y-auto" id="mobileMenu">
    <button class="absolute top-5 right-5 text-gray-500 hover:text-orange-600" id="closeMenuBtn">
        <i class="fas fa-times text-2xl"></i>
    </button>
    <div class="flex flex-col mt-12 space-y-4">
        <a href="index.php" class="flex items-center gap-3 text-gray-700 hover:text-orange-600 transition font-medium py-3 border-b border-gray-100">
            <i class="fas fa-home w-6 text-orange-500"></i> Home
        </a>
        <a href="search.php" class="flex items-center gap-3 text-gray-700 hover:text-orange-600 transition font-medium py-3 border-b border-gray-100">
            <i class="fas fa-search w-6 text-orange-500"></i> Search Reports
        </a>
        <a href="about.php" class="flex items-center gap-3 text-gray-700 hover:text-orange-600 transition font-medium py-3 border-b border-gray-100">
            <i class="fas fa-info-circle w-6 text-orange-500"></i> About
        </a>
        
        <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])): ?>
            <?php 
            $mobile_dashboard = 'dashboard_user.php';
            if ($_SESSION['user_type'] === 'shop') {
                $mobile_dashboard = 'dashboard_shop.php';
            } elseif ($_SESSION['user_type'] === 'admin') {
                $mobile_dashboard = 'dashboard_admin.php';
            } elseif ($_SESSION['user_type'] === 'moderator') {
                $mobile_dashboard = 'dashboard_moderator.php';
            }
            ?>
            <a href="<?php echo $mobile_dashboard; ?>" class="flex items-center gap-3 text-gray-700 hover:text-orange-600 transition font-medium py-3 border-b border-gray-100">
                <i class="fas fa-tachometer-alt w-6 text-orange-500"></i> Dashboard
            </a>
            <a href="edit_profile.php" class="flex items-center gap-3 text-gray-700 hover:text-orange-600 transition font-medium py-3 border-b border-gray-100">
                <i class="fas fa-user-circle w-6 text-orange-500"></i> Profile
            </a>
            <a href="notifications.php" class="flex items-center gap-3 text-gray-700 hover:text-orange-600 transition font-medium py-3 border-b border-gray-100">
                <i class="fas fa-bell w-6 text-orange-500"></i> Notifications
                <?php if ($notif_count > 0): ?>
                    <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5 ml-auto"><?php echo $notif_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="report_item.php" class="flex items-center gap-3 text-gray-700 hover:text-orange-600 transition font-medium py-3 border-b border-gray-100">
                <i class="fas fa-plus-circle w-6 text-orange-500"></i> New Report
            </a>
            <a href="my_reports.php" class="flex items-center gap-3 text-gray-700 hover:text-orange-600 transition font-medium py-3 border-b border-gray-100">
                <i class="fas fa-file-alt w-6 text-orange-500"></i> My Reports
            </a>
            <a href="logout.php" class="flex items-center gap-3 text-red-600 hover:text-red-700 transition font-medium py-3 border-b border-gray-100">
                <i class="fas fa-sign-out-alt w-6"></i> Logout
            </a>
        <?php else: ?>
            <a href="login.php" class="flex items-center gap-3 text-gray-700 hover:text-orange-600 transition font-medium py-3 border-b border-gray-100">
                <i class="fas fa-sign-in-alt w-6 text-orange-500"></i> Login
            </a>
            <a href="register.php" class="orange-gradient text-white text-center py-3 rounded-full font-semibold mt-4">
                <i class="fas fa-user-plus mr-2"></i> Create Account
            </a>
        <?php endif; ?>
    </div>
</div>

<style>
    .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
    .mobile-menu::-webkit-scrollbar { width: 4px; }
    .mobile-menu::-webkit-scrollbar-track { background: #f1f1f1; }
    .mobile-menu::-webkit-scrollbar-thumb { background: #f97316; border-radius: 10px; }
    @media (max-width: 768px) {
        .mobile-menu-btn { display: block; }
        nav a, .nav-link { font-size: 13px; }
    }
    @media (min-width: 769px) {
        .mobile-menu-btn { display: none; }
        .mobile-menu, .menu-overlay { display: none !important; }
        nav a, .nav-link { font-size: 14px; }
    }
</style>

<script>
(function() {
    const menuBtn = document.getElementById('menuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const menuOverlay = document.getElementById('menuOverlay');
    const closeMenuBtn = document.getElementById('closeMenuBtn');
    
    if (menuBtn) {
        menuBtn.addEventListener('click', function() {
            if (mobileMenu) mobileMenu.style.right = '0';
            if (menuOverlay) menuOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
    }
    
    function closeMenu() {
        if (mobileMenu) mobileMenu.style.right = '-100%';
        if (menuOverlay) menuOverlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
    
    if (closeMenuBtn) closeMenuBtn.addEventListener('click', closeMenu);
    if (menuOverlay) menuOverlay.addEventListener('click', closeMenu);
    
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) closeMenu();
    });
    
    if (mobileMenu) {
        const links = mobileMenu.querySelectorAll('a');
        links.forEach(function(link) { link.addEventListener('click', closeMenu); });
    }
})();
</script>

<!-- =========================== HERO SECTION ============================ -->
<section class="relative overflow-hidden pt-14 pb-20 md:pt-20" style="background: radial-gradient(ellipse at 20% 40%, rgba(249,115,22,0.05), transparent 70%), #fffaf5;">
    <div class="max-w-5xl mx-auto px-5 text-center relative z-10">
      <div class="fade-up visible mb-4">
        <span class="trust-badge"><i class="fas fa-award mr-1 text-xs"></i> Egypt's First Jewelry Recovery Platform</span>
      </div>
      <h1 class="fade-up delay-1 visible font-playfair text-4xl md:text-6xl font-black text-gray-800 leading-tight">
        About <span class="text-orange-gradient">Findex</span>
      </h1>
      <p class="fade-up delay-2 visible text-xl md:text-2xl font-light italic text-gray-600 mt-5 max-w-2xl mx-auto">
        “Reuniting people with what matters most through trust, technology, and transparency.”
      </p>
      <p class="fade-up delay-3 visible text-gray-500 max-w-2xl mx-auto mt-4 text-base">
        Findex is Egypt's first dedicated platform for recovering lost and stolen jewelry, seamlessly connecting individuals with a verified network of certified shops powered by AI matching and built on security.
      </p>
      <div class="flex flex-wrap justify-center gap-8 md:gap-12 mt-10 fade-up delay-4 visible">
        <div><div class="font-playfair text-3xl font-extrabold text-orange-600">100+</div><div class="text-xs text-gray-500">Verified Shops</div></div>
        <div><div class="font-playfair text-3xl font-extrabold text-amber-600">5K+</div><div class="text-xs text-gray-500">Recoveries</div></div>
        <div><div class="font-playfair text-3xl font-extrabold text-orange-600">24/7</div><div class="text-xs text-gray-500">Hotline Support</div></div>
        <div><div class="font-playfair text-3xl font-extrabold text-amber-600">AI</div><div class="text-xs text-gray-500">Smart Matching</div></div>
      </div>
    </div>
</section>

<!-- =========================== STORY SECTION ============================ -->
<section id="story" class="py-16 md:py-20 bg-white">
    <div class="max-w-6xl mx-auto px-5">
      <div class="grid md:grid-cols-2 gap-12 items-center">
        <div class="fade-left">
          <span class="text-orange-500 text-xs font-bold uppercase tracking-wider">Our Story</span>
          <h2 class="font-playfair text-3xl md:text-4xl font-bold text-gray-800 mt-2">Born in Cairo, built for Egypt</h2>
          <div class="h-1 w-16 bg-gradient-to-r from-orange-500 to-amber-400 rounded-full mt-3 mb-5"></div>
          <p class="text-gray-600 leading-relaxed mb-4">Every day, precious jewelry goes missing, heirlooms, engagement rings, sentimental pieces. Finding them across a fragmented market was nearly impossible. That is why we built Findex: a digital bridge between individuals and Egypt's most trusted jewelry shops.</p>
          <p class="text-gray-600 leading-relaxed">Today, Findex combines AI driven inventory scanning, identity verification, and real time case tracking. We have already helped thousands of Egyptians recover what they thought was lost forever.</p>
          <div class="flex gap-4 mt-6">
            <div class="flex items-center gap-2 bg-orange-50 px-4 py-2 rounded-full"><i class="fas fa-check-circle text-orange-500"></i><span class="text-xs font-medium">Licensed and Regulated</span></div>
            <div class="flex items-center gap-2 bg-amber-50 px-4 py-2 rounded-full"><i class="fas fa-shield-alt text-amber-600"></i><span class="text-xs font-medium">PDPL compliant</span></div>
          </div>
        </div>
        <div class="fade-right flex justify-center">
          <div class="relative w-full max-w-md">
            <div class="absolute -top-6 -left-6 w-28 h-28 bg-orange-100 rounded-full blur-2xl opacity-60"></div>
            <div class="card-soft p-6 shadow-xl relative z-10 bg-white/90">
              <i class="fas fa-gem text-orange-300 text-3xl mb-3"></i>
              <p class="text-gray-700 font-medium">Findex is transforming jewelry recovery across Egypt, bringing hope back to families and businesses alike.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
</section>

<!-- =========================== HOW IT WORKS (Individuals + Shops) ============================ -->
<section id="how-it-works" class="py-16 bg-gradient-to-br from-orange-50/40 to-white">
    <div class="max-w-7xl mx-auto px-5">
      <div class="text-center fade-up">
        <div class="section-divider"></div>
        <h2 class="font-playfair text-3xl md:text-4xl font-bold text-gray-800">Built for Everyone</h2>
        <p class="text-gray-500 max-w-xl mx-auto mt-3">Whether you have lost something precious or you run a jewelry business, Findex has tailored tools.</p>
      </div>
      <div class="grid md:grid-cols-2 gap-8 mt-12">
        <div class="fade-left card-soft p-6 md:p-8 bg-white/90">
          <div class="flex items-center gap-4 mb-6"><div class="w-14 h-14 orange-gradient rounded-2xl flex items-center justify-center shadow"><i class="fas fa-user text-white text-xl"></i></div><div><h3 class="font-playfair text-2xl font-bold text-gray-800">For Individuals</h3><p class="text-orange-500 text-sm font-medium">Personal recovery tools</p></div></div>
          <div class="space-y-5">
            <div class="flex gap-4"><div class="icon-circle"><i class="fas fa-id-card text-orange-500 text-lg"></i></div><div><h4 class="font-semibold">Identity Verification</h4><p class="text-sm text-gray-500">Secure identity checks ensure legitimate owners file claims.</p></div></div>
            <div class="flex gap-4"><div class="icon-circle"><i class="fas fa-microchip text-orange-500 text-lg"></i></div><div><h4 class="font-semibold">AI Powered Matching</h4><p class="text-sm text-gray-500">Our AI engine scans shop inventories and submitted descriptions and hallmarks to find matches.</p></div></div>
            <div class="flex gap-4"><div class="icon-circle"><i class="fas fa-chart-line text-orange-500 text-lg"></i></div><div><h4 class="font-semibold">Real Time Case Tracking</h4><p class="text-sm text-gray-500">Monitor case status from submission to resolution with instant alerts.</p></div></div>
            <div class="flex gap-4"><div class="icon-circle"><i class="fas fa-lock text-orange-500 text-lg"></i></div><div><h4 class="font-semibold">Confidential Reporting</h4><p class="text-sm text-gray-500">Your personal data is protected under strict privacy protocols.</p></div></div>
            <div class="flex gap-4"><div class="icon-circle"><i class="fas fa-phone-alt text-orange-500 text-lg"></i></div><div><h4 class="font-semibold">24/7 Support Hotline <span class="text-orange-600">16789</span></h4><p class="text-sm text-gray-500">Reach our team any time, we are always here.</p></div></div>
          </div>
        </div>
        <div class="fade-right card-soft p-6 md:p-8 bg-white/90 border-l-4 border-amber-300">
          <div class="flex items-center gap-4 mb-6"><div class="w-14 h-14 gold-gradient rounded-2xl flex items-center justify-center shadow"><i class="fas fa-store text-white text-xl"></i></div><div><h3 class="font-playfair text-2xl font-bold text-gray-800">For Jewelry Shops</h3><p class="text-amber-600 text-sm font-medium">Business Premium Features</p></div></div>
          <div class="space-y-5">
            <div class="flex gap-4"><div class="icon-gold-circle"><i class="fas fa-badge-check text-amber-500 text-lg"></i></div><div><h4 class="font-semibold">Verified Status Badge</h4><p class="text-sm text-gray-500">Earn the Findex Verified badge, builds customer confidence.</p></div></div>
            <div class="flex gap-4"><div class="icon-gold-circle"><i class="fas fa-chart-simple text-amber-500 text-lg"></i></div><div><h4 class="font-semibold">Business Analytics Dashboard</h4><p class="text-sm text-gray-500">Insights on match rates, recovery stats, and performance metrics.</p></div></div>
            <div class="flex gap-4"><div class="icon-gold-circle"><i class="fas fa-bell text-amber-500 text-lg"></i></div><div><h4 class="font-semibold">Instant Match Alerts</h4><p class="text-sm text-gray-500">Real time notifications when a lost report matches your inventory.</p></div></div>
            <div class="flex gap-4"><div class="icon-gold-circle"><i class="fas fa-user-tie text-amber-500 text-lg"></i></div><div><h4 class="font-semibold">Dedicated Account Manager</h4><p class="text-sm text-gray-500">Onboarding and compliance support for premium members.</p></div></div>
            <div class="flex gap-4"><div class="icon-gold-circle"><i class="fas fa-gavel text-amber-500 text-lg"></i></div><div><h4 class="font-semibold">Compliance and Legal Tools</h4><p class="text-sm text-gray-500">Documentation and reporting to handle recovered jewelry legally.</p></div></div>
          </div>
        </div>
      </div>
    </div>
</section>

<!-- =========================== TRUST + AI SECTION ============================ -->
<section id="trust-ai" class="py-16 md:py-20 bg-orange-50/30">
    <div class="max-w-7xl mx-auto px-5">
      <div class="text-center fade-up"><div class="section-divider"></div><h2 class="font-playfair text-3xl md:text-4xl font-bold text-gray-800">Why Trust Findex?</h2><p class="text-gray-500 max-w-xl mx-auto mt-2">Verifiable metrics, partnerships and zero compromise security architecture.</p></div>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mt-12">
        <div class="bg-white rounded-2xl p-5 text-center shadow-sm border border-orange-100 fade-up delay-1"><div class="font-playfair text-3xl font-black text-orange-500">100+</div><div class="text-sm font-semibold">Verified Shops</div><div class="text-xs text-gray-400">Nationwide</div></div>
        <div class="bg-white rounded-2xl p-5 text-center shadow-sm border border-amber-100 fade-up delay-2"><div class="font-playfair text-3xl font-black text-amber-500">5K+</div><div class="text-sm font-semibold">Recoveries</div><div class="text-xs text-gray-400">And counting</div></div>
        <div class="bg-white rounded-2xl p-5 text-center shadow-sm border border-orange-100 fade-up delay-3"><div class="font-playfair text-3xl font-black text-orange-500">98%</div><div class="text-sm font-semibold">Satisfaction</div><div class="text-xs text-gray-400">User verified</div></div>
        <div class="bg-white rounded-2xl p-5 text-center shadow-sm border border-amber-100 fade-up delay-4"><div class="font-playfair text-3xl font-black text-amber-500">72h</div><div class="text-sm font-semibold">Average Recovery</div><div class="text-xs text-gray-400">Fastest in Egypt</div></div>
      </div>
      <div class="grid md:grid-cols-2 gap-7 mt-12">
        <div class="fade-left bg-white rounded-2xl p-6 shadow-md border-l-8 border-orange-400">
          <div class="flex items-center gap-4 mb-3"><i class="fas fa-brain text-3xl text-orange-500"></i><h3 class="font-playfair text-xl font-bold">AI Powered Matching Technology</h3></div>
          <p class="text-gray-600 text-sm leading-relaxed">Our proprietary AI engine analyzes submitted photos, hallmark codes, material descriptions, and shop inventory data to generate accurate match scores, dramatically reducing recovery time across Egypt. Deep learning, visual recognition, and secure API integration.</p>
          <div class="flex flex-wrap gap-3 mt-4"><span class="text-xs bg-orange-100 text-orange-800 px-3 py-1.5 rounded-full"><i class="fas fa-chart-line mr-1"></i>Deep Learning</span><span class="text-xs bg-amber-100 text-amber-800 px-3 py-1.5 rounded-full"><i class="fas fa-eye mr-1"></i>Visual Recognition</span><span class="text-xs bg-gray-100 text-gray-700 px-3 py-1.5 rounded-full"><i class="fas fa-lock mr-1"></i>Secure API</span></div>
        </div>
        <div class="fade-right grid grid-cols-1 gap-4">
          <div class="flex items-start gap-4 bg-white/80 p-4 rounded-xl"><i class="fas fa-building text-orange-500 text-xl mt-1"></i><div><h4 class="font-bold">Government Registered</h4><p class="text-xs text-gray-500">Findex operates in full compliance with Egyptian law and consumer protection authorities.</p></div></div>
          <div class="flex items-start gap-4 bg-white/80 p-4 rounded-xl"><i class="fas fa-shield-hart text-amber-500 text-xl mt-1"></i><div><h4 class="font-bold">Verified Shop Network</h4><p class="text-xs text-gray-500">Every jewelry shop undergoes license verification and physical inspection.</p></div></div>
          <div class="flex items-start gap-4 bg-white/80 p-4 rounded-xl"><i class="fas fa-lock text-orange-500 text-xl mt-1"></i><div><h4 class="font-bold">End to End Encryption</h4><p class="text-xs text-gray-500">AES 256, TLS 1.3, PDPL 2020 compliant. Your data is never exposed.</p></div></div>
        </div>
      </div>
    </div>
</section>

<!-- PRIVACY AND SECURITY SECTION -->
<section class="py-16 bg-white">
    <div class="max-w-6xl mx-auto px-5 text-center fade-up">
      <div class="section-divider"></div>
      <h2 class="font-playfair text-3xl font-bold text-gray-800">Privacy and Security First</h2>
      <p class="text-gray-500 max-w-md mx-auto mt-2">Your data is sacred, designed with privacy first principles at every layer.</p>
      <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-5 mt-10 text-left">
        <div class="bg-orange-50/40 p-5 rounded-2xl"><i class="fas fa-database text-orange-500 text-xl mb-3"></i><h4 class="font-bold">Data Protection</h4><p class="text-xs text-gray-500">Encrypted at rest and transit, compliant with Personal Data Protection Law No. 151.</p></div>
        <div class="bg-amber-50/40 p-5 rounded-2xl"><i class="fas fa-eye-slash text-amber-500 text-xl mb-3"></i><h4 class="font-bold">Controlled Disclosure</h4><p class="text-xs text-gray-500">Case details shared only with verified shops on need to know basis.</p></div>
        <div class="bg-orange-50/40 p-5 rounded-2xl"><i class="fas fa-fingerprint text-orange-500 text-xl mb-3"></i><h4 class="font-bold">Access Controls</h4><p class="text-xs text-gray-500">Multi factor authentication and role based access for sensitive cases.</p></div>
        <div class="bg-amber-50/40 p-5 rounded-2xl"><i class="fas fa-history text-amber-500 text-xl mb-3"></i><h4 class="font-bold">Audit Trails</h4><p class="text-xs text-gray-500">Legal grade timestamped logs for every action.</p></div>
        <div class="bg-orange-50/40 p-5 rounded-2xl"><i class="fas fa-trash-alt text-orange-500 text-xl mb-3"></i><h4 class="font-bold">Right to Erasure</h4><p class="text-xs text-gray-500">Users can request full data deletion at any time.</p></div>
        <div class="bg-amber-50/40 p-5 rounded-2xl"><i class="fas fa-chart-simple text-amber-500 text-xl mb-3"></i><h4 class="font-bold">Data Minimization</h4><p class="text-xs text-gray-500">We collect only strictly necessary case information.</p></div>
      </div>
      <div class="inline-flex items-center gap-4 mt-10 bg-gray-50 px-6 py-3 rounded-full border"><i class="fas fa-certificate text-orange-500"></i><span class="text-sm font-medium">Findex Security Certified, AES 256, TLS 1.3, PDPL 2020</span></div>
    </div>
</section>

<!-- CONTACT + HOTLINE + AI CHAT -->
<section id="contact-section" class="py-16 bg-gradient-to-br from-orange-50 to-white">
    <div class="max-w-6xl mx-auto px-5">
      <div class="text-center fade-up"><div class="section-divider"></div><h2 class="font-playfair text-3xl md:text-4xl font-bold text-gray-800">Contact and Support</h2><p class="text-gray-500 max-w-xl mx-auto mt-2">We are here around the clock, reach us by phone, email, or live chat.</p></div>
      <div class="text-center my-12 fade-up delay-2"><div class="hotline-block mx-auto inline-flex items-center gap-2"><i class="fas fa-phone-alt"></i> 16789</div><p class="text-xs text-gray-500 mt-3">Emergency recovery hotline, 24 hours, 7 days a week</p></div>
      <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
        <div class="card-soft p-6 bg-white fade-left"><div class="flex items-center gap-3 border-b border-orange-100 pb-4 mb-4"><i class="fas fa-user-circle text-orange-500 text-2xl"></i><h3 class="font-playfair text-xl font-bold">Individual Support</h3></div><div class="space-y-4 text-sm"><div><i class="fas fa-envelope w-6 text-orange-400"></i> <a href="mailto:support@findex.eg" class="hover:text-orange-600">support@findex.eg</a></div><div><i class="fas fa-phone-alt w-6 text-orange-400"></i> <a href="tel:+20225000000">+20 (2) 25 000 000</a></div><div><i class="fas fa-headset w-6 text-orange-400"></i> <strong>Hotline:</strong> 16789 (free)</div><div><i class="fas fa-comment-dots w-6 text-orange-400"></i> Live chat available on app and web</div></div><div class="mt-5 text-xs bg-orange-50 p-3 rounded-xl flex items-center gap-2"><i class="fas fa-robot text-orange-500"></i><span>AI support assistant ready 24/7, ask us anything.</span></div></div>
        <div class="card-soft p-6 bg-white fade-right"><div class="flex items-center gap-3 border-b border-amber-100 pb-4 mb-4"><i class="fas fa-briefcase text-amber-500 text-2xl"></i><h3 class="font-playfair text-xl font-bold">Business and Shop Partnerships</h3></div><div class="space-y-4 text-sm"><div><i class="fas fa-envelope w-6 text-amber-500"></i> <a href="mailto:business@findex.eg">business@findex.eg</a></div><div><i class="fas fa-handshake w-6 text-amber-500"></i> <a href="mailto:partners@findex.eg">partners@findex.eg</a></div><div><i class="fas fa-phone w-6 text-amber-500"></i> +20 (2) 25 000 001</div><div><i class="fas fa-store w-6 text-amber-500"></i> Onboarding portal: shops.findex.eg</div></div><div class="mt-5 text-xs bg-amber-50 p-3 rounded-xl"><i class="fas fa-chart-line"></i> Premium dashboard and dedicated account manager for verified shops.</div></div>
      </div>
      <div class="mt-10 text-center text-gray-400 text-xs">Our support team responds within 2 hours. For urgent recoveries, call 16789 immediately.</div>
    </div>
</section>

<!-- =========================== FOOTER =========================== -->
<footer class="bg-gray-900 text-white py-10 mt-8">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid md:grid-cols-4 gap-6">
            <div>
                <div class="flex items-center mb-3">
                    <div class="orange-gradient w-9 h-9 rounded-lg flex items-center justify-center mr-2">
                        <i class="fas fa-gem text-white text-sm"></i>
                    </div>
                    <span class="font-bold text-lg">Findex</span>
                </div>
                <p class="text-gray-400 text-sm">Recovering precious memories, one piece at a time in Egypt.</p>
            </div>
            <div>
                <h3 class="font-semibold text-sm mb-3">Quick Links</h3>
                <ul class="space-y-1 text-gray-400 text-sm">
                    <li><a href="about.php" class="hover:text-orange-400 transition">About Us</a></li>
                    <li><a href="search.php" class="hover:text-orange-400 transition">Browse Reports</a></li>
                    <li><a href="register.php?type=shop" class="hover:text-orange-400 transition">Join as Shop</a></li>
                    <li><a href="pricing.php" class="hover:text-orange-400 transition">Pricing</a></li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold text-sm mb-3">Support</h3>
                <ul class="space-y-1 text-gray-400 text-sm">
                    <li><a href="help.php" class="hover:text-orange-400 transition">Help Center</a></li>
                    <li><a href="contact.php" class="hover:text-orange-400 transition">Contact Us</a></li>
                    <li><a href="privacy.php" class="hover:text-orange-400 transition">Privacy Policy</a></li>
                    <li><a href="terms.php" class="hover:text-orange-400 transition">Terms of Service</a></li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold text-sm mb-3">Connect</h3>
                <div class="flex space-x-3">
                    <a href="#" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-orange-600 transition"><i class="fab fa-facebook-f text-sm"></i></a>
                    <a href="#" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-orange-600 transition"><i class="fab fa-twitter text-sm"></i></a>
                    <a href="#" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-orange-600 transition"><i class="fab fa-instagram text-sm"></i></a>
                </div>
                <div class="mt-4">
                    <p class="text-gray-500 text-[10px]">Follow us for updates</p>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-800 mt-6 pt-6 text-center text-gray-400 text-[11px]">
            <p>&copy; 2026 Findex. All rights reserved. Recover with Trust in Egypt.</p>
        </div>
    </div>
</footer>

<!-- Scroll animation observer -->
<script>
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) entry.target.classList.add('visible');
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -20px 0px' });
    document.querySelectorAll('.fade-up, .fade-left, .fade-right').forEach(el => observer.observe(el));
</script>
</body>
</html>