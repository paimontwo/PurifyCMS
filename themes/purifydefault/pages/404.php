<?php
$title = "404 - Page Not Found";
$desc = "The page you are looking for does not exist or has been moved.";
?>

<div class="hero-section" style="padding: 120px 0; text-align: center;">
    <!-- purify:zone:hero_start -->
    <div class="container">
        <h1 style="font-size: 120px; font-weight: 800; margin: 0; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">404</h1>
        <h2 style="font-size: 32px; margin-bottom: 20px;">Whoops! Lost in Space?</h2>
        <p style="font-size: 18px; color: var(--text-muted); max-width: 600px; margin: 0 auto 40px;"> The page you're searching for seems to have vanished into the digital void. Don't worry, you can easily find your way back.</p>
        
        <div style="display: flex; gap: 20px; justify-content: center;">
            <a href="<?= $cfg_mainurl ?>" class="btn btn-primary" style="padding: 12px 30px;">
                <i class="fa-solid fa-house"></i> Go Home
            </a>
            <a href="javascript:history.back()" class="btn" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px 30px;">
                <i class="fa-solid fa-arrow-left"></i> Go Back
            </a>
        </div>
    </div>
    <!-- purify:zone:hero_end -->
</div>

<style>
    .hero-section {
        background: radial-gradient(circle at center, rgba(99, 102, 241, 0.05) 0%, transparent 70%);
    }
</style>
