<?php
// ... (PHP data loading logic is the same)
$metadataFile = 'metadata.json';
$modpacksDir = 'modpacks';
$modlistsDir = 'modlists';
if (!isset($_GET['pack'])) {
    die("No modpack specified.");
}
$pack_filename = basename($_GET['pack']);
$metadata = file_exists($metadataFile) ? json_decode(file_get_contents($metadataFile), true) : [];
if (!isset($metadata[$pack_filename])) {
    die("Modpack not found in metadata.");
}
$pack_meta = $metadata[$pack_filename];
$modlist_html_file = $modlistsDir . '/' . ($pack_meta['modlist_file'] ?? '');
$has_modlist = !empty($pack_meta['modlist_file']) && file_exists($modlist_html_file);
$mod_list = [];
if ($has_modlist) {
    $doc = new DOMDocument();
    @$doc->loadHTMLFile($modlist_html_file);
    $xpath = new DOMXPath($doc);
    $list_items = $xpath->query('//li');
    foreach ($list_items as $item) {
        $mod_name = '';
        $mod_url = '#';
        $mod_info = '';
        $link = $xpath->query('a', $item)->item(0);
        if ($link) {
            $mod_name = $link->nodeValue;
            $mod_url = $link->getAttribute('href');
            $mod_info = trim(str_replace($mod_name, '', $item->nodeValue));
        } else {
            $mod_name = $item->nodeValue;
        }
        if (empty(trim($mod_name)) || $mod_name === '.connector')
            continue;
        $mod_list[] = ['name' => $mod_name, 'url' => $mod_url, 'info' => $mod_info];
    }
}
$pageTitle = str_replace('_', ' ', pathinfo($pack_filename, PATHINFO_FILENAME));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Mod Details</title>
    <link rel="icon" type="image/png" href="images/capitano.png">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php require '_header.php'; ?>

    <main class="site-main">
        <div class="container">
            <div class="details-container">
                <header class="details-header">
                    <div><a href="index.php" class="back-link">← Back to All Packs</a>
                        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                        <div class="tags"><span
                                class="tag tag-loader"><?php echo htmlspecialchars($pack_meta['loader']); ?></span><span
                                class="tag tag-version"><?php echo htmlspecialchars($pack_meta['version']); ?></span>
                        </div>
                    </div>
                    <a href="<?php echo htmlspecialchars($modpacksDir . '/' . $pack_filename); ?>" class="download-btn"
                        download>Download Pack</a>
                </header>

                <div class="tab-container">
                    <nav class="tab-nav">
                        <button class="tab-btn active" data-tab="mod-list">Mod List</button>
                        <button class="tab-btn" data-tab="setup-notes">Setup & Notes</button>
                    </nav>

                    <div id="mod-list" class="tab-content active">
                        <?php if ($has_modlist): ?>
                            <div class="table-wrapper">
                                <table class="mod-list-table">
                                    <thead>
                                        <tr>
                                            <th>Mod Name</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody><?php foreach ($mod_list as $mod): ?>
                                            <tr>
                                                <td><a href="<?php echo htmlspecialchars($mod['url']); ?>" target="_blank"
                                                        rel="noopener noreferrer"><?php echo htmlspecialchars($mod['name']); ?></a>
                                                </td>
                                                <td><?php echo htmlspecialchars($mod['info']); ?></td>
                                            </tr><?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="placeholder-text">No modlist has been uploaded for this pack.</p>
                        <?php endif; ?>
                    </div>

                    <div id="setup-notes" class="tab-content">
                        <?php if (!empty($pack_meta['java_args'])): ?>
                            <h3>Recommended Java Arguments</h3>
                            <pre><code><?php echo htmlspecialchars($pack_meta['java_args']); ?></code></pre>
                        <?php endif; ?>

                        <?php if (!empty($pack_meta['notes'])): ?>
                            <h3>Notes</h3>
                            <div class="notes-content"><?php echo nl2br(htmlspecialchars($pack_meta['notes'])); ?></div>
                        <?php endif; ?>

                        <?php if (empty($pack_meta['java_args']) && empty($pack_meta['notes'])): ?>
                            <p class="placeholder-text">No setup information or notes have been added for this pack.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require '_footer.php'; ?>
</body>

</html>