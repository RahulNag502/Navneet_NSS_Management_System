<?php

require_once('tcpdf/tcpdf.php');

function generateCertificate($name, $type, $code)
{
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

    $pdf->SetCreator('NAVNEET NSS');
    $pdf->SetAuthor('NAVNEET COLLEGE');
    $pdf->SetTitle('NSS Certificate');

    $pdf->AddPage();

    // Certificate Border
    $pdf->SetLineWidth(3);
    $pdf->Rect(10, 10, 277, 190);

    // College Logo
    $logo = __DIR__ . "/assets/images/college_logo.png";
    $pdf->Image($logo, 130, 18, 35);

    // Title
    $pdf->SetFont('times', 'B', 36);
    $pdf->Cell(0, 60, 'CERTIFICATE', 0, 1, 'C');

    // Subtitle
    $pdf->SetFont('times', '', 16);
    $pdf->Cell(0, -20, 'National Service Scheme (NSS)', 0, 1, 'C');

    $pdf->Ln(20);

    // Body text
    $pdf->SetFont('times', '', 16);

    $text = "This is to certify that";

    $pdf->Cell(0, 10, $text, 0, 1, 'C');

    // Volunteer Name
    $pdf->SetFont('times', 'B', 26);
    $pdf->Cell(0, 10, strtoupper($name), 0, 1, 'C');

    $pdf->Ln(5);

    // Certificate Text
    $pdf->SetFont('times', '', 16);

    $desc = "has successfully completed $type of voluntary service 
    under the National Service Scheme (NSS) at NAVNEET COLLEGE 
    and is awarded this certificate in recognition of dedicated service.";

    $pdf->MultiCell(0, 10, $desc, 0, 'C');

    $pdf->Ln(10);

    // Certificate Code
    $pdf->SetFont('times', 'I', 12);
    $pdf->Cell(0, 10, "Certificate Code: $code", 0, 1, 'C');

    // Signature section
    $pdf->Ln(15);

    $pdf->SetFont('times', '', 14);

    $pdf->Cell(90, 10, 'Program Officer', 0, 0, 'C');
    $pdf->Cell(90, 10, 'Principal', 0, 0, 'C');
    $pdf->Cell(90, 10, 'NSS Coordinator', 0, 1, 'C');

    // Save PDF
    $filename = "$code.pdf";

    $path = __DIR__ . "/../certificates/" . $filename;

    $pdf->Output($path, 'F');

    return $path;
}