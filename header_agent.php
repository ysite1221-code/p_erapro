<div class="admin-header" style="background: #000; color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center;">
    
    <a href="mypage.php" class="logo" style="display:flex; align-items:center;">
        <img src="img/logo_white.png" alt="ERAPRO Agent" style="height:55px;">
    </a>
    
    <div style="display:flex; gap:15px; align-items:center;">
        <?php
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        ?>
        
        <?php if(isset($_SESSION["name"])): ?>
            <span style="font-size:0.9rem; color:#ccc;">Login: <?= htmlspecialchars($_SESSION["name"]) ?></span>
        <?php endif; ?>
        
        <a href="logout.php" style="color:#fff; text-decoration:underline; font-size:0.8rem;">ログアウト</a>
    </div>
</div>