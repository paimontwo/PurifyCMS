<?php
$title = "404 - Page Not Found";
$desc = "The page you are looking for does not exist or has been moved.";
$base_site_url = rtrim($cfg_mainurl ?? '', '/');
?>

<div class="page-section" style="padding: 110px 0; text-align: center;">
    <!-- purify:zone:hero_start -->
    <div class="container">
        <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 56px 32px; box-shadow: 0 10px 24px -6px rgba(15, 23, 42, 0.04);">
            <div style="font-family: 'Outfit', sans-serif; font-size: 72px; font-weight: 800; line-height: 1; color: #4f46e5; margin-bottom: 16px;">404</div>
            <h1 style="font-size: 26px; margin-bottom: 12px; color: #0f172a;">Page Not Found</h1>
            <p style="font-size: 15.5px; color: #64748b; margin: 0 auto 32px; line-height: 1.6;">
                The page you requested could not be found or is currently unavailable. Please check the URL or return to the homepage.
            </p>
            
            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="<?= htmlspecialchars($base_site_url ?: '/') ?>" class="btn btn-primary">
                    <i class="las la-home"></i> Back to Home
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="las la-arrow-left"></i> Go Back
                </a>
            </div>
        </div>
    </div>
    <!-- purify:zone:hero_end -->
</div>
