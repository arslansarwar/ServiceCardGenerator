<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/Member.php';
require_once __DIR__ . '/../includes/Category.php';
require_once __DIR__ . '/../includes/CardRenderer.php';

$code = $_GET['code'] ?? '';
$pdo = getDbConnection();
$member = $code ? getMemberByCode($pdo, $code) : null;

if (!$member) {
    http_response_code(404);
    die('Member not found.');
}

if ($member['status'] !== 'active') {
    http_response_code(403);
    die('This membership is not active.');
}

$familyMembers = $member['back_layout'] === 'family' ? getFamilyMembers($pdo, (int) $member['id']) : [];
$instructions = $member['back_layout'] === 'facilities' ? getAllInstructions($pdo) : [];
$facilities = $member['back_layout'] === 'facilities' ? getAllFacilities($pdo) : [];

$cardWidth = 85.6;
$cardHeight = 54;

$pdf = new TCPDF('L', 'mm', [$cardWidth, $cardHeight], true, 'UTF-8', false);
$pdf->SetCreator(ORG_NAME);
$pdf->SetAuthor(ORG_NAME);
$pdf->SetTitle('Membership Card - ' . $member['member_code']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);

renderCardFront($pdf, $member, $cardWidth, $cardHeight);
renderCardBack($pdf, $member, $familyMembers, $instructions, $facilities, $cardWidth, $cardHeight);

$pdf->Output('membership-card-' . $member['member_code'] . '.pdf', 'D');
