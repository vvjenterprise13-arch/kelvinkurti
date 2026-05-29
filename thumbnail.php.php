<?php
// thumbnail.php

// Settings
$target_width = 400; // નવી ઇમેજની પહોળાઈ (તમે 300 કે 400 રાખી શકો)
$quality = 80;      // ક્વોલિટી (75-85 સારી ગણાય)
$cache_dir = 'assets/uploads/thumbs/';
$source_dir = 'assets/uploads/';

// Get the requested image file
$image_file = isset($_GET['src']) ? basename($_GET['src']) : '';

if (empty($image_file)) {
    // If no image is requested, exit
    header('HTTP/1.1 400 Bad Request');
    exit();
}

// Define paths
$source_path = $source_dir . $image_file;
$thumb_path = $cache_dir . $image_file;

// Check if thumbnail already exists in cache
if (file_exists($thumb_path)) {
    // Serve the cached thumbnail
    header('Content-Type: ' . mime_content_type($thumb_path));
    readfile($thumb_path);
    exit();
}

// Check if original image exists
if (!file_exists($source_path)) {
    header('HTTP/1.1 404 Not Found');
    exit();
}

// === If thumbnail doesn't exist, create it ===

// Get image info
$info = getimagesize($source_path);
$mime = $info['mime'];

// Create a new image from the source file
switch ($mime) {
    case 'image/jpeg':
        $image = imagecreatefromjpeg($source_path);
        break;
    case 'image/png':
        $image = imagecreatefrompng($source_path);
        break;
    case 'image/gif':
        $image = imagecreatefromgif($source_path);
        break;
    default:
        // Unsupported type, serve original file
        header('Content-Type: ' . mime_content_type($source_path));
        readfile($source_path);
        exit();
}

// Get original dimensions
$width_orig = imagesx($image);
$height_orig = imagesy($image);

// Calculate new height to maintain aspect ratio
$ratio_orig = $width_orig / $height_orig;
$target_height = $target_width / $ratio_orig;

// Create a new true color image canvas
$image_p = imagecreatetruecolor($target_width, $target_height);

// Handle transparency for PNGs
if ($mime == "image/png") {
    imagealphablending($image_p, false);
    imagesavealpha($image_p, true);
    $transparent = imagecolorallocatealpha($image_p, 255, 255, 255, 127);
    imagefilledrectangle($image_p, 0, 0, $target_width, $target_height, $transparent);
}

// Resize the image
imagecopyresampled($image_p, $image, 0, 0, 0, 0, $target_width, $target_height, $width_orig, $height_orig);

// Save the new thumbnail to the cache directory
switch ($mime) {
    case 'image/jpeg':
        imagejpeg($image_p, $thumb_path, $quality);
        break;
    case 'image/png':
        $png_quality = round(($quality / 100) * 9);
        imagepng($image_p, $thumb_path, $png_quality);
        break;
    case 'image/gif':
        imagegif($image_p, $thumb_path);
        break;
}

// Free up memory
imagedestroy($image);
imagedestroy($image_p);

// Serve the newly created thumbnail
header('Content-Type: ' . mime_content_type($thumb_path));
readfile($thumb_path);
exit();
?>