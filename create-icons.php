<?php
// Simple script to generate PWA icons
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

foreach ($sizes as $size) {
    $svg = '<?xml version="1.0" encoding="UTF-8"?>
    <svg width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '" xmlns="http://www.w3.org/2000/svg">
        <rect width="' . $size . '" height="' . $size . '" fill="#000000" rx="' . ($size * 0.1) . '"/>
        <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="' . ($size * 0.3) . '" font-weight="bold" fill="white" text-anchor="middle" dominant-baseline="central">🏋️</text>
        <text x="50%" y="75%" font-family="Arial, sans-serif" font-size="' . ($size * 0.1) . '" fill="white" text-anchor="middle" dominant-baseline="central">TRAINING</text>
    </svg>';
    
    // Convert SVG to PNG (requires ImageMagick or similar)
    // For now, we'll just save as SVG and rename to PNG for basic functionality
    file_put_contents("icon-{$size}x{$size}.png", $svg);
}

echo "Icons created! (Note: These are SVG files renamed as PNG. For production, convert to actual PNG files)";
?>
