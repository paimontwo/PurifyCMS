<?php
$title = "Contact Our Team";
$desc = "Get in touch with our professional team to discuss your business requirements.";
?>

<section class="page-section animate-in">
    <!-- purify:zone:contact_hero_start -->
    <div class="container">
        <div class="section-header" style="text-align: left; margin-bottom: 60px;">
            <h1 class="gradient-text">Contact Our Team</h1>
            <p>Ready to start your next project? We are here to help you navigate your digital transformation.</p>
        </div>
    </div>
    <!-- purify:zone:contact_hero_end -->

    <!-- purify:zone:contact_content_start -->
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 80px; align-items: start;">
            <!-- Contact Details -->
            <div style="display: flex; flex-direction: column; gap: 40px;">
                <div>
                    <h3 style="margin-bottom: 16px; font-size: 20px;">Global Headquarters</h3>
                    <p style="color: var(--prfy_text-muted); line-height: 1.8;">
                        123 Business Avenue, Suite 500<br>
                        Silicon Valley, CA 94025<br>
                        United States
                    </p>
                </div>

                <div>
                    <h3 style="margin-bottom: 16px; font-size: 20px;">Direct Contact</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px; color: var(--prfy-slate-600);">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <i class="las la-phone" style="color: var(--prfy-primary); font-size: 20px;"></i>
                            <span>+1 (555) 123-4567</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <i class="las la-envelope" style="color: var(--prfy-primary); font-size: 20px;"></i>
                            <a href="mailto:info@yourcompany.com" style="color: inherit; text-decoration: none;">info@yourcompany.com</a>
                        </div>
                    </div>
                </div>

                <div style="padding: 30px; background: var(--prfy-white); border: 1px solid rgba(0,0,0,0.05); border-radius: var(--prfy-radius-lg); box-shadow: var(--prfy-shadow-sm);">
                    <h4 style="margin-bottom: 12px;">Operating Hours</h4>
                    <p style="font-size: 14px; color: var(--prfy-slate-600);">Monday - Friday: 09:00 AM - 06:00 PM (EST)</p>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-wrap" style="margin: 0; padding: 40px; background: var(--prfy-white); border-radius: var(--prfy-radius-xl); box-shadow: var(--prfy-shadow-lg); border: 1px solid rgba(0,0,0,0.03);">
                <div id="contact-response" style="display:none; padding:16px; margin-bottom:24px; border-radius:var(--prfy-radius-md); font-weight:500; border:1px solid transparent;"></div>

                <form id="v1ContactForm">
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="name" style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--prfy-slate-900);">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.08); background: var(--prfy-slate-50); outline: none; transition: all 0.3s;" required placeholder="John Doe">
                    </div>
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="email" style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--prfy-slate-900);">Work Email</label>
                        <input type="email" id="email" name="email" class="form-control" style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.08); background: var(--prfy-slate-50); outline: none; transition: all 0.3s;" required placeholder="john@company.com">
                    </div>
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="message" style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--prfy-slate-900);">How can we help?</label>
                        <textarea id="message" name="message" rows="5" class="form-control" style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.08); background: var(--prfy-slate-50); outline: none; transition: all 0.3s; resize: none;" required placeholder="Tell us about your project..."></textarea>
                    </div>
                    
                    <?php if (isset($cfg_enable_captcha) && $cfg_enable_captcha && class_exists('NywhashCaptcha')): ?>
                        <div style="margin-bottom: 24px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--prfy-slate-900);">Security Verification</label>
                            <?= NywhashCaptcha::render() ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" id="submitBtn" class="btn btn-primary" style="width:100%; justify-content: center; padding: 16px;">Send Message <i class="las la-paper-plane" style="margin-left:8px;"></i></button>
                </form>
            </div>
        </div>
    </div>
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

    fetch('api/contact', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        responseDiv.style.display = 'block';
        if(data.success) {
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
    .catch(err => {
        responseDiv.style.display = 'block';
        responseDiv.style.background = '#fef2f2';
        responseDiv.style.color = '#991b1b';
        responseDiv.style.borderColor = '#fecaca';
        responseDiv.innerText = 'Something went wrong. Please try again.';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Send Message <i class="fa-solid fa-paper-plane" style="margin-left:8px;"></i>';
    });
});
</script>
