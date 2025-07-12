<?php
session_start();

// --- CONFIGURATION ---
$metadataFile = 'metadata.json';
$modpacksDir = 'modpacks';
$modlistsDir = 'modlists';

// --- INITIALIZATION ---
if (!is_dir($modpacksDir)) mkdir($modpacksDir, 0755, true);
if (!is_dir($modlistsDir)) mkdir($modlistsDir, 0755, true);
$metadata = json_decode(@file_get_contents($metadataFile), true) ?? [];

// Check for a message from the session (from the PRG pattern)
$message = $_SESSION['admin_message'] ?? '';
$message_type = $_SESSION['admin_message_type'] ?? 'success';
unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);

// --- SINGLE FORM PROCESSING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['admin_message'] = '';
    $_SESSION['admin_message_type'] = 'success';
    $hasChanges = false;
    
    // --- 1. Handle New Modpack Upload ---
    if (isset($_FILES['new_modpack_upload']) && $_FILES['new_modpack_upload']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['new_modpack_upload'];
        $filename = basename($file['name']);
        if (pathinfo($filename, PATHINFO_EXTENSION) === 'mrpack') {
            if (move_uploaded_file($file['tmp_name'], $modpacksDir . '/' . $filename)) {
                $_SESSION['admin_message'] .= "Successfully uploaded '{$filename}'. ";
                $hasChanges = true;
            } else { $_SESSION['admin_message'] .= "Error: Could not save '{$filename}'. "; $_SESSION['admin_message_type'] = 'error'; }
        } else { $_SESSION['admin_message'] .= "Error: Invalid file type for new modpack. Only .mrpack allowed. "; $_SESSION['admin_message_type'] = 'error'; }
    }

    // --- 2. Handle Metadata & Modlist Uploads ---
    if (isset($_POST['modpacks'])) {
        $newMetadata = [];
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
            $newMetadata[$pack_filename] = [
                'loader' => trim($data['loader']),
                'version' => trim($data['version']),
                'modlist_file' => $current_modlist_file,
            ];
        }
        file_put_contents($metadataFile, json_encode($newMetadata, JSON_PRETTY_PRINT));
        $_SESSION['admin_message'] .= "Metadata saved. ";
        $hasChanges = true;
    }

    if (!$hasChanges && empty($_SESSION['admin_message'])) {
        $_SESSION['admin_message'] = "No changes were submitted.";
    }
    
    // --- POST-REDIRECT-GET ---
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// --- DATA PREPARATION FOR UI ---
$files = array_filter(scandir($modpacksDir), fn($f) => !in_array($f, ['.', '..']));
natsort($files);
$unique_loaders = array_unique(array_column($metadata, 'loader'));
$unique_versions = array_unique(array_column($metadata, 'version'));
sort($unique_loaders);
sort($unique_versions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>chisato Panel</title>
    <link rel="icon" type="image/png" href="images/capitano.png">
    <link rel="stylesheet" href="admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>chisato Panel</h1>
        <a href="index.php" class="back-link">← Back to Main Site</a>
        <?php if ($message): ?><p class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="card">
                <h2>Upload New Modpack</h2>
                <input type="file" name="new_modpack_upload" id="new-modpack-input" class="is-hidden" accept=".mrpack">
                <div id="modpack-upload-zone" style="cursor: pointer;">
                    <span class="upload-default-text">Drop a .mrpack file here or click to select</span>
                    <span class="upload-file-display is-hidden"></span>
                    <button type="button" class="clear-btn is-hidden" id="clear-upload-btn">×</button>
                </div>
            </div>

            <div class="card">
                <h2>Manage Existing Modpacks</h2>
                <table>
                    <thead>
                        <tr><th>Modpack File</th><th>Mod Loader</th><th>Version</th><th>Modlist HTML</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $file): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file); ?></td>
                            <td><input class="datalist-input" type="text" list="loaders-list" name="modpacks[<?php echo htmlspecialchars($file); ?>][loader]" value="<?php echo htmlspecialchars($metadata[$file]['loader'] ?? ''); ?>"></td>
                            <td><input class="datalist-input" type="text" list="versions-list" name="modpacks[<?php echo htmlspecialchars($file); ?>][version]" value="<?php echo htmlspecialchars($metadata[$file]['version'] ?? ''); ?>"></td>
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
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($files)): ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--ctp-subtext0);">No modpacks found. Upload one above to get started!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="submit-btn">Save All Changes</button>
        </form>
        
        <datalist id="loaders-list"><?php foreach ($unique_loaders as $loader) { if(!empty($loader)) echo "<option value=\"" . htmlspecialchars($loader) . "\">"; } ?></datalist>
        <datalist id="versions-list"><?php foreach ($unique_versions as $version) { if(!empty($version)) echo "<option value=\"" . htmlspecialchars($version) . "\">"; } ?></datalist>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.modlist-cell').forEach(cell => {
            const dropZone = cell.querySelector('.drop-zone');
            const fileInput = cell.querySelector('.modlist-file-input');
            const hiddenInput = cell.querySelector('.modlist-hidden-input');
            const fileDisplay = cell.querySelector('.file-display');
            const filenameSpan = fileDisplay.querySelector('.filename');
            const clearBtn = fileDisplay.querySelector('.clear-btn');
            const showDropZone = () => { fileInput.value = ''; hiddenInput.value = ''; dropZone.classList.remove('is-hidden'); fileDisplay.classList.add('is-hidden'); };
            const showFileDisplay = (name) => { filenameSpan.textContent = name; dropZone.classList.add('is-hidden'); fileDisplay.classList.remove('is-hidden'); };
            dropZone.addEventListener('click', () => fileInput.click());
            clearBtn.addEventListener('click', showDropZone);
            fileInput.addEventListener('change', () => { if (fileInput.files.length > 0) showFileDisplay(fileInput.files[0].name); });
            dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('drag-over'); });
            dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
            dropZone.addEventListener('drop', (e) => { e.preventDefault(); dropZone.classList.remove('drag-over'); if (e.dataTransfer.files.length > 0) { fileInput.files = e.dataTransfer.files; fileInput.dispatchEvent(new Event('change')); } });
        });

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
    });
    </script>
</body>
</html>