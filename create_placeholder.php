<?php
// Create receipts directory
if (!file_exists('uploads/receipts')) {
    mkdir('uploads/receipts', 0777, true);
}

// Create a simple placeholder image using GD
$img = imagecreate(200, 100);
$bg = imagecolorallocate($img, 255, 255, 255);
$text_color = imagecolorallocate($img, 0, 0, 0);
imagestring($img, 5, 50, 40, "Payment Receipt", $text_color);
imagepng($img, 'uploads/receipts/placeholder.jpg');
imagedestroy($img);

echo "Placeholder receipt image created!";
?>