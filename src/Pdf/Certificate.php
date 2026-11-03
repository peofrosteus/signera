<?php
declare(strict_types=1);

namespace Signera\Pdf;

use setasign\Fpdi\Tcpdf\Fpdi;
use Signera\App;
use Signera\Personnummer;

final class Certificate
{
    /** Antal sidor i PDF:en. Kastar om FPDI inte kan läsa filen, och då går den inte heller att slutföra. */
    public static function pageCount(string $file): int
    {
        $pdf = self::newPdf();
        $count = $pdf->setSourceFile($file);
        if ($count < 1) {
            throw new \RuntimeException('PDF:en saknar sidor.');
        }
        // Importera alla sidor redan nu så att fel upptäcks vid uppladdning och inte efter sista signaturen.
        for ($i = 1; $i <= $count; $i++) {
            $pdf->importPage($i);
        }
        return $count;
    }

    /** Skapar originalet plus en sista sida med signeringsintyget och skriver till $target. */
    public static function build(array $document, array $signers, array $creator, string $source, string $target): void
    {
        $pdf = self::newPdf();
        $pdf->SetTitle($document['title']);
        $pdf->SetCreator((string) App::config('app_name', 'Signera'));

        $count = $pdf->setSourceFile($source);
        for ($i = 1; $i <= $count; $i++) {
            $tpl = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tpl);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);
        }

        $pdf->AddPage('P', 'A4');
        $pdf->SetMargins(18, 18, 18);
        $pdf->SetY(18);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->writeHTML(self::html($document, $signers, $creator), true, false, true);

        $pdf->Output($target, 'F');
    }

    private static function newPdf(): Fpdi
    {
        $pdf = new Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(true, 18);
        return $pdf;
    }

    private static function html(array $document, array $signers, array $creator): string
    {
        $mask = (bool) App::config('mask_personnummer_on_certificate', true);
        $rows = '';
        foreach ($signers as $s) {
            $pnr = $mask ? Personnummer::mask($s['personnummer']) : $s['personnummer'];
            $rows .= '<tr>'
                . '<td><b>' . e($s['name']) . '</b><br><span style="color:#555">' . e($s['email']) . '</span></td>'
                . '<td>' . e($pnr) . '</td>'
                . '<td>' . e(date('Y-m-d H:i:s', strtotime($s['signed_at']))) . '</td>'
                . '<td>' . e($s['ip']) . '</td>'
                . '</tr>';
        }

        $title = e($document['title']);
        $publicId = e($document['public_id']);
        $file = e($document['original_filename']);
        $pages = (int) $document['page_count'];
        $sha = e($document['sha256']);
        $sender = e($creator['name'] . ' (' . $creator['email'] . ')');
        $created = e(date('Y-m-d H:i', strtotime($document['created_at'])));
        $completed = e(date('Y-m-d H:i:s', strtotime($document['completed_at'])));
        $tz = e(date('T P'));

        return <<<HTML
<h1 style="font-size:18pt">Signeringsintyg</h1>
<p style="color:#555">Detta intyg är en del av det signerade dokumentet och visar vilka som har signerat och när.</p>
<table cellpadding="4" style="font-size:9.5pt">
  <tr><td width="30%" style="color:#555">Dokument</td><td width="70%"><b>{$title}</b></td></tr>
  <tr><td style="color:#555">Dokument-ID</td><td>{$publicId}</td></tr>
  <tr><td style="color:#555">Originalfil</td><td>{$file} ({$pages} sidor)</td></tr>
  <tr><td style="color:#555">SHA-256 (original)</td><td style="font-size:8pt">{$sha}</td></tr>
  <tr><td style="color:#555">Skickat av</td><td>{$sender}, {$created}</td></tr>
  <tr><td style="color:#555">Slutfört</td><td>{$completed}</td></tr>
</table>
<h2 style="font-size:13pt">Undertecknare</h2>
<table cellpadding="5" border="0" style="font-size:9pt">
  <tr style="background-color:#eeeeee;font-weight:bold">
    <td width="40%">Namn / e-post</td><td width="20%">Personnummer</td><td width="22%">Signerat</td><td width="18%">IP-adress</td>
  </tr>
  {$rows}
</table>
<p style="font-size:8.5pt;color:#555">Varje undertecknare har fått en personlig länk till sin e-postadress, läst dokumentet,
bekräftat de fyra sista siffrorna i sitt personnummer och därefter aktivt valt att signera.
Tidpunkter anges i {$tz}.</p>
HTML;
    }
}
