<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
?>
<header class="agent-header">
    <a href="mypage.php" class="agent-header-logo">
        <img src="img/logo_white.png" alt="ERAPRO Agent"
             onerror="this.style.display='none'; this.nextSibling.style.display='inline'">
        <span style="display:none; font-weight:800; color:#fff; font-size:1rem; letter-spacing:0.05em;">ERAPRO</span>
    </a>
    <div class="agent-header-right">
        <?php if (isset($_SESSION['name'])): ?>
        <span class="agent-header-user"><?= htmlspecialchars($_SESSION['name'] ?? '') ?></span>
        <?php endif; ?>
        <a href="logout.php" class="agent-header-logout">ログアウト</a>
    </div>
</header>
