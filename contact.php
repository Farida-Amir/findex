<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Contact Us - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%); }
        
        /* Professional font sizing */
        html { font-size: 14px; }
        h1 { font-size: 28px; font-weight: 700; }
        h2 { font-size: 22px; font-weight: 600; }
        h3 { font-size: 18px; font-weight: 600; }
        body, p, li, a, span, div, label { font-size: 14px; }
        input, select, textarea, button { font-size: 13px; }
        .text-xs { font-size: 10px; }
        .text-sm { font-size: 12px; }
        .text-base { font-size: 14px; }
        footer p, footer li, footer a { font-size: 11px; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<section class="orange-gradient text-white py-12">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h1 class="text-3xl font-bold mb-2">Contact Us</h1>
        <p class="text-orange-100 text-sm">We'd love to hear from you</p>
    </div>
</section>

<div class="max-w-5xl mx-auto px-4 py-12">
    <div class="grid md:grid-cols-2 gap-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Get in touch</h2>
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 orange-gradient rounded-full flex items-center justify-center">
                        <i class="fas fa-envelope text-white text-sm"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Email</p>
                        <p class="text-gray-800 text-sm">support@findex.com</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 orange-gradient rounded-full flex items-center justify-center">
                        <i class="fas fa-phone text-white text-sm"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Phone</p>
                        <p class="text-gray-800 text-sm">+20 123 456 789</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 orange-gradient rounded-full flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-white text-sm"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Address</p>
                        <p class="text-gray-800 text-sm">Cairo, Egypt</p>
                    </div>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t">
                <p class="text-xs text-gray-500 mb-2">Business Hours</p>
                <p class="text-sm text-gray-700">Saturday - Thursday: 9 AM - 9 PM</p>
                <p class="text-sm text-gray-700">Friday: 2 PM - 8 PM</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Send us a message</h2>
            <form>
                <input type="text" placeholder="Your name" class="w-full mb-3 px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                <input type="email" placeholder="Your email" class="w-full mb-3 px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                <input type="text" placeholder="Subject" class="w-full mb-3 px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:border-orange-500">
                <textarea rows="4" placeholder="Your message" class="w-full mb-4 px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:border-orange-500"></textarea>
                <button type="submit" class="orange-gradient text-white px-5 py-2 rounded-full text-sm font-semibold w-full">Send Message</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>