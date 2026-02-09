<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    update_option('bunny_api_key', $_POST['api_key']);
    update_option('bunny_storage_zone', $_POST['storage_zone']);
    update_option('bunny_pull_zone', $_POST['pull_zone']);
    update_option('bunny_region', $_POST['region']); // de, ny, sg, la
    update_option('bunny_keep_local', isset($_POST['keep_local']) ? 1 : 0);
    
    $message = "Configuración guardada.";
}

$apiKey = get_option('bunny_api_key', '');
$storageZone = get_option('bunny_storage_zone', '');
$pullZone = get_option('bunny_pull_zone', ''); // URL
$region = get_option('bunny_region', 'de');
$keepLocal = get_option('bunny_keep_local', 0);
?>

<div class="mb-6">
    <h2 class="text-xl font-bold mb-4">Configuración de Bunny CDN</h2>
    
    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-4 max-w-lg">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div>
            <label class="block text-gray-700 font-bold mb-2">API Key (Storage)</label>
            <input type="password" name="api_key" value="<?php echo htmlspecialchars($apiKey); ?>" class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block text-gray-700 font-bold mb-2">Storage Zone Name</label>
            <input type="text" name="storage_zone" value="<?php echo htmlspecialchars($storageZone); ?>" class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="block text-gray-700 font-bold mb-2">Pull Zone URL (Endpoint)</label>
            <input type="text" name="pull_zone" value="<?php echo htmlspecialchars($pullZone); ?>" class="w-full border rounded px-3 py-2" placeholder="https://my-zone.b-cdn.net">
        </div>
        
        <div>
            <label class="block text-gray-700 font-bold mb-2">Región de Almacenamiento</label>
            <select name="region" class="w-full border rounded px-3 py-2">
                <option value="de" <?php echo $region === 'de' ? 'selected' : ''; ?>>Falkenstein (DE) - Default</option>
                <option value="ny" <?php echo $region === 'ny' ? 'selected' : ''; ?>>New York (US)</option>
                <option value="la" <?php echo $region === 'la' ? 'selected' : ''; ?>>Los Angeles (US)</option>
                <option value="sg" <?php echo $region === 'sg' ? 'selected' : ''; ?>>Singapore (SG)</option>
                <option value="sy" <?php echo $region === 'sy' ? 'selected' : ''; ?>>Sydney (AU)</option>
            </select>
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
