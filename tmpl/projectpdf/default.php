<?php
/**
 * @package    Com_Fisc_calculator
 */
defined('_JEXEC') or die;

jimport('mpdf.mpdf');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Mpdf\HTMLParserMode;

// ===== Eingangs-Daten =====
$app        = Factory::getApplication();
$projectId  = (int) ($this->projectId ?? $app->input->getInt('id'));
$cases      = $this->cases ?? [];
$caseItems  = $this->caseItems ?? [];
$project    = $this->project ?? null;

$projName   = $project->name ?? ('Projekt #' . $projectId);
$customer   = $project->customer_name ?? '';
$projDesc   = $project->description ?? '';
$reporttype = (int) $app->input->getInt('reporttype', 2);

$now        = Factory::getDate();
$dateHuman  = $now->format('d.m.Y');
$userName   = Factory::getUser()->name ?? '';
$uuid       = uuid();

// ===== Helpers =====
function fmtEUR($n) { return number_format((float)$n, 2, ',', '.'); }
function clamp($v, $min, $max) { return max($min, min($max, $v)); }
function priceBarSvg(float $min, float $max, float $med): string {
    $min   = max(0, $min);
    $range = ($max > $min) ? ($max - $min) : 0.0;
    $pos   = $range > 0 ? clamp(($med - $min) / $range, 0.0, 1.0) : 0.0;
    $x     = (int) round($pos * 100); // 0..100

    return
      '<svg width="100%" height="10" viewBox="0 0 100 10" xmlns="http://www.w3.org/2000/svg">' .
        '<rect x="0" y="4" width="100" height="2" fill="#eeeeee"/>' .
        '<line x1="'.$x.'" y1="1" x2="'.$x.'" y2="9" stroke="#EE8157" stroke-width="2"/>' .
      '</svg>';
}
$tablebgclr = rgba_to_hex_over_white(226, 250, 230, 0.2);

// ===== CONTENT-CSS =====
$contentCss = <<<CSS
* { box-sizing: border-box; }
body { font-family: dejavusans, sans-serif; font-size: 10pt; color: #222; }
.small { font-size: 8pt; color:#666; }
.h2 { font-size: 14pt; }
.hr { border-top: 1px solid #ddd; height:0; margin: 3mm 0 4mm; }

/* Headerzeile: Balken + Seitenzahl außen, tabellenbasiert */
.hline { width:100%; border-collapse:collapse; }
.hline td { vertical-align:middle; padding:0; }
.hbar  { width:22mm; height:3mm; background:#8FBF21; display:inline-block; }
.hpage { font-size:9pt; color:#444; white-space:nowrap; }

/* Balken + Seitenzahl "------ 1 von 5" */
.pagebar { width:100%; }
.pagebar .bar  { display:inline-block; width:80%; height:0.3mm; background:#47A0B1; vertical-align:middle; }
.pagebar .num  { display:inline-block; margin-left:3mm; font-size:9pt; color:#444; vertical-align:middle; }

/* Außenseiten-Ausrichtung (spiegelnd via mirrorMargins) */
.h-odd  { text-align:right; }  /* ungerade Seiten: rechts außen */
.h-even { text-align:left; }   /* gerade Seiten: links außen */

.header, .footer { width: 100%; }
.header .left { float:left; width: 70%; }
.header .right { float:right; width: 30%; text-align: right; }
.clearfix { clear: both; }

.case-card { border: 1px solid #eee; border-radius: 4px; margin-bottom: 10mm; }
.case-card .card-head { padding: 6mm; background: #fafafa; border-bottom: 1px solid #eee; }
.case-card .case-title { font-size: 13pt; margin: 0; }
.case-card .card-body { padding: 4mm 6mm; }

.tbl { width: 100%; border-collapse: collapse; background:$tablebgclr}
.tbl th, .tbl td { border-bottom: 1px solid #eee; padding: 3mm 2mm; vertical-align: middle; }
.tbl th { font-size: 9pt; color:#555; font-weight: bold; }
.tbl tfoot td { font-weight: bold; }
.num { text-align: right; white-space: nowrap; font-size: 7pt; }

/* Case-Kopf (ID + Titel nebeneinander, robust via table) */
.case-head { width:100%; border-collapse:collapse; margin: 5mm 0; }
.case-head td { vertical-align:middle; padding:0; }
.case-head .case-id {
  width:22mm; height:16mm; background:#4B7B93; color:#fff;
  text-align:center; font-size:16pt; font-weight:bold;
}
.case-head .case-title {
  padding-left:5mm; color:#47A0B1; font-size:14pt;border-bottom: 1px solid #47A0B1; border-top: 1px solid #47A0B1;
}

/* Preisinfo als kleine Tabelle – ohne floats */
.pi { width: 50mm; border-collapse:collapse; }
.pi td { padding:0; border-bottom:none!important;}
.pi .minmax td { font-size:7pt; color:#777; }
.pi .bar { padding-top:1mm; }
.pi .med { text-align:center; font-size:7pt; color:#777; }

/* Rest wie gehabt … */
.tbl .num { text-align:right; white-space:nowrap; font-size:7pt; }
.logo-small { height: 8mm; }.item-title {	width:90%;	padding: 0.5mm 5%;	border-top: 1px solid #47A0B1;	border-bottom: 1px solid #47A0B1;	margin-top: 5mm;	margin-bottom: 5mm;}.case-cid {	display:inline-block;	width:2cm;	height:2.3cm;	text-align:center;	vertical-align:middle;	background:#4B7B93;	color:#fff;	float:left;	font-size: 32px;	font-weight: bold;	margin-right:5mm;}.case-title {display:inline-block;	float:left;	width: 15cm;	padding-left:5mm;	color:#47A0B1;	font-size: 14pt;}.cprice {text-align:right;}
CSS;

// ===== CONTENT-HTML (nur Inhalt, keine htmlpageheader/footer Tags!) =====
$contentHtml = '<html><head></head><body>';

if ($reporttype === 1) {
    $contentHtml .= '<div class="h2">'.Text::_("COM_FISC_SALES_PROJECT_CALC_BASE").'</div>';
    $contentHtml .= '<div class="small">Für die Berechnung der Kalkulationen wurden folgende Werte festgelegt.</div><div class="hr"></div>';
}

if ($reporttype === 2) {

    if (!empty($cases)) {
        foreach ($cases as $c) {
            $cid   = (int) $c->id;
            $title = $c->case_name ?? ('Fall #' . $cid);
            $items = $caseItems[$cid] ?? [];

            $sumMin=0; $sumMax=0; $sumCogs=0; $sumFree=0; $sumMargin=0;

            $contentHtml .= '            <table class="case-head">
    <tr>
      <td class="case-id">'.(int)$cid.'</td>
      <td class="case-title">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</td>
    </tr>
  </table>
            
                <table class="tbl">
                  <thead>
                    <tr>
                      <th style="width:52%;">Title</th>
                      <th style="width:12%;">Preis</th>
                      <th style="width:18%;">Preisinfo</th>
                      <th style="width:9%;">EKP</th>
                      <th style="width:9%;">Marge</th>
                    </tr>
                  </thead>
                  <tbody>
            ';

            if (!empty($items)) {
                foreach ($items as $it) {
                    $min  = max(0, (float)($it->min_price  ?? 0));
                    $max  = (float)($it->max_price  ?? 0);
                    $med  = (float)($it->mean_price ?? (($min && $max) ? ($min + $max)/2 : $min));
                    $cogs = (float)($it->cogs_lc ?? 0);
                    $free = (float)($it->free_price1 ?? 0);
                    $margin = $cogs ? ($free - $cogs) : 0;

                    $sumMin    += $min;
                    $sumMax    += $max;
                    $sumCogs   += $cogs;
                    $sumFree   += $free;
                    $sumMargin += $margin;

                    $bar = priceBarSvg($min, $max, $med);

                    $contentHtml .= '
                    <tr>
                      <td><span class="num">'.htmlspecialchars($it->ref ?? '', ENT_QUOTES, 'UTF-8').'</span><br />
                      '.htmlspecialchars($it->title ?? '', ENT_QUOTES, 'UTF-8').'</td>
                      <td class="cprice">'.fmtEUR($free).'</td>
                      <td>
						  <table class="pi">
						    <tr class="minmax">
						      <td>'.fmtEUR($min).'</td>
						      <td style="text-align:right">'.fmtEUR($max).'</td>
						    </tr>
						    <tr>
						      <td colspan="2" class="bar">'.$bar.'</td>
						    </tr>
						    <tr>
						      <td colspan="2" class="med">'.fmtEUR($med).'</td>
						    </tr>
						  </table>
                      </td>
                      <td class="num">'.fmtEUR($cogs).'</td>
                      <td class="num">'.($cogs ? fmtEUR($margin) : '–').'</td>
                    </tr>';
                }
            } else {
                $contentHtml .= '
                <tr>
                  <td colspan="6" class="small" style="text-align:center;color:#888;padding:6mm;">Keine Einträge gefunden.</td>
                </tr>';
            }

            $contentHtml .= '
                    <tr>
                      <td colspan="2" style="text-align:right"><b>'.fmtEUR($sumFree).'</b></td>
                      <td>                      
                        <table class="pi">
                          <tr class="minmax">
                            <td><b>'.fmtEUR($sumMin).'</b></td>
                            <td style="text-align:right"><b>'.fmtEUR($sumMax).'</b></td>
                          </tr>
                        </table>
                      </td>
                      <td class="num">'.fmtEUR($sumCogs).'</td>
                      <td class="num">'.fmtEUR($sumMargin).'</td>
                    </tr>
                  </tbody>
                </table>
              ';
        }
    } else {
        $contentHtml .= '<div class="small" style="color:#888;margin-top:6mm;">Für dieses Projekt sind noch keine Fallbeispiele gespeichert.</div>';
    }
}

$contentHtml .= '</body></html>';

// ===== mPDF laden =====
if (!class_exists('\\Mpdf\\Mpdf')) {
    require_once JPATH_SITE . '/vendor/autoload.php';
}
$mpdf = new \Mpdf\Mpdf([
    'format'         => 'A4',
    'margin_left'    => 12,
    'margin_right'   => 12,
    'margin_top'     => 12,
    'margin_bottom'  => 12,
    'margin_header'  => 8,
    'margin_footer'  => 8,
    'default_font'   => 'dejavusans',
    'mirrorMargins'  => true, // Außen/Innen spiegeln
]);

$mpdf->SetCreator('FiSC Report Generator');
$mpdf->SetAuthor($userName ?: 'FiSC');
$mpdf->SetTitle($projName . ' – ' . $customer);
$mpdf->SetDisplayMode('fullpage');
$mpdf->mirrorMargins = true;

// ===== Deckblatt laden =====
require_once __DIR__ . '/pdf_cover.php';
[$coverCss, $coverHtml] = fiscBuildCover([
    'projName'  => $projName,
    'customer'  => $customer,
    'dateHuman' => $dateHuman,
    'uuid'      => $uuid,
    'projDesc'  => $projDesc,
]);

// --- Seite 1: Deckblatt (ohne Header/Footer) ---
$mpdf->SetHTMLHeader('');
$mpdf->SetHTMLFooter('');
$mpdf->AddPageByArray([
  'margin_left'   => 0, 'margin_right' => 0,
  'margin_top'    => 0, 'margin_bottom'=> 0,
  'margin_header' => 0, 'margin_footer'=> 0,
]);
$mpdf->WriteHTML($coverCss, HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($coverHtml, HTMLParserMode::HTML_BODY);

// --- Seite 2: Rückseite leer (ohne Header/Footer) ---
$mpdf->AddPageByArray([
  'margin_left'    => 28,   // <— hier deine Wunschwerte
  'margin_right'   => 18,
  'margin_top'     => 20,   // Platz über dem Text (unterhalb des Headers)
  'margin_bottom'  => 20,
  'margin_header'  => 20,   // Höhe des Header-Bereichs
  'margin_footer'  => 26,   // Höhe des Footer-Bereichs
  'resetpagenum' => 1,    // nicht nötig, da StartPageGroup() verwendet wird
]);
// nichts schreiben -> bleibt leer

// ===== Ab hier: Inhalts-Teil =====
// 1) Page-Group starten -> {PAGENO} beginnt bei 1, {nbpg} = Seitenanzahl in dieser Gruppe
//$mpdf->StartPageGroup();

// 2) CSS für den Inhalt injizieren
$mpdf->WriteHTML($contentCss, HTMLParserMode::HEADER_CSS);

// 3) Header/Fußzeile (ungerade rechts, gerade links) – mit Balken & Nummer „{PAGENO} von {nbpg}“
$headerOdd = '
  <div style="text-align:right;">
    <div style="float:right;display:inline-block;width:35mm;height:0;line-height:0;border-top:0.3mm solid #47A0B1;vertical-align:middle;"></div>
    <span style="display:inline-block;margin-left:3mm;font-size:9pt;color:#444;vertical-align:middle;">{PAGENO} von {nbpg}</span>
  </div>
';
$headerEven = '
  <div style="text-align:left;">
    <div style="float:left;display:inline-block;width:35mm;height:0;line-height:0;border-top:0.3mm solid #47A0B1;vertical-align:middle;"></div>
    <span style="display:inline-block;margin-left:3mm;font-size:9pt;color:#444;vertical-align:middle;">{PAGENO} von {nbpg}</span>
  </div>
';
$footerHtml =
  '<table width="100%" style="font-size:8pt;border:0;border-collapse:collapse;">
     <tr>
       <td width="22mm" align="left" style="vertical-align:top;">
         <barcode code="' . $uuid . '" type="QR" size="0.3" error="M" disableborder="1" />
       </td>
       <td align="left" style="vertical-align:middle;">
         <div>' . $uuid . '</div>
       </td>
       <td width="30mm" align="right">&nbsp;</td>
     </tr>
   </table>';


$mpdf->SetHTMLHeader($headerOdd, 'O');   // ungerade Seiten
$mpdf->SetHTMLHeader($headerEven, 'E');  // gerade Seiten
$mpdf->SetHTMLFooter($footerHtml, 'O');$mpdf->SetHTMLFooter($footerHtml, 'E');

// 4) (optional) Wasserzeichen ab jetzt
$mpdf->watermarkImgBehind  = true;
$mpdf->watermarkImageAlpha = 0.3;
$mpdf->SetWatermarkImage(JPATH_SITE . '/images/pdf_site_bg.jpg');
$mpdf->showWatermarkImage  = true;
/*
// 5) Erste Inhaltsseite mit DEINEN RÄNDERN eröffnen
$mpdf->AddPageByArray([
  'margin_left'    => 28,   // <— hier deine Wunschwerte
  'margin_right'   => 18,
  'margin_top'     => 20,   // Platz über dem Text (unterhalb des Headers)
  'margin_bottom'  => 20,
  'margin_header'  => 20,   // Höhe des Header-Bereichs
  'margin_footer'  => 26,   // Höhe des Footer-Bereichs
  'resetpagenum' => 1,    // nicht nötig, da StartPageGroup() verwendet wird
]);
*/$mpdf->AddPageByArray(array(
    'mgl' => '22',
    'mgr' => '12',
    'mgt' => '20',
    'mgb' => '20',
    'mgh' => '10',
    'mgf' => '10',    'resetpagenum' => 1, 
));
// 6) Inhalt schreiben
$mpdf->WriteHTML($contentHtml, HTMLParserMode::HTML_BODY);


// Output
$file = preg_replace('~\s+~', '-', trim($customer) ?: 'Projekt') . '_' . date('Y-m-d') . '_pricing.pdf';
$mpdf->Output($file, 'I');
exit;

// ===== Helper =====
function uuid(): string {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}function rgba_to_hex_over_white(int $r, int $g, int $b, float $a): string {
    $af = max(0, min(1, $a));
    $R = (int) round($r * $af + 255 * (1 - $af));
    $G = (int) round($g * $af + 255 * (1 - $af));
    $B = (int) round($b * $af + 255 * (1 - $af));
    return sprintf('#%02X%02X%02X', $R, $G, $B);
}
