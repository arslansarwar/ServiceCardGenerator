<?php
require_once __DIR__ . '/../includes/Member.php';

// Public verification page: don't let search engines index member data
header('X-Robots-Tag: noindex, nofollow');

$code = trim($_GET['code'] ?? '');

// A scanner (camera or hardware) may deliver the full verify URL from the QR
// code rather than a bare code — extract the code param either way.
if ($code !== '' && strpos($code, 'code=') !== false) {
    $query = parse_url($code, PHP_URL_QUERY);
    if ($query) {
        parse_str($query, $parsed);
        if (!empty($parsed['code'])) {
            $code = $parsed['code'];
        }
    }
}

$member = null;
$notFound = false;

if ($code !== '') {
    $pdo = getDbConnection();
    $member = getMemberByCode($pdo, $code);
    $notFound = !$member;
}

$isExpired = $member && strtotime($member['valid_upto']) < strtotime('today');
$isValid = $member && $member['status'] === 'active' && !$isExpired;

// Mask CNIC for a public-facing page: show only the first block
function maskCnic(?string $cnic): string
{
    if (!$cnic) {
        return '-';
    }
    $parts = explode('-', $cnic);
    return $parts[0] . '-XXXXXXX-X';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <title>Membership Verification</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .badge { display:inline-block; padding:6px 14px; border-radius:999px; font-weight:700; font-size:0.85rem; }
        .badge.valid { background:#dcfce7; color:#15803d; }
        .badge.invalid { background:#fef2f2; color:#b91c1c; }
        .photo { width:100px; height:120px; object-fit:cover; border-radius:8px; border:1px solid #e5e7eb; margin:0 auto 16px; display:block; }
        .row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f0f0f0; font-size:0.9rem; }
        .row .label { color:#6b7280; }
        .row .value { font-weight:600; text-align:right; }
    </style>
</head>
<body>
<div class="card-box" style="max-width: 400px; text-align:center;">
    <?php if ($code === ''): ?>
        <h1>Verify Membership Card</h1>
        <p class="subtitle">Scan a card's QR code, or type the membership number below.</p>

        <button type="button" id="camera-btn" onclick="startCamera()" style="background:#4f46e5;">
            📷 Scan with Camera
        </button>

        <div id="camera-wrap" style="display:none; margin-top:12px;">
            <video id="video" style="width:100%; border-radius:8px; background:#000;" playsinline></video>
            <canvas id="canvas" style="display:none;"></canvas>
            <p id="camera-status" style="font-size:0.8rem; color:#6b7280; margin-top:8px;">Point the camera at the card's QR code…</p>
            <button type="button" onclick="stopCamera()" style="background:#e5e7eb; color:#1f2430;">Cancel</button>
        </div>

        <form method="GET" style="margin-top:20px;">
            <label for="code">Membership Number</label>
            <input type="text" id="code" name="code" placeholder="MEM-2026-000123 or scan with a barcode reader" autofocus autocomplete="off" required>
            <button type="submit">Verify</button>
        </form>
        <p style="font-size:0.75rem; color:#9ca3af; margin-top:8px;">
            Using a USB/Bluetooth barcode scanner? Just scan — it types into the box above and submits automatically.
        </p>

        <script src="https://cdn.jsdelivr.net/npm/jsqr/dist/jsQR.js"></script>
        <script>
            // Hardware/Bluetooth scanners act like a keyboard: they type fast, then send Enter.
            // Keeping the field focused and submitting on Enter is all that's needed — no button required.
            const codeInput = document.getElementById('code');
            codeInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.target.form.submit();
                }
            });

            let stream = null;
            let frameCount = 0;

            function startCamera() {
                if (typeof jsQR === 'undefined') {
                    document.getElementById('camera-status').textContent =
                        'QR scanning library failed to load — check your internet connection and try again.';
                    return;
                }

                document.getElementById('camera-btn').style.display = 'none';
                document.getElementById('camera-wrap').style.display = 'block';
                frameCount = 0;
                const video = document.getElementById('video');
                const canvas = document.getElementById('canvas');
                const ctx = canvas.getContext('2d', { willReadFrequently: true });

                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                    .then(function (s) {
                        stream = s;
                        video.srcObject = stream;
                        video.play();
                        requestAnimationFrame(tick);
                    })
                    .catch(function (err) {
                        document.getElementById('camera-status').textContent = 'Camera access failed: ' + err.message;
                    });

                function tick() {
                    if (!stream) return; // cancelled
                    try {
                        if (video.readyState === video.HAVE_ENOUGH_DATA) {
                            frameCount++;
                            if (frameCount % 30 === 0) {
                                console.log('[QR scan] scanning, frame', frameCount, 'size', video.videoWidth + 'x' + video.videoHeight);
                            }
                            canvas.width = video.videoWidth;
                            canvas.height = video.videoHeight;
                            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                            const result = jsQR(imageData.data, imageData.width, imageData.height, {
                                inversionAttempts: 'attemptBoth',
                            });
                            if (result && result.data) {
                                console.log('[QR scan] Detected:', result.data);
                                // Guard against false positives (webcams can misread background
                                // clutter as a QR pattern) — only act on something that actually
                                // looks like our membership code or verify link.
                                const looksValid = /^MEM-/.test(result.data) || result.data.includes('code=');
                                if (looksValid) {
                                    stopCamera();
                                    window.location.href = 'verify.php?code=' + encodeURIComponent(result.data);
                                    return;
                                } else {
                                    document.getElementById('camera-status').textContent =
                                        'Detected something that isn\'t a membership QR code — keep scanning…';
                                }
                            }
                        }
                    } catch (err) {
                        // Keep camera-wrap visible so the error is actually seen (stopCamera()
                        // would hide it, along with this message, before the user can read it).
                        document.getElementById('camera-status').textContent = 'Scanner error: ' + err.message;
                        document.getElementById('camera-status').style.color = '#b91c1c';
                        if (stream) {
                            stream.getTracks().forEach(function (t) { t.stop(); });
                            stream = null;
                        }
                        return;
                    }
                    requestAnimationFrame(tick);
                }
            }

            function stopCamera() {
                if (stream) {
                    stream.getTracks().forEach(function (t) { t.stop(); });
                    stream = null;
                }
                document.getElementById('camera-btn').style.display = 'inline-block';
                document.getElementById('camera-wrap').style.display = 'none';
            }
        </script>

    <?php elseif ($notFound): ?>
        <h1>Not Found</h1>
        <div class="badge invalid">✕ Invalid Membership Number</div>
        <p class="subtitle" style="margin-top:16px;">No membership found for "<?= htmlspecialchars($code) ?>".</p>
        <p><a href="verify.php" style="color:#4f46e5;">Try another code</a></p>

    <?php else: ?>
    <?php if (!empty($member['photo_path']) && file_exists(__DIR__ . '/../' . $member['photo_path'])): ?>
    <img class="photo" src="../<?= htmlspecialchars($member['photo_path']) ?>" alt="Member photo">
    <?php else: ?>
        <div class="photo" style="display:flex; align-items:center; justify-content:center; background:#f3f4f6; color:#9ca3af; font-size:0.75rem;">
            No Photo
        </div>
    <?php endif; ?>

        <h1 style="margin-bottom:4px;"><?= htmlspecialchars($member['full_name']) ?></h1>
        <p class="subtitle" style="margin-top:0;"><?= htmlspecialchars($member['category_name']) ?></p>

        <?php if ($isValid): ?>
        <div class="badge valid">✓ Valid Membership</div>
    <?php else: ?>
        <div class="badge invalid">✕ <?= $isExpired ? 'Expired' : ucfirst($member['status']) ?></div>
    <?php endif; ?>

        <div style="margin-top:20px; text-align:left;">
            <div class="row"><span class="label">Membership No</span><span class="value"><?= htmlspecialchars($member['member_code']) ?></span></div>
            <div class="row"><span class="label">Parents/Spouse</span><span class="value"><?= htmlspecialchars($member['parent_spouse_name'] ?: '-') ?></span></div>
            <div class="row"><span class="label">CNIC</span><span class="value"><?= htmlspecialchars(maskCnic($member['cnic_no'])) ?></span></div>
            <div class="row"><span class="label">Valid Upto</span><span class="value"><?= htmlspecialchars(date('d M Y', strtotime($member['valid_upto']))) ?></span></div>
        </div>

        <p style="margin-top:20px;"><a href="verify.php" style="color:#4f46e5; font-size:0.85rem;">Verify another card</a></p>
    <?php endif; ?>
</div>
</body>
</html>