<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$shop_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Shop data with your actual image paths
$shops = [
    1 => [
        'name' => 'Azza Fahmy Jewelry',
        'image' => 'assets/images/shops/azza-fahmy.jpg',
        'logo_icon' => 'fa-crown',
        'established' => '1969',
        'location' => 'Zamalek, Cairo',
        'full_address' => '24 Brazil Street, Zamalek, Cairo, Egypt',
        'phone' => '+20 2 2735 1234',
        'email' => 'info@azzafahmy.com',
        'website' => 'www.azzafahmy.com',
        'rating' => 5.0,
        'reviews' => 189,
        'description' => 'Azza Fahmy is Egypt\'s most iconic jewelry brand. Founded in 1969, the brand is renowned for authentic handmade designs that blend traditional Arabic calligraphy and architecture with contemporary aesthetics.',
        'long_description' => 'Founded by Azza Fahmy, the first woman to lead a jewelry design house in the Middle East, the brand has become a symbol of luxury and cultural pride. Each collection draws inspiration from Egypt\'s rich history, from Pharaonic motifs to Islamic art. Azza Fahmy jewelry is handcrafted by master artisans using time-honored techniques, ensuring every piece is unique. The brand has gained international recognition, with pieces worn by royalty and celebrities worldwide.',
        'specialties' => ['Handmade Designs', 'Arabic Calligraphy', '18k Gold', 'Diamonds', 'Custom Engraving'],
        'hours' => 'Saturday to Thursday: 10:00 AM - 9:00 PM<br>Friday: 2:00 PM - 9:00 PM',
        'featured_products' => ['Royal Collection', 'Calligraphy Necklaces', 'Pharaoh Inspired Rings', 'Bridal Sets']
    ],
    2 => [
        'name' => 'Damas Jewellery',
        'image' => 'assets/images/shops/damas.jpg',
        'logo_icon' => 'fa-gem',
        'established' => '1907',
        'location' => 'City Stars, Cairo',
        'full_address' => 'City Stars Mall, Omar Ibn El Khattab Street, Heliopolis, Cairo',
        'phone' => '+20 2 2480 1234',
        'email' => 'cairo@damas.com',
        'website' => 'www.damas.com',
        'rating' => 4.8,
        'reviews' => 342,
        'description' => 'Damas is a leading luxury jewelry retailer with over a century of excellence. Known for exquisite diamond collections and exceptional craftsmanship.',
        'long_description' => 'Founded in 1907, Damas has established itself as the region\'s premier jewelry destination. With over 100 showrooms across the Middle East, Damas offers an unparalleled selection of diamond jewelry, gold, and luxury watches. The brand is the official partner of world-renowned brands and carries exclusive collections.',
        'specialties' => ['Diamonds', 'Luxury Watches', 'Bridal Jewelry', 'Gold Collections', 'Custom Designs'],
        'hours' => 'Daily: 10:00 AM - 11:00 PM',
        'featured_products' => ['Diamond Solitaire Rings', 'Luxury Watch Collection', 'Gold Bangle Sets']
    ],
    3 => [
        'name' => 'L\'Azurde Jewelry',
        'image' => 'assets/images/shops/lazurde.jpg',
        'logo_icon' => 'fa-ring',
        'established' => '1980',
        'location' => 'Alexandria',
        'full_address' => '14 Fouad Street, Raml Station, Alexandria',
        'phone' => '+20 3 482 1234',
        'email' => 'info@lazurde.com',
        'website' => 'www.lazurde.com',
        'rating' => 5.0,
        'reviews' => 267,
        'description' => 'Innovative designs blending modern aesthetics with traditional Arabic influences.',
        'long_description' => 'L\'Azurde has been a pioneer in contemporary Arabic jewelry design since 1980. The brand combines modern manufacturing techniques with traditional craftsmanship, creating pieces that appeal to the modern Arab woman. L\'Azurde is known for its innovative use of rose gold, black rhodium, and colored gemstones.',
        'specialties' => ['Rose Gold', 'Black Rhodium', 'Color Gemstones', 'Contemporary Design'],
        'hours' => 'Saturday to Thursday: 10:00 AM - 10:00 PM<br>Friday: 1:00 PM - 10:00 PM',
        'featured_products' => ['Rose Gold Collections', 'Gemstone Necklaces', 'Modern Bridal Sets']
    ],
    4 => [
        'name' => 'Jawhara Jewellery',
        'image' => 'assets/images/shops/jawhara.jpg',
        'logo_icon' => 'fa-necklace',
        'established' => '1988',
        'location' => 'Heliopolis, Cairo',
        'full_address' => '45 El-Hegaz Street, Heliopolis, Cairo',
        'phone' => '+20 2 2417 1234',
        'email' => 'contact@jawhara.com',
        'website' => 'www.jawhara.com',
        'rating' => 5.0,
        'reviews' => 421,
        'description' => 'Family-owned business specializing in unique gold and diamond pieces with Egyptian heritage.',
        'long_description' => 'Jawhara is a family-owned jewelry house that has been serving Egyptian families for over 35 years. Known for exceptional customer service and high-quality craftsmanship, Jawhara specializes in custom designs tailored to individual preferences. The brand is particularly renowned for its engagement rings and wedding bands.',
        'specialties' => ['Custom Engagement Rings', 'Wedding Bands', 'Gold Jewelry', 'Heritage Pieces'],
        'hours' => 'Saturday to Thursday: 11:00 AM - 9:00 PM<br>Friday: Closed',
        'featured_products' => ['Engagement Rings', 'Wedding Sets', 'Gold Necklaces']
    ],
    5 => [
        'name' => 'Tiffany & Co.',
        'image' => 'assets/images/shops/tiffany.jpg',
        'logo_icon' => 'fa-gem',
        'established' => '1837',
        'location' => 'Mall of Arabia',
        'full_address' => 'Mall of Arabia, Juhayna Square, 6th October City, Cairo',
        'phone' => '+20 2 3835 1234',
        'email' => 'cairo@tiffany.com',
        'website' => 'www.tiffany.com',
        'rating' => 5.0,
        'reviews' => 512,
        'description' => 'World-renowned luxury jeweler offering exceptional diamonds and iconic designs.',
        'long_description' => 'Tiffany & Co. is a global luxury jeweler celebrated for its exceptional diamonds, sterling silver, and iconic blue boxes. Founded in New York in 1837, Tiffany has been setting the standard for excellence in jewelry design for nearly two centuries.',
        'specialties' => ['Diamond Engagement Rings', 'Sterling Silver', 'Luxury Watches', 'Iconic Collections'],
        'hours' => 'Daily: 10:00 AM - 11:00 PM',
        'featured_products' => ['Tiffany Setting', 'T Collection', 'HardWear', 'Return to Tiffany']
    ],
    6 => [
        'name' => 'Cairo Jewelry Gallery',
        'image' => 'assets/images/shops/cairo-gallery.jpg',
        'logo_icon' => 'fa-gem',
        'established' => '2005',
        'location' => 'New Cairo',
        'full_address' => 'Point 90 Mall, North Teseen Street, New Cairo',
        'phone' => '+20 2 2614 1234',
        'email' => 'info@cairojewelry.com',
        'website' => 'www.cairojewelry.com',
        'rating' => 5.0,
        'reviews' => 178,
        'description' => 'Premier destination for luxury watches, gold, and custom-made jewelry designs.',
        'long_description' => 'Cairo Jewelry Gallery is a premier destination for discerning jewelry lovers in New Cairo. The gallery features an extensive collection of luxury watches, including Rolex, Cartier, and Omega, alongside stunning gold and diamond jewelry.',
        'specialties' => ['Luxury Watches', 'Custom Design', 'Gold Jewelry', 'Diamond Collections'],
        'hours' => 'Saturday to Thursday: 11:00 AM - 10:00 PM<br>Friday: 2:00 PM - 10:00 PM',
        'featured_products' => ['Rolex Collection', 'Custom Design Service', 'Diamond Necklaces']
    ],
    7 => [
        'name' => 'Cairo Gold',
        'image' => 'assets/images/shops/cairo-gold.jpg',
        'logo_icon' => 'fa-crown',
        'established' => '2010',
        'location' => 'Maadi, Cairo',
        'full_address' => 'Road 9, Maadi, Cairo',
        'phone' => '+20 2 2524 1234',
        'email' => 'info@cairogold.com',
        'website' => 'www.cairogold.com',
        'rating' => 5.0,
        'reviews' => 156,
        'description' => 'Premium gold jewelry with contemporary Egyptian designs.',
        'long_description' => 'Cairo Gold brings a fresh perspective to traditional gold jewelry. The brand focuses on contemporary designs that appeal to modern Egyptians, combining classic gold with innovative concepts.',
        'specialties' => ['18k Gold', '21k Gold', 'Contemporary Design', 'Minimalist Jewelry'],
        'hours' => 'Saturday to Thursday: 10:00 AM - 9:00 PM',
        'featured_products' => ['Minimalist Gold Necklaces', 'Stackable Rings', 'Gold Bangles']
    ],
    8 => [
        'name' => 'Luxor Jewelry',
        'image' => 'assets/images/shops/luxor.jpg',
        'logo_icon' => 'fa-temple',
        'established' => '1975',
        'location' => 'Luxor',
        'full_address' => 'Karnak Temple Street, Luxor',
        'phone' => '+20 95 238 1234',
        'email' => 'info@luxorjewelry.com',
        'website' => 'www.luxorjewelry.com',
        'rating' => 5.0,
        'reviews' => 98,
        'description' => 'Authentic Egyptian jewelry inspired by Pharaonic history.',
        'long_description' => 'Luxor Jewelry captures the magic of ancient Egypt in every piece. Inspired by the temples, hieroglyphics, and treasures of the Pharaohs, each design tells a story of Egypt\'s glorious past.',
        'specialties' => ['Pharaonic Designs', 'Cartouche Jewelry', 'Egyptian Gemstones', 'Traditional Craftsmanship'],
        'hours' => 'Daily: 9:00 AM - 9:00 PM',
        'featured_products' => ['Cartouche Necklaces', 'Scarab Rings', 'Pharaoh Inspired Pendants']
    ]
];

$shop = $shops[$shop_id] ?? null;

if (!$shop) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo htmlspecialchars($shop['name']); ?> - Trusted Partner | Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .font-serif { font-family: 'Playfair Display', serif; }
        
        .partner-header {
            background: linear-gradient(135deg, #fef9f5 0%, #fff5eb 100%);
            border-bottom: 1px solid rgba(249,115,22,0.1);
        }
        
        .shop-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }
        
        .info-card {
            background: white;
            border-radius: 20px;
            border: 1px solid #f0ebe3;
            transition: all 0.3s ease;
        }
        
        .info-card:hover {
            border-color: #f97316;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            letter-spacing: -0.3px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f97316;
            display: inline-block;
        }
        
        .specialty-tag {
            background: #fef3e8;
            color: #ea580c;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .specialty-tag:hover {
            background: #f97316;
            color: white;
        }
        
        .featured-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #f0ebe3;
        }
        
        .featured-item:last-child {
            border-bottom: none;
        }
        
        .featured-item i {
            width: 24px;
            color: #f97316;
            font-size: 14px;
        }
        
        .contact-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0ebe3;
        }
        
        .contact-row:last-child {
            border-bottom: none;
        }
        
        .contact-row i {
            width: 20px;
            color: #f97316;
            font-size: 14px;
        }
        
        .rating-stars {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .rating-stars i {
            color: #fbbf24;
            font-size: 12px;
        }
        
        .verified-badge {
            background: #10b981;
            color: white;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-back {
            background: #1f2937;
            color: white;
            padding: 10px 24px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-back:hover {
            background: #f97316;
            transform: translateY(-2px);
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
        }
        
        .benefit-item i {
            width: 20px;
            color: #10b981;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .shop-image { width: 90px; height: 90px; }
        }
    </style>
</head>
<body class="bg-gray-50">

<?php include 'includes/navbar.php'; ?>

<!-- Partner Header -->
<div class="partner-header py-10">
    <div class="max-w-6xl mx-auto px-5">
        <div class="flex flex-col md:flex-row items-center gap-6">
            <!-- Shop Image -->
            <img src="<?php echo $shop['image']; ?>" alt="<?php echo $shop['name']; ?>" class="shop-image" onerror="this.src='https://placehold.co/120x120/f97316/white?text=Shop'">
            
            <!-- Shop Info -->
            <div class="text-center md:text-left flex-1">
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 mb-2">
                    <h1 class="font-serif text-2xl md:text-3xl font-bold text-gray-800"><?php echo $shop['name']; ?></h1>
                    <span class="verified-badge"><i class="fas fa-check-circle"></i> Verified Partner</span>
                </div>
                
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 text-sm text-gray-500 mb-3">
                    <span><i class="fas fa-map-marker-alt text-orange-500 mr-1"></i> <?php echo $shop['location']; ?></span>
                    <span><i class="far fa-calendar-alt text-orange-500 mr-1"></i> Established <?php echo $shop['established']; ?></span>
                    <div class="flex items-center gap-1">
                        <div class="rating-stars">
                            <?php for($i = 0; $i < floor($shop['rating']); $i++): ?>
                                <i class="fas fa-star"></i>
                            <?php endfor; ?>
                            <?php if($shop['rating'] - floor($shop['rating']) > 0): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php endif; ?>
                        </div>
                        <span class="text-sm text-gray-500"><?php echo $shop['rating']; ?> (<?php echo $shop['reviews']; ?> reviews)</span>
                    </div>
                </div>
                
                <p class="text-gray-600 text-sm max-w-xl"><?php echo $shop['description']; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="max-w-6xl mx-auto px-5 py-10">
    
    <div class="grid md:grid-cols-3 gap-8">
        
        <!-- Main Content - Left Side -->
        <div class="md:col-span-2 space-y-6">
            
            <!-- About Section -->
            <div class="info-card p-6">
                <h2 class="section-title mb-5">About the House</h2>
                <p class="text-gray-600 leading-relaxed text-sm"><?php echo $shop['long_description']; ?></p>
            </div>
            
            <!-- Specialties Section -->
            <div class="info-card p-6">
                <h2 class="section-title mb-5">Areas of Expertise</h2>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($shop['specialties'] as $specialty): ?>
                        <span class="specialty-tag"><?php echo $specialty; ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Featured Collections -->
            <div class="info-card p-6">
                <h2 class="section-title mb-5">Featured Collections</h2>
                <div>
                    <?php foreach ($shop['featured_products'] as $product): ?>
                        <div class="featured-item">
                            <i class="fas fa-gem"></i>
                            <span class="text-gray-700 text-sm"><?php echo $product; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar - Right Side -->
        <div class="space-y-6">
            
            <!-- Contact Card -->
            <div class="info-card p-6">
                <h3 class="font-serif text-lg font-semibold text-gray-800 mb-4">Contact Information</h3>
                <div>
                    <div class="contact-row">
                        <i class="fas fa-map-marker-alt"></i>
                        <span class="text-gray-600 text-sm"><?php echo $shop['full_address']; ?></span>
                    </div>
                    <div class="contact-row">
                        <i class="fas fa-phone"></i>
                        <a href="tel:<?php echo str_replace(' ', '', $shop['phone']); ?>" class="text-gray-600 hover:text-orange-600 text-sm transition"><?php echo $shop['phone']; ?></a>
                    </div>
                    <div class="contact-row">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?php echo $shop['email']; ?>" class="text-gray-600 hover:text-orange-600 text-sm transition"><?php echo $shop['email']; ?></a>
                    </div>
                    <div class="contact-row">
                        <i class="fas fa-globe"></i>
                        <a href="https://<?php echo $shop['website']; ?>" target="_blank" class="text-gray-600 hover:text-orange-600 text-sm transition"><?php echo $shop['website']; ?></a>
                    </div>
                </div>
            </div>
            
            <!-- Business Hours Card -->
            <div class="info-card p-6">
                <h3 class="font-serif text-lg font-semibold text-gray-800 mb-4">Business Hours</h3>
                <div class="text-gray-600 text-sm leading-relaxed">
                    <?php echo $shop['hours']; ?>
                </div>
            </div>
            
            <!-- Why Choose This Shop -->
            <div class="info-card p-6">
                <h3 class="font-serif text-lg font-semibold text-gray-800 mb-4">Why Choose This Shop</h3>
                <div>
                    <div class="benefit-item"><i class="fas fa-check-circle"></i><span class="text-gray-600 text-sm">100% Authentic Products</span></div>
                    <div class="benefit-item"><i class="fas fa-check-circle"></i><span class="text-gray-600 text-sm">Certificate of Authenticity</span></div>
                    <div class="benefit-item"><i class="fas fa-check-circle"></i><span class="text-gray-600 text-sm">Lifetime Warranty on Select Items</span></div>
                    <div class="benefit-item"><i class="fas fa-check-circle"></i><span class="text-gray-600 text-sm">Free Cleaning and Inspection</span></div>
                    <div class="benefit-item"><i class="fas fa-check-circle"></i><span class="text-gray-600 text-sm">Secure Packaging and Delivery</span></div>
                </div>
            </div>
            
            <!-- Back Button -->
            <a href="index.php" class="btn-back w-full justify-center">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
    
    <!-- Related Reports Section -->
    <div class="mt-10 info-card p-6">
        <h2 class="section-title mb-5">Recent Reports in <?php echo explode(',', $shop['location'])[0]; ?></h2>
        <div class="grid md:grid-cols-3 gap-4">
            <?php
            $location_search = explode(',', $shop['location'])[0];
            $stmt = $pdo->prepare("
                SELECT id, title, report_type, location, created_at 
                FROM reports 
                WHERE location LIKE ? AND moderation_status = 'approved'
                ORDER BY created_at DESC 
                LIMIT 3
            ");
            $stmt->execute(['%' . $location_search . '%']);
            $related_reports = $stmt->fetchAll();
            ?>
            <?php if (!empty($related_reports)): ?>
                <?php foreach ($related_reports as $report): ?>
                    <a href="view_report.php?id=<?php echo $report['id']; ?>" class="block p-4 border border-gray-100 rounded-xl hover:border-orange-200 hover:shadow-md transition group">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-xs rounded-full <?php echo $report['report_type'] === 'stolen' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                                <?php echo ucfirst($report['report_type']); ?>
                            </span>
                        </div>
                        <h3 class="font-semibold text-gray-800 text-sm mb-1 group-hover:text-orange-600 transition"><?php echo htmlspecialchars($report['title']); ?></h3>
                        <p class="text-xs text-gray-400"><?php echo timeAgo($report['created_at']); ?></p>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500 text-sm col-span-3 text-center py-6">No recent reports in this area.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>