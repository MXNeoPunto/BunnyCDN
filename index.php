<?php

require_once __DIR__ . '/S3Client.php';

add_action('media_upload', 'r2_upload_handler', 10, 2);
add_action('media_delete', 'r2_delete_handler', 10, 1);
add_filter('admin_menu_items', 'r2_add_menu');

function r2_add_menu($items) {
    $items[] = ['label' => 'Cloudflare R2', 'url' => '/admin/plugin-page.php?plugin=cloudflare-r2', 'icon' => 'cloud'];
    return $items;
}

function r2_upload_handler($mediaId, $localPath) {
    $endpoint = get_option('r2_endpoint');
    $accessKey = get_option('r2_access_key');
    $secretKey = get_option('r2_secret_key');
    $bucketName = get_option('r2_bucket_name');
    $customDomain = get_option('r2_custom_domain');
    
    if (!$endpoint || !$accessKey || !$secretKey || !$bucketName) return;

    $fileName = basename($localPath);
    $originalPath = $localPath;
    
    // WebP Conversion
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $mimeType = mime_content_type($localPath);

    if (defined('WEBP_CONVERTER_ACTIVE') && in_array($ext, ['jpg', 'jpeg', 'png']) && function_exists('imagewebp')) {
        $image = null;
        if ($ext == 'jpeg' || $ext == 'jpg') $image = @imagecreatefromjpeg($localPath);
        elseif ($ext == 'png') $image = @imagecreatefrompng($localPath);
        
        if ($image) {
            $newFileName = pathinfo($fileName, PATHINFO_FILENAME) . '.webp';
            $newPath = dirname($localPath) . '/' . $newFileName;
            
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            imagewebp($image, $newPath, 80);
            imagedestroy($image);
            
            $localPath = $newPath;
            $fileName = $newFileName;
            $mimeType = 'image/webp';
        }
    }

    $s3 = new S3Client($endpoint, $accessKey, $secretKey, $bucketName);
    
    if ($s3->putObject($fileName, $localPath, $mimeType)) {
        global $pdo;
        $cdnUrl = rtrim($customDomain, '/') . '/' . $fileName;
        $stmt = $pdo->prepare("UPDATE media SET cdn_url = ?, file_name = ? WHERE id = ?");
        $stmt->execute([$cdnUrl, $fileName, $mediaId]);
        
        $keepLocal = get_option('r2_keep_local', 0);

        // Remove local WebP if created
        if ($localPath !== $originalPath) {
             @unlink($localPath); // Always remove temp WebP if created just for upload
        }
        
        // Remove original local file if not keeping local
        if (!$keepLocal) {
            @unlink($originalPath);
        }
    }
}

function r2_delete_handler($mediaId) {
    global $pdo;
    $endpoint = get_option('r2_endpoint');
    $accessKey = get_option('r2_access_key');
    $secretKey = get_option('r2_secret_key');
    $bucketName = get_option('r2_bucket_name');
    
    if (!$endpoint || !$accessKey || !$secretKey || !$bucketName) return;
    
    $stmt = $pdo->prepare("SELECT file_name FROM media WHERE id = ?");
    $stmt->execute([$mediaId]);
    $fileName = $stmt->fetchColumn();
    
    if ($fileName) {
        $s3 = new S3Client($endpoint, $accessKey, $secretKey, $bucketName);
        $s3->deleteObject($fileName);
    }
}
