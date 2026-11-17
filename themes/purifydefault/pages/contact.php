<?php
$title = "Contact Us";
$desc = "Get in touch with our team to discuss your project requirements or support inquiries.";
$support_email = !empty($cfg_support) ? $cfg_support : (!empty($cfg_contact_email) ? $cfg_contact_email : 'support@' . ($cfg_domain ?: 'example.com'));
?>

<style>
.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1.4fr;
    gap: 48px;
    align-items: start;
}
.contact-info-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 32px;
    display: flex;
    flex-direction: column;
    gap: 28px;
}
.contact-info-item {
    display: flex;
    gap: 16px;
    align-items: flex-start;
}
.contact-info-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: rgba(79, 70, 229, 0.08);
    color: #4f46e5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.contact-info-item h3 {
    font-size: 16px;
    margin-bottom: 4px;
    color: #0f172a;
}
.contact-info-item p,
.contact-info-item a {
    font-size: 14.5px;
    color: #475569;
    text-decoration: none;
    line-height: 1.6;
}
.contact-info-item a:hover {
    color: #4f46e5;
}
.contact-form-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 36px;
    box-shadow: 0 10px 24px -6px rgba(15, 23, 42, 0.04);
}
.contact-input {
    width: 100%;
    padding: 12px 16px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    font-size: 14.5px;
    font-family: inherit;
    color: #0f172a;
    outline: none;
    transition: all 0.15s ease;
}
.contact-input:focus {
    background: #ffffff;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}
@media (max-width: 900px) {
    .contact-grid {
        grid-template-columns: 1fr;
        gap: 32px;
    }
}
</style>

<section class="page-section animate-in">
    <!-- purify:zone:contact_hero_start -->
    <div class="container">
        <div class="section-header" style="text-align: left; margin-left: 0; max-width: 720px; margin-bottom: 40px;">
            <span class="section-badge"><i class="las la-envelope"></i> Contact Us</span>
            <h1>Get in Touch With Our Team</h1>
            <p>Have a question, project inquiry, or need technical assistance? Send us a message and we will respond promptly.</p>
        </div>
    </div>
    <!-- purify:zone:contact_hero_end -->

    <!-- purify:zone:contact_content_start -->
    <div class="container">
        <div class="contact-grid">
            <div class="contact-info-card">
                <div class="contact-info-item">
                    <div class="contact-info-icon"><i class="las la-building"></i></div>
                    <div>
                        <h3>Organization</h3>
                        <p><?= htmlspecialchars(!empty($cfg_legalname) ? $cfg_legalname : ($cfg_name ?? 'PurifyCMS')) ?></p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon"><i class="las la-envelope"></i></div>
                    <div>
                        <h3>Direct Email</h3>
                        <a href="mailto:<?= htmlspecialchars($support_email) ?>"><?= htmlspecialchars($support_email) ?></a>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon"><i class="las la-clock"></i></div>
                    <div>
                        <h3>Operating Hours</h3>
                        <p>Monday &ndash; Friday: 09:00 &ndash; 18:00<br>Weekend support available for active orders.</p>
                    </div>
                </div>
            </div>

            <div class="contact-form-card">
                <div id="contact-response" style="display:none; padding:14px 16px; margin-bottom:20px; border-radius:10px; font-size:14px; font-weight:500; border:1px solid transparent;"></div>

                <form id="v1ContactForm">
                    <div style="margin-bottom: 20px;">
                        <label for="name" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13.5px; color: #0f172a;">Full Name</label>
                        <input type="text" id="name" name="name" class="contact-input" required placeholder="John Doe">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label for="email" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13.5px; color: #0f172a;">Email Address</label>
                        <input type="email" id="email" name="email" class="contact-input" required placeholder="john@company.com">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label for="message" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13.5px; color: #0f172a;">Message</label>
                        <textarea id="message" name="message" rows="5" class="contact-input" style="resize: vertical;" required placeholder="How can we help you?"></textarea>
                    </div>
                    
                    <?php if (isset($cfg_enable_captcha) && $cfg_enable_captcha && class_exists('NywhashCaptcha')): ?>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13.5px; color: #0f172a;">Security Verification</label>
                            <?= NywhashCaptcha::render() ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" id="submitBtn" class="btn btn-primary" style="width:100%; padding: 14px;">
                        Send Message <i class="las la-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <!-- purify:zone:contact_content_end -->
</section>

<script>
document.getElementById('v1ContactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    const responseDiv = document.getElementById('contact-response');
    const formData = new FormData(this);

    btn.disabled = true;
    btn.innerHTML = 'Sending Message...';
    responseDiv.style.display = 'none';

    fetch('<?= htmlspecialchars(rtrim($cfg_mainurl ?? '', '/')) ?>/api/contact', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        responseDiv.style.display = 'block';
        if (data.success) {
            responseDiv.style.background = '#f0fdf4';
            responseDiv.style.color = '#166534';
            responseDiv.style.borderColor = '#bbf7d0';
            responseDiv.innerText = data.message;
            this.reset();
        } else {
            responseDiv.style.background = '#fef2f2';
            responseDiv.style.color = '#991b1b';
            responseDiv.style.borderColor = '#fecaca';
            responseDiv.innerText = data.message;
        }
    })
    .catch(() => {
        responseDiv.style.display = 'block';
        responseDiv.style.background = '#fef2f2';
        responseDiv.style.color = '#991b1b';
        responseDiv.style.borderColor = '#fecaca';
        responseDiv.innerText = 'Something went wrong. Please try again.';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Send Message <i class="las la-paper-plane"></i>';
    });
});
</script>
