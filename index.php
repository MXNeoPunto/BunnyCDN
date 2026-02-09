<?php

add_action('media_upload', 'bunny_cdn_upload_handler', 10, 2);
add_action('media_delete', 'bunny_cdn_delete_handler', 10, 1);
add_filter('admin_menu_items', 'bunny_cdn_add_menu');

function bunny_cdn_add_menu($items) {
    $items[] = ['label' => 'Bunny CDN', 'url' => '/admin/plugin-page.php?plugin=bunny-cdn', 'icon' => 'cloud'];
    return $items;
}

function bunny_cdn_upload_handler($mediaId, $localPath) {
    $apiKey = get_option('bunny_api_key');
    $storageZone = get_option('bunny_storage_zone');
    $pullZone = get_option('bunny_pull_zone');
    $region = get_option('bunny_region', 'de');
    
    if (!$apiKey || !$storageZone) return;

    $fileName = basename($localPath);
    $originalPath = $localPath;
    
    // WebP Conversion
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
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
            $success = imagewebp($image, $newPath, 80);
            imagedestroy($image);
            
            if ($success && file_exists($newPath) && filesize($newPath) > 0) {
                $localPath = $newPath;
                $fileName = $newFileName;
            }
        }
    }

    // Determine Host
    $host = 'storage.bunnycdn.com';
    if ($region == 'ny') $host = 'ny.storage.bunnycdn.com';
    else if ($region == 'la') $host = 'la.storage.bunnycdn.com';
    else if ($region == 'sg') $host = 'sg.storage.bunnycdn.com';
    else if ($region == 'sy') $host = 'syd.storage.bunnycdn.com';

    $url = "https://{$host}/{$storageZone}/{$fileName}";
    
    if (!file_exists($localPath)) {
        error_log("Bunny CDN Error: File not found: $localPath");
        throw new Exception("Error: File not found at $localPath");
    }

    $fileStream = fopen($localPath, 'r');
    if (!$fileStream) {
        error_log("Bunny CDN Error: Could not open file: $localPath");
        throw new Exception("Error: Could not open file for reading.");
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_UPLOAD, 1);
    curl_setopt($ch, CURLOPT_INFILE, $fileStream);
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localPath));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "AccessKey: {$apiKey}",
        "Content-Type: application/octet-stream"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fileStream);
        error_log("Bunny CDN cURL Error: " . $error);
        throw new Exception("Error connecting to Bunny CDN: " . $error);
    }

    curl_close($ch);
    fclose($fileStream);
    
    if ($httpCode == 201) {
        global $pdo;
        $cdnUrl = rtrim($pullZone, '/') . '/' . $fileName;
        $stmt = $pdo->prepare("UPDATE media SET cdn_url = ?, file_name = ? WHERE id = ?");
        $stmt->execute([$cdnUrl, $fileName, $mediaId]);
        
        $keepLocal = get_option('bunny_keep_local', 0);

        // Remove local WebP if created
        if ($localPath !== $originalPath) {
             @unlink($localPath); // Always remove temp WebP if created just for upload
        }
        
        // Remove original local file if not keeping local
        if (!$keepLocal) {
            @unlink($originalPath);
        }
    } else {
        error_log("Bunny CDN Upload Failed: HTTP $httpCode - Response: $response");
        throw new Exception("Error uploading to Bunny CDN. HTTP Code: $httpCode");
    }
}

function bunny_cdn_delete_handler($mediaId) {
    global $pdo;
    $apiKey = get_option('bunny_api_key');
    $storageZone = get_option('bunny_storage_zone');
    $region = get_option('bunny_region', 'de');
    
    if (!$apiKey || !$storageZone) return;
    
    $stmt = $pdo->prepare("SELECT file_name FROM media WHERE id = ?");
    $stmt->execute([$mediaId]);
    $fileName = $stmt->fetchColumn();
    
    if ($fileName) {
        $host = 'storage.bunnycdn.com';
        if ($region == 'ny') $host = 'ny.storage.bunnycdn.com';
        else if ($region == 'la') $host = 'la.storage.bunnycdn.com';
        else if ($region == 'sg') $host = 'sg.storage.bunnycdn.com';
        else if ($region == 'sy') $host = 'syd.storage.bunnycdn.com';
        
        $url = "https://{$host}/{$storageZone}/{$fileName}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "AccessKey: {$apiKey}"
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
