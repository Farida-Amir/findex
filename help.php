<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Help Center - Findex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/global.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .orange-gradient { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        body { background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%); }
        .faq-item { border-bottom: 1px solid #e5e7eb; }
        .faq-item:last-child { border-bottom: none; }
        
        /* Professional font sizing */
        html { font-size: 14px; }
        h1 { font-size: 28px; font-weight: 700; }
        h2 { font-size: 22px; font-weight: 600; }
        h3 { font-size: 18px; font-weight: 600; }
        body, p, li, a, span, div { font-size: 14px; }
        .text-xs { font-size: 10px; }
        .text-sm { font-size: 12px; }
        .text-base { font-size: 14px; }
        .text-lg { font-size: 16px; }
        footer p, footer li, footer a { font-size: 11px; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<section class="orange-gradient text-white py-12">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h1 class="text-3xl font-bold mb-2">Help Center</h1>
        <p class="text-orange-100 text-sm">Find answers to common questions</p>
    </div>
</section>

<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="divide-y">
            <div class="faq-item p-6">
                <h3 class="font-semibold text-gray-800 mb-2 text-base">How do I report a lost item?</h3>
                <p class="text-gray-600 text-sm">Click the "Report" button in the navigation menu, fill in the details about your item, and submit. It's completely free.</p>
            </div>
            <div class="faq-item p-6">
                <h3 class="font-semibold text-gray-800 mb-2 text-base">How do I claim an item I found?</h3>
                <p class="text-gray-600 text-sm">Search for the item, click "View Details", then click "Claim This Item". Provide evidence of ownership and submit.</p>
            </div>
            <div class="faq-item p-6">
                <h3 class="font-semibold text-gray-800 mb-2 text-base">How long does verification take?</h3>
                <p class="text-gray-600 text-sm">Regular users: 24-48 hours. Shops: 2-3 business days.</p>
            </div>
            <div class="faq-item p-6">
                <h3 class="font-semibold text-gray-800 mb-2 text-base">Is Findex free?</h3>
                <p class="text-gray-600 text-sm">Yes! Posting reports, searching, and claiming items is completely free for regular users.</p>
            </div>
            <div class="faq-item p-6">
                <h3 class="font-semibold text-gray-800 mb-2 text-base">How do I contact support?</h3>
                <p class="text-gray-600 text-sm">Email us at support@findex.com or call +20 123 456 789. We're available Saturday to Thursday, 9 AM - 9 PM.</p>
            </div>
            <div class="faq-item p-6">
                <h3 class="font-semibold text-gray-800 mb-2 text-base">Can I edit my report after posting?</h3>
                <p class="text-gray-600 text-sm">Yes. Go to your dashboard, find the report, and click "Edit".</p>
            </div>
            <div class="faq-item p-6">
                <h3 class="font-semibold text-gray-800 mb-2 text-base">What happens when I approve a claim?</h3>
                <p class="text-gray-600 text-sm">The claimant's contact information is shared with you so you can arrange the return.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>