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

    // --- 1. HANDLE DELETION --- (Now checks for a specific submit button name)
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
            } else { $_SESSION['admin_message'] = "Error: Could not delete '{$filename_to_delete}'."; $_SESSION['admin_message_type'] = 'error'; }
        }
        $hasChanges = true;
    }
    // --- 2. HANDLE MAIN FORM (UPLOAD & METADATA) ---
    elseif (isset($_POST['action_save'])) {
        if (isset($_FILES['new_modpack_upload']) && $_FILES['new_modpack_upload']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['new_modpack_upload'];
            $filename = basename($file['name']);
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'mrpack') {
                if (move_uploaded_file($file['tmp_name'], $modpacksDir . '/' . $filename)) {
                    $_SESSION['admin_message'] .= "Uploaded '{$filename}'. ";
                }
            }
        }
        if (isset($_POST['modpacks'])) {
            foreach ($_POST['modpacks'] as $pack_filename => $data) {
                $current_modlist_file = $data['modlist_file'];
                if (isset($_FILES['modlist_uploads']['name'][$pack_filename]) && $_FILES['modlist_uploads']['error'][$pack_filename] === UPLOAD_ERR_OK) {
                    $file_tmp_name = $_FILES['modlist_uploads']['tmp_name'][$pack_filename];
                    $file_name = basename($_FILES['modlist_uploads']['name'][$pack_filename]);
                    if (pathinfo($file_name, PATHINFO_EXTENSION) === 'html') {
                        if (move_uploaded_file($file_tmp_name, $modlistsDir . '/' . $file_name)) {
                            $current_modlist_file = $file_name;
                        }
                    }
                }
                $metadata[$pack_filename] = [
                    'loader' => trim($data['loader']),
                    'version' => trim($data['version']),
                    'modlist_file' => $current_modlist_file,
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
    <div class="container">
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
                <table>
                    <thead>
                        <tr><th>Modpack File</th><th>Mod Loader</th><th>Version</th><th>Modlist HTML</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $file): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file); ?></td>
                            <td>
                                <select name="modpacks[<?php echo htmlspecialchars($file); ?>][loader]"><?php foreach ($predefined_loaders as $loader): ?><option value="<?php echo $loader; ?>" <?php if (($metadata[$file]['loader'] ?? '') == $loader) echo 'selected'; ?>><?php echo $loader ?: 'Select...'; ?></option><?php endforeach; ?></select>
                            </td>
                            <td>
                                <select name="modpacks[<?php echo htmlspecialchars($file); ?>][version]"><?php foreach ($predefined_versions as $version): ?><option value="<?php echo $version; ?>" <?php if (($metadata[$file]['version'] ?? '') == $version) echo 'selected'; ?>><?php echo $version ?: 'Select...'; ?></option><?php endforeach; ?></select>
                            </td>
                            <td class="modlist-cell">
                                <input type="hidden" name="modpacks[<?php echo htmlspecialchars($file); ?>][modlist_file]" class="modlist-hidden-input" value="<?php echo htmlspecialchars($metadata[$file]['modlist_file'] ?? ''); ?>">
                                <input type="file" name="modlist_uploads[<?php echo htmlspecialchars($file); ?>]" class="modlist-file-input is-hidden" accept=".html">
                                <div class="drop-zone-wrapper">
                                    <?php $has_file = !empty($metadata[$file]['modlist_file'] ?? ''); ?>
                                    <div class="drop-zone <?php if ($has_file) echo 'is-hidden'; ?>">Drop HTML file or click</div>
                                    <div class="file-display <?php if (!$has_file) echo 'is-hidden'; ?>">
                                        <span class="filename"><?php echo htmlspecialchars($metadata[$file]['modlist_file'] ?? ''); ?></span>
                                        <button type="button" class="clear-btn">×</button>
                                    </div>
                                </div>
                            </td>
                            <td class="actions-cell">
                                <!-- This is now its own form, fixing the bug -->
                                <form method="POST" class="delete-form">
                                    <input type="hidden" name="modpack_file" value="<?php echo htmlspecialchars($file); ?>">
                                    <button type="submit" name="action_delete" value="1" class="delete-btn" title="Delete Modpack">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10,18a1,1,0,0,0,1-1V11a1,1,0,0,0-2,0v6A1,1,0,0,0,10,18Z"/><path d="M14,18a1,1,0,0,0,1-1V11a1,1,0,0,0-2,0v6A1,1,0,0,0,14,18Z"/><path d="M20,6H16V5a3,3,0,0,0-3-3H11A3,3,0,0,0,8,5V6H4A1,1,0,0,0,4,8H5V19a3,3,0,0,0,3,3h8a3,3,0,0,0,3-3V8h1a1,1,0,0,0,0-2ZM10,5a1,1,0,0,1,1-1h2a1,1,0,0,1,1,1V6H10Zm7,14a1,1,0,0,1-1,1H8a1,1,0,0,1-1-1V8H17Z"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($files)): ?>
                            <tr><td colspan="5" style="text-align: center; color: var(--ctp-subtext0);">No modpacks found. Upload one above to get started!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- The main save button now has a name to differentiate it -->
            <button type="submit" name="action_save" value="1" class="submit-btn">Save All Changes</button>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- RESTORED: Logic for New Modpack Uploader ---
        const uploadZone = document.getElementById('modpack-upload-zone');
        const uploadInput = document.getElementById('new-modpack-input');
        const defaultText = uploadZone.querySelector('.upload-default-text');
        const fileDisplayText = uploadZone.querySelector('.upload-file-display');
        const clearUploadBtn = document.getElementById('clear-upload-btn');
        const handleUploadSelect = () => { if (uploadInput.files.length > 0) { fileDisplayText.textContent = `Selected: ${uploadInput.files[0].name}`; defaultText.classList.add('is-hidden'); fileDisplayText.classList.remove('is-hidden'); clearUploadBtn.classList.remove('is-hidden'); } };
        const resetUploadZone = () => { uploadInput.value = ''; defaultText.classList.remove('is-hidden'); fileDisplayText.classList.add('is-hidden'); clearUploadBtn.classList.add('is-hidden'); };
        uploadZone.addEventListener('click', (e) => { if (e.target !== clearUploadBtn) uploadInput.click(); });
        clearUploadBtn.addEventListener('click', resetUploadZone);
        uploadInput.addEventListener('change', handleUploadSelect);
        uploadZone.addEventListener('dragover', (e) => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
        uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
        uploadZone.addEventListener('drop', (e) => { e.preventDefault(); uploadZone.classList.remove('drag-over'); if (e.dataTransfer.files.length > 0) { uploadInput.files = e.dataTransfer.files; handleUploadSelect(); } });

        // Logic for Modlist HTML Drop Zones (Unchanged)
        document.querySelectorAll('.modlist-cell').forEach(cell => {
            const dropZone = cell.querySelector('.drop-zone');
            const fileInput = cell.querySelector('.modlist-file-input');
            const fileDisplay = cell.querySelector('.file-display');
            const filenameSpan = fileDisplay.querySelector('.filename');
            const clearBtn = fileDisplay.querySelector('.clear-btn');
            const showDropZone = () => { fileInput.value = ''; fileDisplay.classList.add('is-hidden'); dropZone.classList.remove('is-hidden'); };
            const showFileDisplay = (name) => { filenameSpan.textContent = name; dropZone.classList.add('is-hidden'); fileDisplay.classList.remove('is-hidden'); };
            dropZone.addEventListener('click', () => fileInput.click());
            clearBtn.addEventListener('click', showDropZone);
            fileInput.addEventListener('change', () => { if (fileInput.files.length > 0) showFileDisplay(fileInput.files[0].name); });
            dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('drag-over'); });
            dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
            dropZone.addEventListener('drop', (e) => { e.preventDefault(); dropZone.classList.remove('drag-over'); if (e.dataTransfer.files.length > 0) { fileInput.files = e.dataTransfer.files; fileInput.dispatchEvent(new Event('change')); } });
        });

        // Add deletion confirmation
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', (event) => {
                if (!confirm('Are you sure you want to delete this modpack? This will also delete its modlist file and cannot be undone.')) {
                    event.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>