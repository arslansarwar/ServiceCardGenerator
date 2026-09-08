<?php

require_once __DIR__ . '/../config/app.php';

/**
 * Convert a #RRGGBB hex string to an [R, G, B] array. Falls back to dark green on bad input.
 */
function hexToRgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) {
        return [28, 59, 46];
    }
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}

/**
 * Pick black or white text depending on background brightness, so text stays readable
 * on both light categories (e.g. white) and dark ones (e.g. green).
 */
function contrastTextColor(array $rgb): array
{
    [$r, $g, $b] = $rgb;
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance > 0.6 ? [31, 36, 48] : [255, 255, 255];
}

/**
 * Draws a small circular icon with a 1-2 letter monogram. Simple, dependency-free
 * stand-in for the line icons in the reference design — swap for an icon image/font
 * later if you want a pixel-exact match.
 */
function drawIconBullet(TCPDF $pdf, float $x, float $y, float $size, string $letters, array $accentRgb): void
{
    $pdf->SetFillColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
    $pdf->Circle($x + $size / 2, $y + $size / 2, $size / 2, 0, 360, 'F');
    $textColor = contrastTextColor($accentRgb);
    $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
    $pdf->SetFont('helvetica', 'B', 5);
    $pdf->SetXY($x, $y + $size / 2 - 1.6);
    $pdf->Cell($size, 3.2, $letters, 0, 0, 'C');
}

/**
 * Renders one "icon + label + value" row on the front of the card.
 */
function drawDataRow(TCPDF $pdf, float $x, float $y, float $rowWidth, string $icon, string $label, string $value, array $accentRgb, array $textRgb): void
{
    drawIconBullet($pdf, $x, $y, 4.2, $icon, $accentRgb);

    $pdf->SetTextColor($textRgb[0], $textRgb[1], $textRgb[2]);
    $pdf->SetFont('helvetica', 'B', 5.2);
    $pdf->SetXY($x + 5.5, $y);
    $pdf->Cell($rowWidth - 5.5, 3, strtoupper($label), 0, 0, 'L');

    $pdf->SetFont('helvetica', '', 6.3);
    $pdf->SetXY($x + 5.5, $y + 3);
    $pdf->MultiCell($rowWidth - 5.5, 3, $value, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
}

/**
 * FRONT of the card: header band, QR code, logo monogram, member photo, data rows, footer.
 */
function renderCardFront(TCPDF $pdf, array $member, float $cardWidth, float $cardHeight): void
{
    $accentRgb = hexToRgb($member['color_hex']);
    $textRgb = contrastTextColor($accentRgb);

    $pdf->AddPage();

    // Header band
    $headerHeight = 12;
    $pdf->SetFillColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
    $pdf->Rect(0, 0, $cardWidth, $headerHeight, 'F');

    // QR code, top-left — encodes a link to the public verify page so scanning
    // opens the member's info directly in a browser
    $qrSize = 10;
    $style = ['border' => false, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => [255, 255, 255]];
    $verifyUrl = appDirUrl() . '/verify.php?code=' . urlencode($member['member_code']);
    $pdf->write2DBarcode($verifyUrl, 'QRCODE,M', 1.5, 1, $qrSize, $qrSize, $style, 'N');

    // Org logo monogram, top-right
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Circle($cardWidth - 7, 6, 4.5, 0, 360, 'F');
    $pdf->SetTextColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
    $pdf->SetFont('helvetica', 'B', 5);
    $pdf->SetXY($cardWidth - 11.5, 4.4);
    $pdf->Cell(9, 3.2, ORG_SHORT, 0, 0, 'C');

    // Header text
    $pdf->SetTextColor($textRgb[0], $textRgb[1], $textRgb[2]);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetXY($qrSize + 3, 1.5);
    $pdf->Cell($cardWidth - $qrSize - 15, 5, 'MEMBERSHIP CARD', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 4.8);
    $pdf->SetXY($qrSize + 3, 6.5);
    $pdf->Cell($cardWidth - $qrSize - 15, 3, strtoupper(ORG_NAME), 0, 0, 'L');

    // Photo, right side
    $photoW = 17;
    $photoH = 20;
    $photoX = $cardWidth - $photoW - 3;
    $photoY = $headerHeight + 3;
    if (!empty($member['photo_path']) && file_exists(__DIR__ . '/../' . $member['photo_path'])) {
        $pdf->Image(__DIR__ . '/../' . $member['photo_path'], $photoX, $photoY, $photoW, $photoH, '', '', '', true, 300, '', false, false, 0, 'CM', false, false);
    } else {
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Rect($photoX, $photoY, $photoW, $photoH, 'D');
        $pdf->SetFont('helvetica', '', 5);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->SetXY($photoX, $photoY + $photoH / 2 - 1.5);
        $pdf->Cell($photoW, 3, 'PHOTO', 0, 0, 'C');
    }

    // Data rows on the left
    $rowX = 3;
    $rowWidth = $photoX - $rowX - 2;
    $rowY = $headerHeight + 2.5;
    $rowGap = 6.3;

    $rows = [
        ['N', 'Name', $member['full_name']],
        ['P', 'Parents/Spouse', $member['parent_spouse_name'] ?: '-'],
        ['C', 'CNIC No', $member['cnic_no'] ?: '-'],
        ['M', 'Membership No', $member['member_code']],
        ['ST', 'Category', $member['category_name']],
    ];

    foreach ($rows as $i => [$icon, $label, $value]) {
        drawDataRow($pdf, $rowX, $rowY + $i * $rowGap, $rowWidth, $icon, $label, $value, $accentRgb, $textRgb);
    }

    // Footer band: validity + signature
    $footerHeight = 9;
    $footerY = $cardHeight - $footerHeight;
    $pdf->SetFillColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
    $pdf->Rect(0, $footerY, $cardWidth, $footerHeight, 'F');

    $pdf->SetTextColor($textRgb[0], $textRgb[1], $textRgb[2]);
    $pdf->SetFont('helvetica', 'B', 5.5);
    $pdf->SetXY(3, $footerY + 1.2);
    $pdf->Cell($cardWidth / 2, 3, 'VALID UPTO', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 5.5);
    $pdf->SetXY(3, $footerY + 4.5);
    $pdf->Cell($cardWidth / 2, 3, date('d M Y', strtotime($member['valid_upto'])), 0, 0, 'L');

    $pdf->SetFont('helvetica', 'I', 4.6);
    $pdf->SetXY($cardWidth / 2, $footerY + 5.3);
    $pdf->Cell($cardWidth / 2 - 3, 3, 'Issuing Authority', 0, 0, 'R');
}

/**
 * BACK of the card: either a family-members table, or instructions + facilities list,
 * depending on the member's category back_layout.
 */
function renderCardBack(TCPDF $pdf, array $member, array $familyMembers, array $instructions, array $facilities, float $cardWidth, float $cardHeight): void
{
    $accentRgb = hexToRgb($member['color_hex']);
    $textRgb = contrastTextColor($accentRgb);

    $pdf->AddPage();
    $pdf->SetTextColor(31, 36, 48);

    if ($member['back_layout'] === 'family') {
        // Header
        $pdf->SetFillColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
        $pdf->Rect(0, 0, $cardWidth, 7, 'F');
        $pdf->SetTextColor($textRgb[0], $textRgb[1], $textRgb[2]);
        $pdf->SetFont('helvetica', 'B', 6.5);
        $pdf->SetXY(0, 1.3);
        $pdf->Cell($cardWidth, 4, 'DETAILS OF FAMILY MEMBERS', 0, 1, 'C');

        // Table
        $colWidths = [8, 40, 24, 12];
        $headers = ['#', 'Name', 'Relationship', 'Year'];
        $x = 1;
        $y = 9;
        $pdf->SetFont('helvetica', 'B', 5);
        $pdf->SetTextColor(31, 36, 48);
        foreach ($headers as $i => $h) {
            $pdf->SetXY($x, $y);
            $pdf->Cell($colWidths[$i], 4, $h, 1, 0, 'C');
            $x += $colWidths[$i];
        }
        $y += 4;

        $pdf->SetFont('helvetica', '', 5);
        $maxRows = 5;
        for ($i = 0; $i < $maxRows; $i++) {
            $x = 1;
            $fam = $familyMembers[$i] ?? null;
            $cells = $fam ? [(string)($i + 1), $fam['name'], $fam['relationship'], $fam['birth_year']] : [(string)($i + 1), '', '', ''];
            foreach ($cells as $ci => $val) {
                $pdf->SetXY($x, $y);
                $pdf->Cell($colWidths[$ci], 4, (string) $val, 1, 0, 'C');
                $x += $colWidths[$ci];
            }
            $y += 4;
        }

        // Footer banner
        $pdf->SetFillColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
        $pdf->Rect(0, $cardHeight - 5, $cardWidth, 5, 'F');
        $pdf->SetTextColor($textRgb[0], $textRgb[1], $textRgb[2]);
        $pdf->SetFont('helvetica', '', 4.3);
        $pdf->SetXY(2, $cardHeight - 4.2);
        $pdf->Cell($cardWidth - 4, 3, 'If found, please return to ' . ORG_NAME, 0, 0, 'L');

    } else { // facilities layout
        $pdf->SetFillColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
        $pdf->Rect(0, 0, $cardWidth, 6, 'F');
        $pdf->SetTextColor($textRgb[0], $textRgb[1], $textRgb[2]);
        $pdf->SetFont('helvetica', 'B', 5.5);
        $pdf->SetXY(0, 1.2);
        $pdf->Cell($cardWidth, 3.5, 'INSTRUCTIONS FOR MEMBERS', 0, 1, 'C');

        $pdf->SetTextColor(31, 36, 48);
        $pdf->SetFont('helvetica', '', 4.3);
        $y = 7.5;
        foreach (array_slice($instructions, 0, 4) as $instr) {
            $pdf->SetXY(2, $y);
            $pdf->MultiCell($cardWidth - 4, 3, '• ' . $instr['text'], 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
            $y += 3.2;
        }

        $y += 1;
        $pdf->SetFillColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
        $pdf->Rect(0, $y, $cardWidth, 4, 'F');
        $pdf->SetTextColor($textRgb[0], $textRgb[1], $textRgb[2]);
        $pdf->SetFont('helvetica', 'B', 4.8);
        $pdf->SetXY(0, $y + 0.7);
        $pdf->Cell($cardWidth, 3, 'MEMBER FACILITIES & DISCOUNTS', 0, 1, 'C');
        $y += 5;

        // Logo image if the admin uploaded one for this facility (and has rights to use it),
        // otherwise a plain text badge with name + discount.
        $pdf->SetTextColor(31, 36, 48);
        $pdf->SetFont('helvetica', '', 4.5);
        $perRow = 3;
        $colW = ($cardWidth - 4) / $perRow;
        $x = 2;
        $col = 0;
        foreach (array_slice($facilities, 0, 6) as $fac) {
            $hasLogo = !empty($fac['logo_path']) && file_exists(__DIR__ . '/../' . $fac['logo_path']);
            if ($hasLogo) {
                $logoSize = 6;
                $pdf->Image(__DIR__ . '/../' . $fac['logo_path'], $x + ($colW - $logoSize) / 2, $y, $logoSize, $logoSize, '', '', '', true, 300, '', false, false, 0, '', false, false);
                $pdf->SetXY($x, $y + $logoSize + 0.5);
                $label = $fac['discount_text'] ?: $fac['name'];
                $pdf->MultiCell($colW - 1, 3, $label, 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'T');
            } else {
                $pdf->SetXY($x, $y);
                $label = $fac['name'] . ($fac['discount_text'] ? ' (' . $fac['discount_text'] . ')' : '');
                $pdf->MultiCell($colW - 1, 3, $label, 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'T');
            }
            $col++;
            $x += $colW;
            if ($col >= $perRow) {
                $col = 0;
                $x = 2;
                $y += 9;
            }
        }

        // Footer banner
        $pdf->SetFillColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
        $pdf->Rect(0, $cardHeight - 5, $cardWidth, 5, 'F');
        $pdf->SetTextColor($textRgb[0], $textRgb[1], $textRgb[2]);
        $pdf->SetFont('helvetica', '', 4.3);
        $pdf->SetXY(2, $cardHeight - 4.2);
        $pdf->Cell($cardWidth - 4, 3, 'If found, please return to ' . ORG_NAME, 0, 0, 'L');
    }
}