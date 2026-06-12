<?php

$notif_count = 0;
if (isset($_SESSION['user_id'])) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $notif_count = $stmt->fetchColumn();
}
?>

<nav class="bg-white/95 backdrop-blur-md shadow-lg sticky top-0 z-50 border-b border-orange-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4">
        <div class="flex justify-between items-center">
            <!-- Logo - Clickable to go home -->
            <a href="index.php" class="flex items-center space-x-3 hover:opacity-90 transition">
                <div class="orange-gradient rounded-xl w-10 h-10 flex items-center justify-center shadow-md">
                    <i class="fas fa-gem text-white text-xl"></i>
                </div>
                <div>
                    <span class="font-bold text-xl sm:text-2xl text-gray-800">Findex</span>
                    <span class="text-[10px] sm:text-xs text-gray-400 block -mt-1">مصر | Luxury Recovery</span>
                </div>
            </a>
            
            <!-- Desktop Menu (visible on tablets and desktop) -->
            <div class="hidden md:flex items-center space-x-6 lg:space-x-8">
                <a href="index.php" class="text-gray-600 hover:text-orange-600 transition font-medium nav-link">Home</a>
                <a href="search.php" class="text-gray-600 hover:text-orange-600 transition font-medium nav-link">Search</a>
                <a href="about.php" class="text-gray-600 hover:text-orange-600 transition font-medium nav-link">About</a>
                
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])): ?>
                    <?php 
                    // Determine correct dashboard page based on user type
                    $dashboard_page = 'dashboard_user.php';
                    if ($_SESSION['user_type'] === 'shop') {
                        $dashboard_page = 'dashboard_shop.php';
                    } elseif ($_SESSION['user_type'] === 'admin') {
                        $dashboard_page = 'dashboard_admin.php';
                    } elseif ($_SESSION['user_type'] === 'moderator') {
                        $dashboard_page = 'dashboard_moderator.php';
                    } elseif ($_SESSION['user_type'] === 'finance') {
                        $dashboard_page = 'dashboard_finance.php';
                    }
                    ?>
                    
                    <!-- Dashboard Link -->
                    <a href="<?php echo $dashboard_page; ?>" class="text-gray-600 hover:text-orange-600 transition nav-link">Dashboard</a>
                    
                    <!-- Send Alert Link - Only for Admin -->
                    <?php if ($_SESSION['user_type'] === 'admin'): ?>
                        <a href="admin_send_notification.php" class="text-gray-600 hover:text-orange-600 transition nav-link">Send Alert</a>
                    <?php endif; ?>
                    
                    <!-- Profile Link -->
                    <a href="profile.php?id=<?php echo $_SESSION['user_id']; ?>" class="text-gray-600 hover:text-orange-600 transition nav-link">Profile</a>
                    
                    <!-- Notifications Bell with Counter -->
                    <a href="notifications.php" class="relative text-gray-600 hover:text-orange-600 transition">
                        <i class="fas fa-bell text-xl"></i>
                        <?php if ($notif_count > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1">
                                <?php echo $notif_count > 9 ? '9+' : $notif_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Report Button -->
                    <a href="report_item.php" class="orange-gradient text-white px-4 py-2 rounded-full text-sm font-semibold shadow-md hover:shadow-lg transition">
                        <i class="fas fa-plus mr-1"></i> Report
                    </a>
                    
                    <!-- Logout Button -->
                    <a href="logout.php" class="text-red-500 hover:text-red-600 transition">Logout</a>
                    
                <?php else: ?>
                    <!-- Guest Menu -->
                    <a href="login.php" class="text-gray-600 hover:text-orange-600 transition nav-link">Login</a>
                    <a href="register.php" class="orange-gradient text-white px-5 py-2 rounded-full text-sm font-semibold shadow-md hover:shadow-lg transition">Sign Up</a>
                <?php endif; ?>
            </div>
            
            <!-- Mobile Menu Button (visible only on phones) -->
            <button class="mobile-menu-btn md:hidden" id="menuBtn">
                <i class="fas fa-bars text-2xl text-orange-600"></i>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile Menu Overlay -->
<div class="menu-overlay fixed inset-0 bg-black/50 z-40 hidden" id="menuOverlay"></div>

<!-- Mobile Menu Slide-in Panel -->
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
            } elseif ($_SESSION['user_type'] === 'finance') {
                $mobile_dashboard = 'dashboard_finance.php';
            }
            ?>
            
            <a href="<?php echo $mobile_dashboard; ?>" class="flex items-center gap-3 text-gray-700 hover:text-orange-600 transition font-medium py-3 border-b border-gray-100">
                <i class="fas fa-tachometer-alt w-6 text-orange-500"></i> Dashboard
            </a>
            
            <!-- Send Alert Link for Admin in Mobile Menu -->
            <?php if ($_SESSION['user_type'] === 'admin'): ?>
                <a href="admin_send_notification.php" class="flex items-center gap-3 text-gray-700 hover:text-orange-600 transition font-medium py-3 border-b border-gray-100">
                    <i class="fas fa-bell w-6 text-orange-500"></i> Send Alert
                </a>
            <?php endif; ?>
            
            <!-- Profile Link for mobile menu -->
            <a href="profile.php?id=<?php echo $_SESSION['user_id']; ?>" class="flex items-center gap-3 text-gray-700 hover:text-orange-600 transition font-medium py-3 border-b border-gray-100">
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
    .orange-gradient {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    }
    .mobile-menu::-webkit-scrollbar {
        width: 4px;
    }
    .mobile-menu::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .mobile-menu::-webkit-scrollbar-thumb {
        background: #f97316;
        border-radius: 10px;
    }
    
    /* Responsive navbar */
    @media (max-width: 768px) {
        .desktop-menu {
            display: none;
        }
        .mobile-menu-btn {
            display: block;
        }
        nav a, .nav-link {
            font-size: 13px;
        }
    }
    @media (min-width: 769px) {
        .mobile-menu-btn {
            display: none;
        }
        .mobile-menu, .menu-overlay {
            display: none !important;
        }
        nav a, .nav-link {
            font-size: 14px;
        }
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
        if (window.innerWidth >= 768) {
            closeMenu();
        }
    });
    
    if (mobileMenu) {
        const links = mobileMenu.querySelectorAll('a');
        links.forEach(function(link) {
            link.addEventListener('click', closeMenu);
        });
    }
})();
</script>