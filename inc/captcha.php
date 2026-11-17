<?php
/**
 * NywhashCaptcha
 * High-performance, memory-buffered captcha for PurifyCMS.
 */
class NywhashCaptcha {
    
    public static function generate() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $n1 = rand(1, 9);
        $n2 = rand(1, 9);
        $op = rand(0, 1) ? '+' : '-';
        if ($op === '-' && $n1 < $n2) { $tmp = $n1; $n1 = $n2; $n2 = $tmp; }
        $result = ($op === '+') ? ($n1 + $n2) : ($n1 - $n2);
        $_SESSION['nywhash_captcha'] = $result;
        return "$n1 $op $n2 = ?";
    }

    public static function verify($input) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['nywhash_captcha'])) return false;
        $valid = ((int)$input === (int)$_SESSION['nywhash_captcha']);
        if ($valid) unset($_SESSION['nywhash_captcha']);
        return $valid;
    }

    public static function getImageBase64($question = null) {
        if (!$question) $question = self::generate();
        
        $w = 140; $h = 45;
        $img = imagecreatetruecolor($w, $h);
        
        // Anti-Bot Gradient & Noise
        $bg = imagecolorallocate($img, 250, 250, 250);
        imagefilledrectangle($img, 0, 0, $w, $h, $bg);
        
        // Noise dots
        for ($i=0; $i<80; $i++) {
            $color = imagecolorallocate($img, rand(200,240), rand(200,240), rand(200,240));
            imagesetpixel($img, rand(0,$w), rand(0,$h), $color);
        }
        
        // Criss-cross noise lines
        for ($i=0; $i<4; $i++) {
            $color = imagecolorallocate($img, rand(200,230), rand(200,230), rand(200,230));
            imageline($img, 0, rand(0,$h), $w, rand(0,$h), $color);
        }

        // Render text with pixel distortion (Using built-in fonts for portability)
        $text_color = imagecolorallocate($img, 30, 41, 59); // Slate-800
        $x = 20; $y = 15;
        
        // We write the text multiple times with tiny offsets to thicken/distort slightly
        imagestring($img, 5, $x, $y, $question, $text_color);
        imagestring($img, 5, $x+1, $y, $question, $text_color);
        
        // Final noise overlay
        for ($i=0; $i<150; $i++) {
            $color = imagecolorallocate($img, rand(150,200), rand(150,200), rand(150,200));
            imagesetpixel($img, rand(0,$w), rand(0,$h), $color);
        }

        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);
        
        return 'data:image/png;base64,' . base64_encode($data);
    }

    public static function render() {
        $img_src = self::getImageBase64();
        // Embedded CSS for theme portability (No dependencies required by theme)
        $css = '
        <style>
            .nywhash-captcha-wrap { display: flex; align-items: stretch; gap: 10px; margin-top: 10px; }
            .nywhash-captcha-img-box { background: #fff; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; min-width: 140px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); position: relative; }
            .nywhash-captcha-img-box img { display: block; filter: contrast(110%); }
            .nywhash-captcha-input { width: 70px !important; text-align: center; border-radius: 8px !important; font-weight: 700; font-size: 16px; border: 1px solid rgba(0,0,0,0.1) !important; background: #fff !important; }
            .nywhash-captcha-refresh { background: var(--bg-surface, #f8fafc); border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; width: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: all 0.2s; }
            .nywhash-captcha-refresh:hover { color: var(--prfy-primary); background: #fff; border-color: var(--prfy-primary); transform: rotate(180deg); }
        </style>';
        
        return $css . '
        <div class="nywhash-captcha-wrap">
            <div class="nywhash-captcha-img-box" id="nywhashCaptchaBox">
                <img src="'.$img_src.'" alt="captcha" id="nywhashCaptchaImg">
            </div>
            <button type="button" class="nywhash-captcha-refresh" onclick="refreshNywhashCaptcha(this)" title="Refresh">
                <i class="las la-sync-alt"></i>
            </button>
            <input type="number" name="captcha_result" required class="form-control nywhash-captcha-input" placeholder="?">
        </div>
        <script>
        function refreshNywhashCaptcha(btn) {
            btn.style.opacity = "0.5";
            fetch("api/captcha-refresh")
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        document.getElementById("nywhashCaptchaImg").src = data.img;
                        btn.style.opacity = "1";
                    }
                });
        }
        </script>';
    }
}
