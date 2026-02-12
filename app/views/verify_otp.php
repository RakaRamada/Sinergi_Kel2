<?php
// app/views/verify_otp.php
// Modern OTP Verification Page

// Variables passed from controller:
// $maskedEmail - masked email for display
// $nama - user's name
// $pesan - error/success message
// $pesan_type - 'error', 'warning', 'success'
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - Sinergi</title>
    <link href="/sinergi/public/css/output.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
    * {
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        margin: 0;
        min-height: 100vh;
        background: linear-gradient(135deg, #111827 0%, #1f2937 50%, #374151 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .otp-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        padding: 48px 40px;
        max-width: 480px;
        width: 100%;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
        text-align: center;
    }

    .logo-wrapper {
        margin-bottom: 32px;
    }

    .logo-wrapper img {
        height: 48px;
        width: auto;
    }

    .email-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #111827, #374151);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        box-shadow: 0 10px 40px rgba(17, 24, 39, 0.3);
    }

    .email-icon svg {
        width: 40px;
        height: 40px;
        color: white;
    }

    h1 {
        font-size: 28px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 8px 0;
    }

    .subtitle {
        color: #6b7280;
        font-size: 15px;
        margin: 0 0 8px 0;
        line-height: 1.6;
    }

    .email-display {
        background: #f3f4f6;
        padding: 10px 20px;
        border-radius: 100px;
        display: inline-block;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
        margin-bottom: 32px;
    }

    .otp-inputs {
        display: flex;
        gap: 12px;
        justify-content: center;
        margin-bottom: 24px;
    }

    .otp-input {
        width: 52px;
        height: 64px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 28px;
        font-weight: 700;
        text-align: center;
        color: #111827;
        background: #fff;
        transition: all 0.2s ease;
        outline: none;
    }

    .otp-input:focus {
        border-color: #111827;
        box-shadow: 0 0 0 4px rgba(17, 24, 39, 0.1);
        transform: scale(1.05);
    }

    .otp-input.filled {
        background: #f9fafb;
        border-color: #111827;
    }

    .timer-box {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .timer-icon {
        font-size: 24px;
    }

    .timer-text {
        color: #92400e;
        font-size: 15px;
        font-weight: 600;
    }

    .timer-countdown {
        font-size: 20px;
        font-weight: 700;
        color: #78350f;
        font-variant-numeric: tabular-nums;
    }

    .btn-verify {
        width: 100%;
        background: linear-gradient(135deg, #111827, #374151);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 16px;
    }

    .btn-verify:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(17, 24, 39, 0.3);
    }

    .btn-verify:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .resend-section {
        color: #6b7280;
        font-size: 14px;
    }

    .resend-section a {
        color: #111827;
        font-weight: 600;
        text-decoration: none;
    }

    .resend-section a:hover {
        text-decoration: underline;
    }

    .resend-section button {
        background: none;
        border: none;
        color: #111827;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        padding: 0;
    }

    .resend-section button:disabled {
        color: #9ca3af;
        cursor: not-allowed;
    }

    /* Alert Styles */
    .alert {
        padding: 14px 16px;
        border-radius: 12px;
        margin-bottom: 24px;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-error {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .alert-warning {
        background: #fffbeb;
        color: #d97706;
        border: 1px solid #fde68a;
    }

    .alert-success {
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #6b7280;
        text-decoration: none;
        font-size: 14px;
        margin-top: 24px;
    }

    .back-link:hover {
        color: #111827;
    }

    @media (max-width: 480px) {
        .otp-container {
            padding: 32px 24px;
        }

        .otp-input {
            width: 44px;
            height: 56px;
            font-size: 24px;
        }

        .otp-inputs {
            gap: 8px;
        }

        h1 {
            font-size: 24px;
        }
    }
    </style>
</head>

<body>
    <div class="otp-container">
        <!-- Logo -->
        <div class="logo-wrapper">
            <img src="/sinergi/public/assets/images/logo_sinergi_hitam.png" alt="Sinergi">
        </div>

        <!-- Email Icon -->
        <div class="email-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>

        <!-- Title -->
        <h1>Verifikasi Email Anda</h1>
        <p class="subtitle">Kami telah mengirimkan kode OTP 6 digit ke</p>
        <div class="email-display"><?php echo htmlspecialchars($maskedEmail); ?></div>

        <!-- Alert Message -->
        <?php if (!empty($pesan)): 
            $alertClass = $pesan_type === 'success' ? 'alert-success' : ($pesan_type === 'warning' ? 'alert-warning' : 'alert-error');
            $icon = $pesan_type === 'success' ? '✓' : ($pesan_type === 'warning' ? '⚠️' : '✕');
        ?>
        <div class="alert <?php echo $alertClass; ?>">
            <span><?php echo $icon; ?></span>
            <?php echo htmlspecialchars($pesan); ?>
        </div>
        <?php endif; ?>

        <!-- OTP Form -->
        <form action="index.php?page=verify-otp-process" method="POST" id="otpForm">
            <div class="otp-inputs">
                <input type="text" class="otp-input" name="otp1" id="otp1" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" required>
                <input type="text" class="otp-input" name="otp2" id="otp2" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                <input type="text" class="otp-input" name="otp3" id="otp3" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                <input type="text" class="otp-input" name="otp4" id="otp4" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                <input type="text" class="otp-input" name="otp5" id="otp5" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                <input type="text" class="otp-input" name="otp6" id="otp6" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
            </div>

            <!-- Timer -->
            <div class="timer-box">
                <span class="timer-icon">⏱️</span>
                <span class="timer-text">Kode berlaku:</span>
                <span class="timer-countdown" id="countdown">05:00</span>
            </div>

            <button type="submit" class="btn-verify" id="verifyBtn">
                Verifikasi Akun
            </button>
        </form>

        <!-- Resend Section -->
        <div class="resend-section">
            <span>Tidak menerima kode? </span>
            <button id="resendBtn" onclick="resendOtp()" disabled>
                Kirim Ulang (<span id="resendTimer">60</span>s)
            </button>
        </div>

        <!-- Back Link -->
        <a href="index.php?page=register" class="back-link">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali ke Registrasi
        </a>
    </div>

    <script>
    // OTP Input Auto-Focus Logic
    const otpInputs = document.querySelectorAll('.otp-input');
    
    otpInputs.forEach((input, index) => {
        // Auto focus first input
        if (index === 0) input.focus();
        
        input.addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            
            if (this.value.length === 1) {
                this.classList.add('filled');
                // Move to next input
                if (index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            } else {
                this.classList.remove('filled');
            }
        });
        
        input.addEventListener('keydown', function(e) {
            // Handle backspace
            if (e.key === 'Backspace' && this.value === '' && index > 0) {
                otpInputs[index - 1].focus();
                otpInputs[index - 1].value = '';
                otpInputs[index - 1].classList.remove('filled');
            }
            
            // Handle paste
            if (e.key === 'v' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                navigator.clipboard.readText().then(text => {
                    const digits = text.replace(/[^0-9]/g, '').slice(0, 6);
                    digits.split('').forEach((digit, i) => {
                        if (otpInputs[i]) {
                            otpInputs[i].value = digit;
                            otpInputs[i].classList.add('filled');
                        }
                    });
                    if (digits.length > 0) {
                        otpInputs[Math.min(digits.length - 1, 5)].focus();
                    }
                });
            }
        });
        
        // Handle paste event
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasteData = e.clipboardData.getData('text');
            const digits = pasteData.replace(/[^0-9]/g, '').slice(0, 6);
            digits.split('').forEach((digit, i) => {
                if (otpInputs[i]) {
                    otpInputs[i].value = digit;
                    otpInputs[i].classList.add('filled');
                }
            });
            if (digits.length > 0) {
                otpInputs[Math.min(digits.length - 1, 5)].focus();
            }
        });
    });

    // Countdown Timer (5 minutes)
    let timeLeft = 5 * 60; // 5 minutes in seconds
    const countdownEl = document.getElementById('countdown');
    const verifyBtn = document.getElementById('verifyBtn');
    
    const countdownInterval = setInterval(() => {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        countdownEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        
        if (timeLeft <= 0) {
            clearInterval(countdownInterval);
            countdownEl.textContent = 'Expired';
            verifyBtn.disabled = true;
            document.querySelector('.timer-box').style.background = 'linear-gradient(135deg, #fee2e2, #fecaca)';
            document.querySelector('.timer-text').textContent = 'Kode sudah ';
        }
        timeLeft--;
    }, 1000);

    // Resend Timer (60 seconds cooldown)
    let resendTimeLeft = 60;
    const resendBtn = document.getElementById('resendBtn');
    const resendTimerEl = document.getElementById('resendTimer');
    
    const resendInterval = setInterval(() => {
        resendTimeLeft--;
        resendTimerEl.textContent = resendTimeLeft;
        
        if (resendTimeLeft <= 0) {
            clearInterval(resendInterval);
            resendBtn.disabled = false;
            resendBtn.innerHTML = 'Kirim Ulang OTP';
        }
    }, 1000);

    function resendOtp() {
        window.location.href = 'index.php?page=resend-otp';
    }
    </script>
</body>

</html>
