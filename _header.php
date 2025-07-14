<header class="site-header">
    <div class="header-container">
        <a href="../index.php" class="logo">
            <img src="images/capitano.png" alt="Site Logo">
        </a>
        <nav class="main-nav">
            <ul>
                <!-- We'll use PHP to dynamically set the 'active' class -->
                <li><a href="index.php" class="<?php if (basename($_SERVER['PHP_SELF']) == 'index.php') echo 'active'; ?>">Packs</a></li>
                <li><a href="admin.php" class="<?php if (basename($_SERVER['PHP_SELF']) == 'admin.php') echo 'active'; ?>">Admin</a></li>
                <li><a href="https://github.com/chisato04/mrpack-depot" target="_blank" rel="noopener noreferrer">GitHub</a></li>
            </ul>
        </nav>
    </div>
</header>