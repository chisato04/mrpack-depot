<?php
// ... (Your existing PHP data loading at the top remains the same)
$pageTitle = "chisato | mrpack";
$pageDescription = "A self-hosted repository for Minecraft Modpacks. Click a pack for details or the icon to download.";
$modpacksDir = 'modpacks';
$metadataFile = 'metadata.json';
$metadata = file_exists($metadataFile) ? json_decode(file_get_contents($metadataFile), true) : [];
$all_modpacks = [];
$loaders = [];
$versions = [];
if (is_dir($modpacksDir)) {
    $files = array_filter(scandir($modpacksDir), fn($f) => !in_array($f, ['.', '..']));
    natsort($files);
    foreach ($files as $file) {
        if (!isset($metadata[$file]))
            continue;
        $file_meta = $metadata[$file];
        $all_modpacks[$file] = ['name' => htmlspecialchars(pathinfo($file, PATHINFO_FILENAME)), 'download_url' => htmlspecialchars($modpacksDir . '/' . $file, ENT_QUOTES, 'UTF-8'), 'loader' => htmlspecialchars($file_meta['loader']), 'version' => htmlspecialchars($file_meta['version']),];
        if (!empty($file_meta['loader']))
            $loaders[] = $file_meta['loader'];
        if (!empty($file_meta['version']))
            $versions[] = $file_meta['version'];
    }
}
$unique_loaders = array_unique($loaders);
$unique_versions = array_unique($versions);
sort($unique_loaders);
sort($unique_versions);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
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
            <div class="sidebar-card">
                <header>
                    <h1>chisato packs</h1>
                    <p><?php echo htmlspecialchars($pageDescription); ?></p>
                </header>
                <div class="search-container"><svg class="search-icon" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M10,18a8,8,0,1,1,8-8A8.009,8.009,0,0,1,10,18ZM10,4a6,6,0,1,0,6,6A6.007,6.007,0,0,0,10,4Z" />
                        <path d="M21,22a1,1,0,0,1-.707-0.293l-4-4a1,1,0,0,1,1.414-1.414l4,4A1,1,0,0,1,21,22Z" />
                    </svg><input type="search" id="searchInput" placeholder="Search packs..."></div>
                <div class="filter-group">
                    <h2>Mod Loaders</h2>
                    <div class="filter-buttons"><button class="filter-btn active" data-filter-group="loader"
                            data-filter="all">All</button><?php foreach ($unique_loaders as $loader): ?><button
                                class="filter-btn" data-filter-group="loader"
                                data-filter="<?php echo htmlspecialchars($loader); ?>"><?php echo htmlspecialchars($loader); ?></button><?php endforeach; ?>
                    </div>
                </div>
                <div class="filter-group">
                    <h2>Versions</h2>
                    <div class="filter-buttons"><button class="filter-btn active" data-filter-group="version"
                            data-filter="all">All</button><?php foreach ($unique_versions as $version): ?><button
                                class="filter-btn" data-filter-group="version"
                                data-filter="<?php echo htmlspecialchars($version); ?>"><?php echo htmlspecialchars($version); ?></button><?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="content-card">
                <main class="grid-container" id="gridContainer"><?php foreach ($all_modpacks as $file => $pack): ?>
                        <div class="card" data-href="details.php?pack=<?php echo urlencode($file); ?>"
                            data-loader="<?php echo $pack['loader']; ?>" data-version="<?php echo $pack['version']; ?>">
                            <div class="card-content">
                                <h3><?php echo str_replace('_', ' ', $pack['name']); ?></h3>
                                <div class="tags"><span class="tag tag-loader"><?php echo $pack['loader']; ?></span><span
                                        class="tag tag-version"><?php echo $pack['version']; ?></span></div>
                            </div><a href="<?php echo $pack['download_url']; ?>" class="card-download-btn" download
                                title="Download <?php echo str_replace('_', ' ', $pack['name']); ?>"><svg
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M12,15a1,1,0,0,1-.707-.293l-4-4a1,1,0,1,1,1.414-1.414L12,12.586l3.293-3.293a1,1,0,0,1,1.414,1.414l-4,4A1,1,0,0,1,12,15Z" />
                                    <path d="M12,2A1,1,0,0,0,11,3V13a1,1,0,0,0,2,0V3A1,1,0,0,0,12,2Z" />
                                    <path
                                        d="M20,14a1,1,0,0,0-1,1v4a1,1,0,0,1-1,1H6a1,1,0,0,1-1-1V15a1,1,0,0,0-2,0v4a3,3,0,0,0,3,3H18a3,3,0,0,0,3-3V15A1,1,0,0,0,20,14Z" />
                                </svg></a>
                        </div><?php endforeach; ?>
                </main>
            </div>
        </div>
    </main>

    <?php require '_footer.php'; ?>
</body>

</html>