<header>
    <div class="header-inner">
        <a href="index.php" class="logo">
            <img src="img/logo_blue.png" alt="ERAPRO">
        </a>
        <nav class="header-nav">
            <?php
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
            if (isset($_SESSION['chk_ssid']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user') {
                echo '<a href="mypage_user.php" class="btn-mypage">マイページ</a>';
                echo '<a href="logout.php" class="btn-login">ログアウト</a>';
            } else {
                echo '<a href="agent_lp.php" class="header-nav-link">募集人の方はこちら</a>';
                echo '<a href="login_user.php" class="btn-login">ログイン</a>';
            }
            ?>
        </nav>
    </div>
</header>
