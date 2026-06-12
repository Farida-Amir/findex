<?php
/**
 * Mailer Functions - Findex Platform
 * Handles all email notifications and communications
 */

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com'); // Change to your SMTP server
define('SMTP_PORT', 587);
define('SMTP_USER', ''); // Set your email
define('SMTP_PASS', ''); // Set your password
define('SMTP_FROM_EMAIL', 'noreply@findex.com');
define('SMTP_FROM_NAME', 'Findex - Lost & Stolen Jewelry Recovery');

/**
 * Send email using PHP mail() function (fallback)
 * For production, switch to PHPMailer or similar library
 */
function sendEmail($to, $subject, $message, $isHTML = true) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $contentType = $isHTML ? "text/html; charset=UTF-8" : "text/plain; charset=UTF-8";
    $headers .= "Content-type: {$contentType}" . "\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">" . "\r\n";
    $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Send welcome email to new user
 */
function sendWelcomeEmail($email, $name, $userType = 'user') {
    $subject = "Welcome to Findex - Lost & Stolen Jewelry Recovery";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { color: white; margin: 0; font-size: 28px; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { background: #f97316; color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; display: inline-block; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #999; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✨ Welcome to Findex ✨</h1>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($name) . ",</h2>
                <p>Thank you for joining Findex - Egypt's leading platform for recovering lost and stolen jewelry!</p>
                
                <h3>What you can do:</h3>
                <ul>
                    <li>📝 Report lost or stolen jewelry items</li>
                    <li>🔍 Search our database of recovered items</li>
                    <li>🏪 Connect with verified jewelry shops</li>
                    <li>🤖 Use AI-powered image enhancement</li>
                </ul>";
    
    if ($userType === 'shop') {
        $message .= "
                <h3>For Shop Owners:</h3>
                <ul>
                    <li>💎 Get verified and earn customer trust</li>
                    <li>📊 Track your shop's performance</li>
                    <li>⚡ Boost your reports for more visibility</li>
                </ul>
                <p><strong>Note:</strong> Your shop account is pending admin approval. You'll receive an email once approved.</p>";
    }
    
    $message .= "
                <center>
                    <a href='" . SITE_URL . "login.php' class='button'>Get Started →</a>
                </center>
                <p>If you have any questions, reply to this email or contact our support team.</p>
                <p>Best regards,<br><strong>The Findex Team</strong></p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Findex - Recover Lost & Stolen Jewelry. All rights reserved.</p>
                <p><small>This is an automated message, please do not reply directly to this email.</small></p>
            </div>
        </div>
    </body>
    </html>";
    
    return sendEmail($email, $subject, $message, true);
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($email, $name, $resetToken) {
    $resetLink = SITE_URL . "reset_password.php?token=" . $resetToken;
    $subject = "Reset Your Findex Account Password";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { color: white; margin: 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { background: #f97316; color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; display: inline-block; margin: 20px 0; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Password Reset Request</h1>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($name) . ",</h2>
                <p>We received a request to reset your Findex account password.</p>
                
                <center>
                    <a href='{$resetLink}' class='button'>Reset Password →</a>
                </center>
                
                <div class='warning'>
                    <strong>⚠️ This link expires in 1 hour</strong><br>
                    If you didn't request this, please ignore this email. Your password will remain unchanged.
                </div>
                
                <p>Or copy and paste this link into your browser:<br>
                <small style='color: #666; word-break: break-all;'>{$resetLink}</small></p>
                
                <hr style='margin: 30px 0;'>
                <p style='font-size: 12px; color: #999;'>For security, never share this link with anyone. Findex will never ask for your password.</p>
            </div>
        </div>
    </body>
    </html>";
    
    return sendEmail($email, $subject, $message, true);
}

/**
 * Send claim notification to shop owner
 */
function sendClaimNotificationEmail($shopEmail, $shopName, $claimantName, $reportTitle, $claimId) {
    $claimLink = SITE_URL . "view_claim.php?id=" . $claimId;
    $subject = "New Claim on Your Report - " . $reportTitle;
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); padding: 20px; text-align: center; border-radius: 10px 10px 0 0; color: white; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { background: #f97316; color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; display: inline-block; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>📋 New Claim Submitted</h2>
            </div>
            <div class='content'>
                <h3>Dear " . htmlspecialchars($shopName) . ",</h3>
                <p>A user has submitted a claim on your report: <strong>" . htmlspecialchars($reportTitle) . "</strong></p>
                
                <p><strong>Claimant:</strong> " . htmlspecialchars($claimantName) . "<br>
                <strong>Status:</strong> Pending Review</p>
                
                <center>
                    <a href='{$claimLink}' class='button'>Review Claim →</a>
                </center>
                
                <p>Please review the claim details and respond within 7 days.</p>
            </div>
        </div>
    </body>
    </html>";
    
    return sendEmail($shopEmail, $subject, $message, true);
}

/**
 * Send claim status update to claimant
 */
function sendClaimStatusEmail($claimantEmail, $claimantName, $reportTitle, $status, $message_text = null) {
    $subject = "Claim Status Update - " . $reportTitle;
    
    $statusText = ucfirst($status);
    $statusColor = $status === 'approved' ? '#28a745' : ($status === 'rejected' ? '#dc3545' : '#ffc107');
    $statusIcon = $status === 'approved' ? '✅' : ($status === 'rejected' ? '❌' : '⏳');
    
    $additionalInfo = '';
    if ($status === 'approved') {
        $additionalInfo = "<p><strong>Next steps:</strong> Please contact the shop to arrange pickup of your item. You'll receive further instructions via email.</p>";
    } elseif ($status === 'rejected' && $message_text) {
        $additionalInfo = "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 20px 0;'><strong>Reason for rejection:</strong><br>" . htmlspecialchars($message_text) . "</div>";
    }
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); padding: 20px; text-align: center; border-radius: 10px 10px 0 0; color: white; }
            .status { text-align: center; padding: 20px; font-size: 48px; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>{$statusIcon} Claim {$statusText}</h2>
            </div>
            <div class='content'>
                <h3>Hello " . htmlspecialchars($claimantName) . ",</h3>
                <p>Your claim for <strong>" . htmlspecialchars($reportTitle) . "</strong> has been <strong style='color: {$statusColor};'>{$statusText}</strong>.</p>
                
                {$additionalInfo}
                
                <p><a href='" . SITE_URL . "my_reports.php' style='color: #f97316;'>View your claims dashboard →</a></p>
                
                <hr>
                <p style='font-size: 12px; color: #999;'>Have questions? Contact our support team.</p>
            </div>
        </div>
    </body>
    </html>";
    
    return sendEmail($claimantEmail, $subject, $message, true);
}

/**
 * Send shop verification notification
 */
function sendShopVerificationEmail($shopEmail, $shopName, $isApproved, $message = null) {
    $subject = $isApproved ? "Your Shop is Verified! 🎉" : "Shop Verification Update";
    
    if ($isApproved) {
        $content = "
            <h3>Congratulations " . htmlspecialchars($shopName) . "!</h3>
            <p>Your shop has been verified on Findex! 🎉</p>
            <p><strong>Benefits you now have:</strong></p>
            <ul>
                <li>✓ Verified badge on your profile</li>
                <li>✓ Increased customer trust</li>
                <li>✓ Higher visibility in search results</li>
                <li>✓ Priority support</li>
            </ul>
            <center><a href='" . SITE_URL . "dashboard_shop.php' style='background: #f97316; color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; display: inline-block;'>Go to Dashboard →</a></center>";
    } else {
        $content = "
            <h3>Hello " . htmlspecialchars($shopName) . ",</h3>
            <p>Thank you for submitting your shop verification documents.</p>
            <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                <strong>Status:</strong> " . ($message ?: "Under Review") . "
            </div>
            <p>We'll notify you once your verification is complete. This usually takes 2-3 business days.</p>";
    }
    
    $message_html = "
    <!DOCTYPE html>
    <html>
    <head><style>body{font-family:Arial;}</style></head>
    <body style='padding:20px;max-width:600px;margin:0 auto;'>
        <div style='background:linear-gradient(135deg,#f97316,#ea580c);padding:20px;text-align:center;border-radius:10px 10px 0 0;color:white;'>
            <h2>🏪 Shop Verification</h2>
        </div>
        <div style='background:#f9f9f9;padding:30px;border-radius:0 0 10px 10px;'>
            {$content}
            <hr style='margin:30px 0;'>
            <p style='font-size:12px;color:#999;'>Findex - Egypt's Luxury Recovery Platform</p>
        </div>
    </body>
    </html>";
    
    return sendEmail($shopEmail, $subject, $message_html, true);
}

/**
 * Send report status update notification
 */
function sendReportStatusEmail($userEmail, $userName, $reportTitle, $newStatus, $reason = null) {
    $subject = "Report Status Update - " . $reportTitle;
    
    $statusMessages = [
        'active' => "Your report has been published and is now visible to all shops and users.",
        'resolved' => "Great news! Your report has been marked as resolved. " . ($reason ?: "The item has been recovered."),
        'closed' => "Your report has been closed. " . ($reason ?: "Contact support for more information.")
    ];
    
    $message_html = "
    <!DOCTYPE html>
    <html>
    <head><style>body{font-family:Arial;}</style></head>
    <body style='padding:20px;max-width:600px;margin:0 auto;'>
        <div style='background:linear-gradient(135deg,#f97316,#ea580c);padding:20px;text-align:center;border-radius:10px 10px 0 0;color:white;'>
            <h2>📄 Report Updated</h2>
        </div>
        <div style='background:#f9f9f9;padding:30px;border-radius:0 0 10px 10px;'>
            <h3>Hello " . htmlspecialchars($userName) . ",</h3>
            <p>Your report <strong>\"{$reportTitle}\"</strong> has been updated to: <strong style='color:#f97316;text-transform:uppercase;'>{$newStatus}</strong></p>
            <p>" . ($statusMessages[$newStatus] ?? "Status changed to {$newStatus}") . "</p>
            <p><a href='" . SITE_URL . "view_report.php?id={$reportId}' style='color:#f97316;'>View your report →</a></p>
        </div>
    </body>
    </html>";
    
    return sendEmail($userEmail, $subject, $message_html, true);
}
?>