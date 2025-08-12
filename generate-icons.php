<?php
// Generate simple PNG icons for PWA
header('Content-Type: text/html');

$size = $_GET['size'] ?? 192;
$size = (int)$size;

// Create a simple black square with white text
$image = imagecreate($size, $size);

// Colors
$black = imagecolorallocate($image, 0, 0, 0);
$white = imagecolorallocate($image, 255, 255, 255);

// Fill background
imagefill($image, 0, 0, $black);

// Add text
$font_size = $size / 8;
$text = "T";
$text_box = imagettfbbox($font_size, 0, __DIR__ . '/arial.ttf', $text);
$text_width = $text_box[4] - $text_box[0];
$text_height = $text_box[1] - $text_box[7];
$x = ($size - $text_width) / 2;
$y = ($size - $text_height) / 2 + $text_height;

// Use imagestring if TTF font not available
imagestring($image, 5, $size/2 - 10, $size/2 - 10, "T", $white);

// Output as PNG
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
?>
