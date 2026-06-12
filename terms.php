<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Terms of Service - Findex</title>
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
        <h1 class="text-3xl font-bold mb-2">Terms of Service</h1>
        <p class="text-orange-100 text-sm">Last updated: January 2026</p>
    </div>
</section>

<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        <div class="space-y-6 text-gray-600 text-sm leading-relaxed">
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">1. Acceptance of Terms</h2>
                <p>By accessing or using Findex, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our platform.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">2. Eligibility</h2>
                <p>You must be at least 18 years old to use Findex. By using our platform, you represent that you meet this requirement. Shops must provide a valid business license for verification.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">3. User Accounts</h2>
                <p>You are responsible for maintaining the confidentiality of your account credentials. You agree to accept responsibility for all activities that occur under your account.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">4. Verification Requirements</h2>
                <p>All users must complete identity verification. Shops must provide a valid business license. False information may result in account suspension or termination.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">5. Prohibited Conduct</h2>
                <p>You may not post false or misleading reports, submit fraudulent claims, harass other users, or use the platform for any illegal purpose. Violations will result in immediate account termination.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">6. Claims Process</h2>
                <p>Claims require documented evidence of ownership. False claims may result in account suspension. Findex reserves the right to mediate disputes and make final decisions on escalated claims.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">7. Subscription and Payments</h2>
                <p>Basic features are free for all users. Shops may subscribe to paid plans for additional features. Subscriptions auto-renew monthly and can be canceled at any time.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">8. Content Ownership</h2>
                <p>You retain ownership of content you post. By posting, you grant Findex a license to display and use your content to provide our services.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">9. Limitation of Liability</h2>
                <p>Findex is a platform connecting users. We are not responsible for the actual recovery of items or for disputes between users beyond our moderation role.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">10. Account Termination</h2>
                <p>We may suspend or terminate your account for violation of these terms. You may also delete your account at any time by contacting support.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">11. Changes to Terms</h2>
                <p>We may modify these terms at any time. Continued use of the platform constitutes acceptance of the modified terms.</p>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">12. Contact</h2>
                <p>For questions about these terms, contact us at support@findex.com.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>