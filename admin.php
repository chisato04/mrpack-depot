<?php
session_start();
// --- CONFIGURATION ---
$metadataFile = 'metadata.json';
$modpacksDir = 'modpacks';
$modlistsDir = 'modlists';
$predefined_loaders = ["", "Forge", "NeoForge", "Fabric", "Quilt", "Vanilla"];
$predefined_versions = ["", "1.21.1", "1.20.1", "1.19.2", "1.18.2", "1.16.5", "1.12.2", "1.7.10"];

// --- INITIALIZATION ---
if (!is_dir($modpacksDir)) mkdir($modpacksDir, 0755, true);
if (!is_dir($modlistsDir)) mkdir($modlistsDir, 0755, true);
$metadata = json_decode(@file_get_contents($metadataFile), true) ?? [];
$message = $_SESSION['admin_message'] ?? '';
$message_type = $_SESSION['admin_message_type'] ?? 'success';
unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);

// --- FORM PROCESSING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['admin_message'] = '';
    $_SESSION['admin_message_type'] = 'success';
    $hasChanges = false;
    
    // --- HANDLE DELETION ---
    if (isset($_POST['action_delete'])) {
        $filename_to_delete = basename($_POST['modpack_file']);
        $pack_path = $modpacksDir . '/' . $filename_to_delete;
        if (file_exists($pack_path) && strpos(realpath($pack_path), realpath($modpacksDir)) === 0) {
            if (unlink($pack_path)) {
                if (!empty($metadata[$filename_to_delete]['modlist_file'])) {
                    $modlist_path = $modlistsDir . '/' . basename($metadata[$filename_to_delete]['modlist_file']);
                    if (file_exists($modlist_path)) unlink($modlist_path);
                }
                unset($metadata[$filename_to_delete]);
                file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
                $_SESSION['admin_message'] = "Successfully deleted '{$filename_to_delete}'.";
            }
        }
        $hasChanges = true;
    }
    // --- HANDLE MAIN FORM (UPLOAD & METADATA) ---
    elseif (isset($_POST['action_save'])) {
        if (isset($_FILES['new_modpack_upload']) && $_FILES['new_modpack_upload']['error'] === UPLOAD_ERR_OK) { /* ... upload ... */ }
        if (isset($_POST['modpacks'])) {
            foreach ($_POST['modpacks'] as $pack_filename => $data) {
                $current_modlist_file = $data['modlist_file'];
                if (isset($_FILES['modlist_uploads']['name'][$pack_filename]) && $_FILES['modlist_uploads']['error'][$pack_filename] === UPLOAD_ERR_OK) { /* ... html upload ... */ }
                
                // ADDED acks for new fields
                $metadata[$pack_filename] = [
                    'loader' => trim($data['loader']),
                    'version' => trim($data['version']),
                    'modlist_file' => $current_modlist_file,
                    'java_args' => trim($data['java_args']), // New field
                    'notes' => trim($data['notes']),       // New field
                ];
            }
            file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
            $_SESSION['admin_message'] .= "Metadata saved. ";
        }
        $hasChanges = true;
    }
    if (!$hasChanges && empty($_SESSION['admin_message'])) $_SESSION['admin_message'] = "No changes were submitted.";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$files = array_filter(scandir($modpacksDir), fn($f) => !in_array($f, ['.', '..']));
natsort($files);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>chisato Panel</title> <link rel="icon" type="image/png" href="images/capitano.png">
    <link rel="stylesheet" href="admin.css"> <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container wide"> <!-- Added 'wide' class for more space -->
        <h1>chisato Panel</h1> <a href="index.php" class="back-link">← Back to Main Site</a>
        <?php if ($message): ?><p class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="card">
                <h2>Upload New Modpack</h2>
                <input type="file" name="new_modpack_upload" id="new-modpack-input" class="is-hidden" accept=".mrpack">
                <div id="modpack-upload-zone" style="cursor: pointer;">
                    <span class="upload-default-text">Drop a .mrpack file here or click to select</span>
                    <span class="upload-file-display is-hidden"></span> <button type="button" class="clear-btn is-hidden" id="clear-upload-btn">×</button>
                </div>
            </div>

            <div class="card">
                <h2>Manage Existing Modpacks</h2>
                <div class="table-wrapper admin-table">
                    <table>
                        <thead>
                            <tr><th>Modpack</th><th>Loader</th><th>Version</th><th>Modlist</th><th>Java Arguments</th><th>Notes</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($file); ?></td>
                                <td><select name="modpacks[<?php echo htmlspecialchars($file); ?>][loader]"><?php foreach ($predefined_loaders as $loader): ?><option value="<?php echo $loader; ?>" <?php if (($metadata[$file]['loader'] ?? '') == $loader) echo 'selected'; ?>><?php echo $loader ?: 'Select...'; ?></option><?php endforeach; ?></select></td>
                                <td><select name="modpacks[<?php echo htmlspecialchars($file); ?>][version]"><?php foreach ($predefined_versions as $version): ?><option value="<?php echo $version; ?>" <?php if (($metadata[$file]['version'] ?? '') == $version) echo 'selected'; ?>><?php echo $version ?: 'Select...'; ?></option><?php endforeach; ?></select></td>
                                <td class="modlist-cell"><input type="hidden" name="modpacks[<?php echo htmlspecialchars($file); ?>][modlist_file]" class="modlist-hidden-input" value="<?php echo htmlspecialchars($metadata[$file]['modlist_file'] ?? ''); ?>"><input type="file" name="modlist_uploads[<?php echo htmlspecialchars($file); ?>]" class="modlist-file-input is-hidden" accept=".html"><div class="drop-zone-wrapper"><?php $has_file = !empty($metadata[$file]['modlist_file'] ?? ''); ?><div class="drop-zone <?php if ($has_file) echo 'is-hidden'; ?>">Drop HTML</div><div class="file-display <?php if (!$has_file) echo 'is-hidden'; ?>"><span class="filename"><?php echo htmlspecialchars($metadata[$file]['modlist_file'] ?? ''); ?></span><button type="button" class="clear-btn">×</button></div></div></td>
                                <td><textarea name="modpacks[<?php echo htmlspecialchars($file); ?>][java_args]" rows="3"><?php echo htmlspecialchars($metadata[$file]['java_args'] ?? ''); ?></textarea></td>
                                <td><textarea name="modpacks[<?php echo htmlspecialchars($file); ?>][notes]" rows="3"><?php echo htmlspecialchars($metadata[$file]['notes'] ?? ''); ?></textarea></td>
                                <td class="actions-cell"><form method="POST" class="delete-form"><input type="hidden" name="modpack_file" value="<?php echo htmlspecialchars($file); ?>"><button type="submit" name="action_delete" value="1" class="delete-btn" title="Delete Modpack"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10,18a1,1,0,0,0,1-1V11a1,1,0,0,0-2,0v6A1,1,0,0,0,10,18Z"/><path d="M14,18a1,1,0,0,0,1-1V11a1,1,0,0,0-2,0v6A1,1,0,0,0,14,18Z"/><path d="M20,6H16V5a3,3,0,0,0-3-3H11A3,3,0,0,0,8,5V6H4A1,1,0,0,0,4,8H5V19a3,3,0,0,0,3,3h8a3,3,0,0,0,3-3V8h1a1,1,0,0,0,0-2ZM10,5a1,1,0,0,1,1-1h2a1,1,0,0,1,1,1V6H10Zm7,14a1,1,0,0,1-1,1H8a1,1,0,0,1-1-1V8H17Z"/></svg></button></form></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($files)): ?><tr><td colspan="7" style="text-align: center; color: var(--ctp-subtext0);">No modpacks found. Upload one above!</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <button type="submit" name="action_save" value="1" class="submit-btn">Save All Changes</button>
        </form>
    </div>
    <script>/* All JS here */</script>
</body>
</html>