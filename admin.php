<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    update_option('r2_endpoint', $_POST['endpoint']);
    update_option('r2_access_key', $_POST['access_key']);
    update_option('r2_secret_key', $_POST['secret_key']);
    update_option('r2_bucket_name', $_POST['bucket_name']);
    update_option('r2_custom_domain', $_POST['custom_domain']);
    update_option('r2_keep_local', isset($_POST['keep_local']) ? 1 : 0);
    
    $message = "Configuración guardada.";
}

$endpoint = get_option('r2_endpoint', '');
$accessKey = get_option('r2_access_key', '');
$secretKey = get_option('r2_secret_key', '');
$bucketName = get_option('r2_bucket_name', '');
$customDomain = get_option('r2_custom_domain', '');
$keepLocal = get_option('r2_keep_local', 0);
?>

<div class="mb-6">
    <h2 class="text-xl font-bold mb-4">Configuración de Cloudflare R2</h2>
    
    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-4 max-w-lg">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div>
            <label class="block text-gray-700 font-bold mb-2">R2 Endpoint URL</label>
            <input type="text" name="endpoint" value="<?php echo htmlspecialchars($endpoint); ?>" class="w-full border rounded px-3 py-2" placeholder="https://<accountid>.r2.cloudflarestorage.com">
            <p class="text-xs text-gray-500 mt-1">El endpoint S3 API de tu bucket R2.</p>
        </div>

        <div>
            <label class="block text-gray-700 font-bold mb-2">Access Key ID</label>
            <input type="text" name="access_key" value="<?php echo htmlspecialchars($accessKey); ?>" class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block text-gray-700 font-bold mb-2">Secret Access Key</label>
            <input type="password" name="secret_key" value="<?php echo htmlspecialchars($secretKey); ?>" class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block text-gray-700 font-bold mb-2">Bucket Name</label>
            <input type="text" name="bucket_name" value="<?php echo htmlspecialchars($bucketName); ?>" class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block text-gray-700 font-bold mb-2">Custom Domain (Public URL)</label>
            <input type="text" name="custom_domain" value="<?php echo htmlspecialchars($customDomain); ?>" class="w-full border rounded px-3 py-2" placeholder="https://cdn.misite.com">
            <p class="text-xs text-gray-500 mt-1">La URL pública mapped a tu bucket R2.</p>
        </div>

        <div class="flex items-center space-x-2">
            <input type="checkbox" name="keep_local" id="keep_local" value="1" <?php echo $keepLocal ? 'checked' : ''; ?>>
            <label for="keep_local" class="text-gray-700 font-medium">Mantener archivos locales después de subir</label>
        </div>

        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
            Guardar Configuración
        </button>
    </form>
</div>
