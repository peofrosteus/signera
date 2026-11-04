<?php
declare(strict_types=1);

namespace Signera\Pdf;

use setasign\Fpdi\Tcpdf\Fpdi;

/** FPDI/TCPDF med en sidfot som stämplar varje sida, även sidor som skapas av automatisk sidbrytning. */
final class StampedPdf extends Fpdi
{
    /** Text före sidnumreringen. Tom sträng = ingen stämpel. */
    public string $stamp = '';

    /** Totalt antal sidor, känt från en första provkörning. 0 = utelämna "av N". */
    public int $totalPages = 0;

    public function Footer(): void
    {
        if ($this->stamp === '') {
            return;
        }
        // TCPDF:s sidnummer-alias fungerar inte med inbäddade Unicode-typsnitt, därför riktiga siffror.
        $text = $this->stamp . ' · Sida ' . $this->getPage() . ($this->totalPages > 0 ? ' av ' . $this->totalPages : '');
        $width = $this->getPageWidth() - 16;

        // Krymp texten på smala sidor så att den alltid ryms på en rad.
        $size = 6.5;
        $this->SetFont('dejavusans', '', $size);
        while ($size > 4 && $this->GetStringWidth($text) > $width) {
            $size -= 0.25;
            $this->SetFont('dejavusans', '', $size);
        }
        $this->SetTextColor(110, 110, 110);
        $this->SetXY(8, $this->getPageHeight() - 7);
        $this->Cell($width, 4, $text, 0, 0, 'C');
    }
}
