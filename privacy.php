<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Privacy Policy - Findex</title>
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
        body, p, li, a, span, div { font-size: 14px; }
        .text-xs { font-size: 10px; }
        .text-sm { font-size: 12px; }
        footer p, footer li, footer a { font-size: 11px; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<section class="orange-gradient text-white py-12">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h1 class="text-3xl font-bold mb-2">Privacy Policy</h1>
        <p class="text-orange-100 text-sm">Last updated: January 2026</p>
    </div>
</section>

<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        <div class="space-y-6 text-gray-600 text-sm leading-relaxed">
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">1. Information We Collect</h2>
                <p>We collect information you provide directly to us, including name, email address, phone number, and identification documents for verification purposes. When you post a report, we collect details about the jewelry item including photos, location, and incident date.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">2. How We Use Your Information</h2>
                <p>We use your information to verify your identity, process reports and claims, match lost items with found items, communicate with you about your reports, and improve our services.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">3. Information Sharing</h2>
                <p>We do not sell your personal information. We only share your contact information with the other party after a claim has been formally approved. Your identification documents are only accessible to authorized verification staff.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">4. Data Security</h2>
                <p>We take reasonable measures to protect your personal information from unauthorized access, alteration, or destruction. All sensitive data is encrypted and stored securely.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">5. Your Rights</h2>
                <p>You may request access to, correction of, or deletion of your personal information at any time by contacting our support team. You may also request to close your account and have your data removed.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">6. Cookies</h2>
                <p>We use cookies only for essential site functionality and to improve your browsing experience. You may disable cookies in your browser settings.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">7. Changes to This Policy</h2>
                <p>We may update this privacy policy from time to time. We will notify you of any material changes by posting the new policy on this page.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">8. Contact Us</h2>
                <p>If you have any questions about this privacy policy, please contact us at support@findex.com or call +20 123 456 789.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>