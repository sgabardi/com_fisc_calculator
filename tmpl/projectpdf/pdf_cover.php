<?php
defined('_JEXEC') or die;

function fiscBuildCover(array $d): array
{
    $projName  = (string)($d['projName']  ?? '');
    $customer  = (string)($d['customer']  ?? '');
    $dateHuman = (string)($d['dateHuman'] ?? '');
    $uuid      = (string)($d['uuid']      ?? '');
    $projDesc  = (string)($d['projDesc']  ?? '');
    $descShort = $projDesc ? mb_strimwidth($projDesc, 0, 600, ' …', 'UTF-8') : '';

    $css = <<<CSS
/* --- Cover styles (mPDF-safe) --- */
* { box-sizing: border-box; }
.cover-page {
  width: 210mm; height: 297mm; position: relative; overflow: hidden;
  font-family: dejavusans, sans-serif; color:#1f2937;
  background: #e6ffe1;
  background-image: linear-gradient(158deg, rgba(230,255,225,1) 0%, rgba(214,224,247,1) 100%);
}

/* Kopfzeile */
.system_header { width: 190mm; padding: 0 25mm; }
.system_logo   { padding-top: 5mm; }
.fisc_cover_logo { height: 12mm; }

/* Trenner */
.divider { width: 100%; border-top: 1px solid #000; margin: 6mm 0; }

/* Inhalt */
.info-container { position: absolute; left:0; right:0; top: 78mm; bottom: 0; padding: 0; }
.info-grid { width: 100%; border-collapse: collapse; }
.info-grid td { width: 33%; vertical-align: top; padding-right: 6mm; }
.info-label { font-size: 9pt; color:#6b7280; text-transform: uppercase; letter-spacing: .4px; }
.info-value { font-size: 12pt; margin-top: 1mm; }

.qr { margin-top: 3mm; } /* Abstand QR-Code */
.desc { position: absolute; left:20mm; right:20mm; bottom: 36mm; font-size: 11pt; color:#374151; white-space: pre-wrap; }
.report_title {position: absolute; width: 70%;font-size:50px;font-weight:bold;color:#47A0B1}.report_project {position: absolute; width: 100%;text-align:right;font-size:20px;font-weight:bold; margin-top:-1cm }.cover_bg {margin-top:2cm; margin-bottom: 2cm; }
/* Footer */
.brand  { position: absolute; bottom: 18mm; right: 20mm; text-align: right;}
.brand .by { font-size: 8pt; color:#6b7280; }
.badges { position: absolute; bottom: 18mm; left: 20mm; font-size: 9pt; color:#6b7280; }
.badge  { display: inline-block; border:1px solid #e5e7eb; padding: 2mm 4mm; border-radius: 3mm; margin-right: 3mm; }

/* Akzentfarbe */
.accent { color:#8FBF21; }
CSS;

    $uuidEsc = htmlspecialchars($uuid, ENT_QUOTES, 'UTF-8');
    $customerEsc = htmlspecialchars($customer, ENT_QUOTES, 'UTF-8');
    $projNameEsc = htmlspecialchars($projName, ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
<div class="cover-page">

  <div class="system_header">
    <div class="system_logo">
      <img src="templates/admc/assets/images/fisc_logo.png" class="fisc_cover_logo" alt="FiSC" />
    </div>
  </div>  <div class="cover_bg"><img class="logo-small" src="images/pdf_cover_bg.png"/></div>
	<div class="report_title">		Preisberechnung<br />		Fallbeispiele<br />			</div>	<div class="report_project">{$projNameEsc}</div>
  <div class="divider"></div>

  <div class="info-container">
    <table class="info-grid">
      <tr>
        <td style="width:60%">
          <div class="info-label">Kunde</div>
          <div class="info-value">{$customerEsc}</div>
        </td>
        <td style="width:20%">
          <div class="info-label">Datum</div>
          <div class="info-value">{$dateHuman}</div>
        </td>
        <td style="width:20%">
          <div class="info-label">Dok-ID</div>
          <center>
          <div class="qr">
            <!-- QR-Code (mPDF) -->
            <barcode code="{$uuidEsc}" type="QR" size="0.5" error="M" disableborder="1" />
          </div>          <div class="info-value"><small style="font-size:7px">{$uuidEsc}</small></div>          </center>
        </td>
      </tr>
    </table>
  </div>
</div>
HTML;

    return [$css, $html];
}
