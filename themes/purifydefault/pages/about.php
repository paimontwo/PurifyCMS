<?php
$title = "About Us";
$desc = "Learn more about our organization, what we do, and how we work.";
$base_site_url = rtrim($cfg_mainurl ?? '', '/');
?>

<style>
.about-split {
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 40px;
    align-items: start;
}
@media (max-width: 900px) {
    .about-split {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-section animate-in">
    <!-- purify:zone:about_hero_start -->
    <div class="container">
        <div class="section-header" style="text-align: left; margin-left: 0; max-width: 720px;">
            <span class="section-badge"><i class="las la-building"></i> About Us</span>
            <h1>About <?= htmlspecialchars($cfg_name ?? 'Our Company') ?></h1>
            <p>We build practical, reliable web software and digital services focused on speed, simplicity, and long-term maintainability.</p>
        </div>
    </div>
    <!-- purify:zone:about_hero_end -->

    <!-- purify:zone:about_mission_start -->
    <div class="container">
        <div class="about-split">
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px;">
                <h2 style="font-size: 20px; margin-bottom: 12px;">Who We Are</h2>
                <p style="font-size: 15px; color: #475569; line-height: 1.7; margin-bottom: 24px;">
                    We focus on creating straightforward, dependable tools that help individuals and businesses publish content and manage online operations without unnecessary complexity.
                </p>

                <h2 style="font-size: 20px; margin-bottom: 12px;">How We Work</h2>
                <p style="font-size: 15px; color: #475569; line-height: 1.7;">
                    Every feature is designed to be lightweight, easy to host on standard servers, and simple to maintain over time.
                </p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="bento-card" style="padding: 22px;">
                    <div style="display: flex; gap: 16px; align-items: flex-start;">
                        <div class="bento-icon" style="margin-bottom: 0; flex-shrink: 0;"><i class="las la-bolt"></i></div>
                        <div>
                            <h3 style="font-size: 16.5px; margin-bottom: 4px;">Fast &amp; Lightweight</h3>
                            <p>Minimal server requirements and static HTML caching keep pages responsive.</p>
                        </div>
                    </div>
                </div>

                <div class="bento-card" style="padding: 22px;">
                    <div style="display: flex; gap: 16px; align-items: flex-start;">
                        <div class="bento-icon" style="margin-bottom: 0; flex-shrink: 0;"><i class="las la-shield-alt"></i></div>
                        <div>
                            <h3 style="font-size: 16.5px; margin-bottom: 4px;">Secure by Default</h3>
                            <p>Built-in CSRF verification, session protection, and self-contained file storage.</p>
                        </div>
                    </div>
                </div>

                <div class="bento-card" style="padding: 22px;">
                    <div style="display: flex; gap: 16px; align-items: flex-start;">
                        <div class="bento-icon" style="margin-bottom: 0; flex-shrink: 0;"><i class="las la-headset"></i></div>
                        <div>
                            <h3 style="font-size: 16.5px; margin-bottom: 4px;">Direct Support</h3>
                            <p>Clear documentation and responsive assistance whenever you need help.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- purify:zone:about_mission_end -->
</div>
