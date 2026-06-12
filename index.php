<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$recent_reports = [];
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name as user_name 
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.moderation_status = 'approved' AND r.status = 'active'
    ORDER BY r.created_at DESC 
    LIMIT 6
");
$stmt->execute();
$recent_reports = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Findex - Recover Lost & Stolen Jewelry in Egypt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .artistic-font { font-family: 'Playfair Display', serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        .gold-gradient { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        body { background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%); }
        
        .hero-pattern {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            position: relative;
            overflow: hidden;
        }
        
        /* Feature Cards */
        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 28px 20px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            border: 1px solid #eef2ff;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(249,115,22,0.05), transparent);
            transition: left 0.5s ease;
        }
        
        .feature-card:hover::before {
            left: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 25px 40px -12px rgba(0, 0, 0, 0.12);
            border-color: #f97316;
        }
        
        .feature-icon {
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.08);
        }
        
        /* Step Circles */
        .step-circle {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-weight: bold;
            font-size: 22px;
            transition: all 0.3s ease;
        }
        
        .step-wrapper {
            transition: all 0.3s ease;
            cursor: default;
        }
        
        .step-wrapper:hover {
            transform: translateY(-4px);
        }
        
        .step-wrapper:hover .step-circle {
            transform: scale(1.05);
            box-shadow: 0 0 0 4px rgba(249,115,22,0.15);
        }
        
        /* Horizontal Scroll Section */
        .partner-scroll-section {
            background: #ffffff;
            padding: 60px 0;
            position: relative;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 48px;
        }
        
        .section-header .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fef3e8;
            padding: 6px 16px;
            border-radius: 40px;
            font-size: 11px;
            font-weight: 600;
            color: #ea580c;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }
        
        .section-header .badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(249,115,22,0.1);
        }
        
        .section-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .section-header .divider {
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, #f97316, #f59e0b);
            margin: 16px auto 0;
            transition: width 0.3s ease;
        }
        
        .section-header:hover .divider {
            width: 80px;
        }
        
        /* Scroll Container */
        .scroll-container {
            position: relative;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .scroll-wrapper {
            overflow-x: auto;
            scroll-behavior: smooth;
            scrollbar-width: thin;
            -webkit-overflow-scrolling: touch;
        }
        
        .scroll-wrapper::-webkit-scrollbar {
            height: 4px;
        }
        
        .scroll-wrapper::-webkit-scrollbar-track {
            background: #f0ebe3;
            border-radius: 10px;
        }
        
        .scroll-wrapper::-webkit-scrollbar-thumb {
            background: #f97316;
            border-radius: 10px;
            transition: background 0.3s ease;
        }
        
        .scroll-wrapper::-webkit-scrollbar-thumb:hover {
            background: #ea580c;
        }
        
        .scroll-content {
            display: flex;
            gap: 24px;
            padding: 8px 4px 20px 4px;
        }
        
        /* Shop Card - Clickable */
        .shop-card-scroll {
            flex: 0 0 280px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            border: 1px solid #f0ebe3;
            cursor: pointer;
            text-decoration: none;
            display: block;
            animation: fadeInLeft 0.5s ease-out forwards;
            opacity: 0;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .shop-card-scroll:nth-child(1) { animation-delay: 0.05s; animation-name: fadeInLeft; }
        .shop-card-scroll:nth-child(2) { animation-delay: 0.1s; animation-name: fadeInLeft; }
        .shop-card-scroll:nth-child(3) { animation-delay: 0.15s; animation-name: fadeInLeft; }
        .shop-card-scroll:nth-child(4) { animation-delay: 0.2s; animation-name: fadeInLeft; }
        .shop-card-scroll:nth-child(5) { animation-delay: 0.25s; animation-name: fadeInLeft; }
        .shop-card-scroll:nth-child(6) { animation-delay: 0.3s; animation-name: fadeInLeft; }
        .shop-card-scroll:nth-child(7) { animation-delay: 0.35s; animation-name: fadeInLeft; }
        .shop-card-scroll:nth-child(8) { animation-delay: 0.4s; animation-name: fadeInLeft; }
        
        .shop-card-scroll:hover {
            transform: translateY(-6px);
            border-color: #f97316;
            box-shadow: 0 20px 35px -12px rgba(249,115,22,0.15);
        }
        
        .card-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .shop-card-scroll:hover .card-img {
            transform: scale(1.04);
        }
        
        .card-body-scroll {
            padding: 16px;
        }
        
        .shop-name {
            font-size: 1rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 6px;
        }
        
        .rating-scroll {
            display: flex;
            align-items: center;
            gap: 3px;
            margin-bottom: 8px;
        }
        
        .rating-scroll i {
            font-size: 10px;
            color: #fbbf24;
        }
        
        .rating-scroll span {
            font-size: 10px;
            color: #9ca3af;
            margin-left: 4px;
        }
        
        .location-scroll {
            font-size: 11px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }
        
        .location-scroll i {
            color: #f97316;
            font-size: 9px;
        }
        
        .verified-scroll {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f0fdf4;
            padding: 3px 10px;
            border-radius: 30px;
            font-size: 9px;
            font-weight: 600;
            color: #16a34a;
        }
        
        /* Scroll Buttons */
        .scroll-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.25s ease;
            color: #f97316;
            border: 1px solid #f0ebe3;
            z-index: 10;
        }
        
        .scroll-btn:hover {
            background: #f97316;
            color: white;
            transform: translateY(-50%) scale(1.08);
            box-shadow: 0 8px 20px rgba(249,115,22,0.25);
        }
        
        .scroll-btn.left {
            left: 0;
        }
        
        .scroll-btn.right {
            right: 0;
        }
        
        /* Trust Row */
        .trust-row-simple {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 32px;
            margin-top: 48px;
            padding-top: 24px;
            border-top: 1px solid #f0ebe3;
        }
        
        .trust-item-simple {
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
            cursor: default;
        }
        
        .trust-item-simple:hover {
            transform: translateY(-3px);
        }
        
        .trust-item-simple i {
            transition: transform 0.2s ease;
        }
        
        .trust-item-simple:hover i {
            transform: scale(1.1);
        }
        
        /* Report Cards */
        .report-card {
            transition: all 0.35s ease;
            animation: fadeInUp 0.5s ease-out forwards;
            opacity: 0;
        }
        
        .report-card:nth-child(1) { animation-delay: 0.05s; }
        .report-card:nth-child(2) { animation-delay: 0.1s; }
        .report-card:nth-child(3) { animation-delay: 0.15s; }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px -12px rgba(0, 0, 0, 0.12);
        }
        
        /* CTA Button */
        .cta-button {
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .cta-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }
        
        .cta-button:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .cta-button:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 10px 28px rgba(249,115,22,0.35);
        }
        
        .cta-button i {
            transition: transform 0.2s ease;
        }
        
        .cta-button:hover i {
            transform: translateX(3px);
        }
        
        /* Fade on scroll */
        .fade-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.7s ease;
        }
        
        .fade-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Scroll indicator */
        .scroll-indicator {
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            align-items: center;
            background: rgba(249,115,22,0.1);
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 10px;
            color: #f97316;
            white-space: nowrap;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 0.6; transform: translateX(-50%) scale(1); }
            50% { opacity: 1; transform: translateX(-50%) scale(1.02); }
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(5px); }
        }
        
        .scroll-indicator i {
            animation: bounce 1.5s infinite;
        }
        
        @media (max-width: 768px) {
            .partner-scroll-section { padding: 40px 0; }
            .section-header h2 { font-size: 1.5rem; }
            .shop-card-scroll { flex: 0 0 240px; }
            .card-img { height: 150px; }
            .trust-row-simple { gap: 16px; }
            .scroll-btn { width: 36px; height: 36px; }
            .scroll-indicator { display: none; }
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Hero Section -->
<section class="hero-pattern text-white py-14 md:py-20">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-1.5 rounded-full mb-5">
            <i class="fas fa-map-marked-alt text-orange-400 text-xs"></i>
            <span class="text-[10px] tracking-wider">Egypt's Premier Recovery Platform</span>
        </div>
        <h1 class="artistic-font text-3xl md:text-4xl font-bold mb-4">Find Your Precious Items</h1>
        <div class="flex flex-col items-center gap-1 mb-5">
            <p class="text-base md:text-lg text-gray-300">Search Lost & Stolen</p>
            <h2 class="text-2xl md:text-3xl font-bold gold-gradient bg-clip-text text-transparent">Jewelry in Egypt</h2>
        </div>
        <p class="text-sm text-gray-400 max-w-md mx-auto">Recover what's yours with Egypt's most trusted platform</p>
        <div class="flex justify-center gap-4 mt-7">
            <a href="register.php" class="orange-gradient text-white px-6 py-2.5 rounded-full font-semibold text-sm shadow-lg hover:shadow-xl transition hover:scale-105">Get Started Free</a>
            <a href="search.php" class="bg-white/10 backdrop-blur-sm border border-white/20 text-white px-6 py-2.5 rounded-full font-semibold text-sm hover:bg-white/20 transition hover:scale-105">Browse Reports</a>
        </div>
    </div>
</section>

<!-- ============================================================ -->
<!-- HORIZONTAL SCROLL SLIDESHOW WITH CLICKABLE SHOP CARDS -->
<!-- ============================================================ -->
<section class="partner-scroll-section fade-on-scroll">
    <div class="section-header">
        <div class="badge">
            <i class="fas fa-store"></i> OUR PARTNERS
        </div>
        <h2>Trusted Jewelry Shops</h2>
        <p class="text-gray-500 text-sm">Nationwide network of verified jewelry stores</p>
        <div class="divider"></div>
    </div>
    
    <div class="scroll-container">
        <!-- Scroll Buttons -->
        <div class="scroll-btn left" id="scrollLeft">
            <i class="fas fa-chevron-left text-sm"></i>
        </div>
        <div class="scroll-btn right" id="scrollRight">
            <i class="fas fa-chevron-right text-sm"></i>
        </div>
        
        <!-- Scroll Wrapper -->
        <div class="scroll-wrapper" id="scrollWrapper">
            <div class="scroll-content">
                
                <!-- Shop 1 - Azza Fahmy (Clickable) -->
                <a href="partner_detail.php?id=1" class="shop-card-scroll">
                    <img class="card-img" src="assets/images/shops/azza-fahmy.jpg" alt="Azza Fahmy Jewelry">
                    <div class="card-body-scroll">
                        <h4 class="shop-name">Azza Fahmy Jewelry</h4>
                        <div class="rating-scroll">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <span>5.0 (189)</span>
                        </div>
                        <div class="location-scroll">
                            <i class="fas fa-map-marker-alt"></i> Zamalek, Cairo
                        </div>
                        <div class="verified-scroll">
                            <i class="fas fa-check-circle"></i> Verified Partner
                        </div>
                    </div>
                </a>
                
                <!-- Shop 2 - Damas (Clickable) -->
                <a href="partner_detail.php?id=2" class="shop-card-scroll">
                    <img class="card-img" src="assets/images/shops/damas.jpg" alt="Damas Jewellery">
                    <div class="card-body-scroll">
                        <h4 class="shop-name">Damas Jewellery</h4>
                        <div class="rating-scroll">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                            <span>4.8 (342)</span>
                        </div>
                        <div class="location-scroll">
                            <i class="fas fa-map-marker-alt"></i> City Stars, Cairo
                        </div>
                        <div class="verified-scroll">
                            <i class="fas fa-check-circle"></i> Verified Partner
                        </div>
                    </div>
                </a>
                
                <!-- Shop 3 - L'Azurde (Clickable) -->
                <a href="partner_detail.php?id=3" class="shop-card-scroll">
                    <img class="card-img" src="assets/images/shops/lazurde.jpg" alt="L'Azurde Jewelry">
                    <div class="card-body-scroll">
                        <h4 class="shop-name">L'Azurde Jewelry</h4>
                        <div class="rating-scroll">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <span>5.0 (267)</span>
                        </div>
                        <div class="location-scroll">
                            <i class="fas fa-map-marker-alt"></i> Alexandria
                        </div>
                        <div class="verified-scroll">
                            <i class="fas fa-check-circle"></i> Verified Partner
                        </div>
                    </div>
                </a>
                
                <!-- Shop 4 - Jawhara (Clickable) -->
                <a href="partner_detail.php?id=4" class="shop-card-scroll">
                    <img class="card-img" src="assets/images/shops/jawhara.jpg" alt="Jawhara Jewellery">
                    <div class="card-body-scroll">
                        <h4 class="shop-name">Jawhara Jewellery</h4>
                        <div class="rating-scroll">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <span>5.0 (421)</span>
                        </div>
                        <div class="location-scroll">
                            <i class="fas fa-map-marker-alt"></i> Heliopolis, Cairo
                        </div>
                        <div class="verified-scroll">
                            <i class="fas fa-check-circle"></i> Verified Partner
                        </div>
                    </div>
                </a>
                
                <!-- Shop 5 - Tiffany & Co (Clickable) -->
                <a href="partner_detail.php?id=5" class="shop-card-scroll">
                    <img class="card-img" src="assets/images/shops/tiffany.jpg" alt="Tiffany & Co.">
                    <div class="card-body-scroll">
                        <h4 class="shop-name">Tiffany & Co.</h4>
                        <div class="rating-scroll">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <span>5.0 (512)</span>
                        </div>
                        <div class="location-scroll">
                            <i class="fas fa-map-marker-alt"></i> Mall of Arabia
                        </div>
                        <div class="verified-scroll">
                            <i class="fas fa-check-circle"></i> Verified Partner
                        </div>
                    </div>
                </a>
                
                <!-- Shop 6 - Cairo Gallery (Clickable) -->
                <a href="partner_detail.php?id=6" class="shop-card-scroll">
                    <img class="card-img" src="assets/images/shops/cairo-gallery.jpg" alt="Cairo Jewelry Gallery">
                    <div class="card-body-scroll">
                        <h4 class="shop-name">Cairo Jewelry Gallery</h4>
                        <div class="rating-scroll">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <span>5.0 (178)</span>
                        </div>
                        <div class="location-scroll">
                            <i class="fas fa-map-marker-alt"></i> New Cairo
                        </div>
                        <div class="verified-scroll">
                            <i class="fas fa-check-circle"></i> Verified Partner
                        </div>
                    </div>
                </a>
                
                <!-- Shop 7 - Cairo Gold (Clickable) -->
                <a href="partner_detail.php?id=7" class="shop-card-scroll">
                    <img class="card-img" src="assets/images/shops/cairo-gold.jpg" alt="Cairo Gold">
                    <div class="card-body-scroll">
                        <h4 class="shop-name">Cairo Gold</h4>
                        <div class="rating-scroll">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <span>5.0 (156)</span>
                        </div>
                        <div class="location-scroll">
                            <i class="fas fa-map-marker-alt"></i> Maadi, Cairo
                        </div>
                        <div class="verified-scroll">
                            <i class="fas fa-check-circle"></i> Verified Partner
                        </div>
                    </div>
                </a>
                
                <!-- Shop 8 - Luxor Jewelry (Clickable) -->
                <a href="partner_detail.php?id=8" class="shop-card-scroll">
                    <img class="card-img" src="assets/images/shops/luxor.jpg" alt="Luxor Jewelry">
                    <div class="card-body-scroll">
                        <h4 class="shop-name">Luxor Jewelry</h4>
                        <div class="rating-scroll">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <span>5.0 (98)</span>
                        </div>
                        <div class="location-scroll">
                            <i class="fas fa-map-marker-alt"></i> Luxor
                        </div>
                        <div class="verified-scroll">
                            <i class="fas fa-check-circle"></i> Verified Partner
                        </div>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="scroll-indicator">
            <i class="fas fa-arrow-right text-xs"></i> Scroll to see more <i class="fas fa-arrow-right text-xs"></i>
        </div>
    </div>
    
    <!-- Trust Row -->
    <div class="trust-row-simple">
        <div class="trust-item-simple"><i class="fas fa-shield-alt text-orange-500"></i><span>100% Verified Shops</span></div>
        <div class="trust-item-simple"><i class="fas fa-handshake text-amber-500"></i><span>Official Partnership</span></div>
        <div class="trust-item-simple"><i class="fas fa-headset text-emerald-500"></i><span>24/7 Support</span></div>
        <div class="trust-item-simple"><i class="fas fa-chart-line text-purple-500"></i><span>5,000+ Recoveries</span></div>
    </div>
</section>

<!-- Features Section -->
<section class="py-14 bg-white/50 fade-on-scroll">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-10">
            <span class="orange-gradient text-white px-4 py-1 rounded-full text-[11px] inline-block mb-3">Why Choose Findex</span>
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3">Powerful Features</h2>
            <p class="text-gray-500 text-sm max-w-2xl mx-auto">Everything you need to report and recover lost jewelry</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-7">
            <div class="feature-card">
                <div class="orange-gradient w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 feature-icon">
                    <i class="fas fa-robot text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold mb-2">AI-Powered Identification</h3>
                <p class="text-gray-500 text-sm">Advanced AI analyzes photos to identify unique jewelry characteristics</p>
            </div>
            <div class="feature-card">
                <div class="gold-gradient w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 feature-icon">
                    <i class="fas fa-shield-alt text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold mb-2">Verified Shop Network</h3>
                <p class="text-gray-500 text-sm">Trusted jewelry shops help identify and recover stolen items</p>
            </div>
            <div class="feature-card">
                <div class="bg-gray-100 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 feature-icon">
                    <i class="fas fa-chart-line text-gray-700 text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold mb-2">Real-time Analytics</h3>
                <p class="text-gray-500 text-sm">Track report views and potential matches in real-time</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="py-14 fade-on-scroll">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-10">
            <span class="gold-gradient text-white px-4 py-1 rounded-full text-[11px] inline-block mb-3">Simple Process</span>
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3">How It Works</h2>
            <p class="text-gray-500 text-sm">4 simple steps to report and recover</p>
        </div>
        
        <div class="grid md:grid-cols-4 gap-7">
            <div class="text-center step-wrapper">
                <div class="step-circle orange-gradient text-white shadow-md mx-auto">1</div>
                <h3 class="font-semibold text-base mb-2">Create Report</h3>
                <p class="text-gray-500 text-xs">Submit details about your lost or stolen jewelry</p>
            </div>
            <div class="text-center step-wrapper">
                <div class="step-circle gold-gradient text-white shadow-md mx-auto">2</div>
                <h3 class="font-semibold text-base mb-2">AI Analysis</h3>
                <p class="text-gray-500 text-xs">Our AI processes and enhances your images</p>
            </div>
            <div class="text-center step-wrapper">
                <div class="step-circle bg-gray-100 text-gray-600 shadow-md mx-auto">3</div>
                <h3 class="font-semibold text-base mb-2">Match Detection</h3>
                <p class="text-gray-500 text-xs">System searches for potential matches</p>
            </div>
            <div class="text-center step-wrapper">
                <div class="step-circle orange-gradient text-white shadow-md mx-auto">4</div>
                <h3 class="font-semibold text-base mb-2">Recovery</h3>
                <p class="text-gray-500 text-xs">Get notified when your item is found</p>
            </div>
        </div>
    </div>
</section>

<!-- Recent Reports Section -->
<?php if (!empty($recent_reports)): ?>
<section class="py-14 bg-white/50 fade-on-scroll">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-10">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">Recent Reports</h2>
            <p class="text-gray-500 text-sm">Latest lost and found jewelry in Egypt</p>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            <?php foreach (array_slice($recent_reports, 0, 3) as $report): ?>
            <a href="view_report.php?id=<?php echo $report['id']; ?>" class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition report-card block">
                <div class="p-5">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 text-[11px] rounded-full <?php echo $report['report_type'] === 'stolen' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo ucfirst($report['report_type']); ?>
                        </span>
                    </div>
                    <h3 class="font-semibold text-base mb-2"><?php echo htmlspecialchars($report['title']); ?></h3>
                    <p class="text-gray-500 text-xs mb-3"><?php echo htmlspecialchars(substr($report['description'], 0, 80)); ?>...</p>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500"><i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($report['location']); ?></span>
                        <span class="text-orange-600">View →</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-8">
            <a href="search.php" class="inline-block border border-orange-500 text-orange-600 px-6 py-2 rounded-full text-sm font-medium hover:bg-orange-500 hover:text-white transition">Browse All Reports →</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<section class="py-14 fade-on-scroll" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);">
    <div class="max-w-4xl mx-auto text-center px-4">
        <h2 class="text-2xl md:text-3xl font-bold text-white mb-3">Ready to Report Your Lost Jewelry?</h2>
        <p class="text-gray-300 text-sm mb-6">Join thousands of Egyptians who have successfully recovered their precious items</p>
        <a href="register.php" class="gold-gradient text-white px-7 py-2.5 rounded-full font-semibold text-sm inline-flex items-center gap-2 hover:shadow-lg transition cta-button">
            <i class="fas fa-gem text-sm"></i> Start Reporting Now
        </a>
    </div>
</section>

<!-- AI Assistant Widget -->
<?php include 'includes/ai_assistant.php'; ?>
<?php include 'includes/footer.php'; ?>

<!-- Horizontal Scroll JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scrollWrapper = document.getElementById('scrollWrapper');
    const scrollLeft = document.getElementById('scrollLeft');
    const scrollRight = document.getElementById('scrollRight');
    
    if (scrollLeft && scrollRight && scrollWrapper) {
        scrollLeft.addEventListener('click', function() {
            scrollWrapper.scrollBy({ left: -300, behavior: 'smooth' });
        });
        
        scrollRight.addEventListener('click', function() {
            scrollWrapper.scrollBy({ left: 300, behavior: 'smooth' });
        });
    }
    
    // Fade on scroll observer
    const fadeElements = document.querySelectorAll('.fade-on-scroll');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    
    fadeElements.forEach(el => observer.observe(el));
});
</script>
</body>
</html>