<header style="background: #fff; border-bottom: 1px solid #ddd;">
    <div class="header-inner" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 20px;">
        <a href="index.php" class="logo">
            <img src="img/logo_blue.png" alt="ERAPRO" style="height:50px; vertical-align:middle;">
        </a>
        
        <nav>
            <?php
            if (session_status() == PHP_SESSION_NONE) { session_start(); }
            
            // ログイン済み（一般ユーザー）
            if (isset($_SESSION["chk_ssid"]) && isset($_SESSION["user_type"]) && $_SESSION["user_type"] == 'user') {
                echo '<a href="mypage_user.php" style="margin-right:20px; color:#333;">マイページ</a>';
                echo '<a href="logout.php" class="btn-login" style="border:1px solid #004e92; color:#004e92; padding: 5px 15px; border-radius: 20px;">ログアウト</a>';
            } 
            // 未ログイン
            else {
                echo '<a href="login_user.php" class="btn-login" style="border:1px solid #004e92; color:#004e92; padding: 5px 15px; border-radius: 20px;">ログイン</a>';
                echo '<a href="agent_lp.php" style="font-size: 0.8rem; color: #999; text-decoration: underline; margin-left: 20px;">募集人の方はこちら</a>';
            }
            ?>
        </nav>
    </div>
</header>