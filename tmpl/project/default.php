<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.keepalive');

$app        = Factory::getApplication();
$projectId  = $this->projectId ?? (int)$app->input->getInt('id');
$cases      = $this->cases ?? [];
$caseItems  = $this->caseItems ?? [];
$products   = $this->products ?? [];
$project    = $this->project ?? null; // vom View setzen (s.u.)
$projName   = $project->name ?? ('Projekt #' . (int)$projectId);
$customer   = $project->customer_name ?? '';
$projDesc   = $project->description ?? '';

// 👉 Passe die Ziel-URL der Projektliste ggf. an (Menu-Item etc.)
$projectListUrl = Route::_('index.php?option=com_fisc_calculator&view=projects');

$tokenField = HTMLHelper::_('form.token');
$tokenName  = Session::getFormToken();
?>

<div id="cases-grid"
     data-project-id="<?= (int)$projectId ?>"
     data-endpoint-savefree="<?= Route::_('index.php?option=com_fisc_calculator&task=project.saveFreePrice&format=json'); ?>"
     data-endpoint-deletecase="<?= Route::_('index.php?option=com_fisc_calculator&task=project.deleteCase&format=json'); ?>"
     data-endpoint-addcase="<?= Route::_('index.php?option=com_fisc_calculator&task=project.addCase&format=json'); ?>"
     data-endpoint-additem="<?= Route::_('index.php?option=com_fisc_calculator&task=project.addItem&format=json'); ?>"
     data-endpoint-delitem="<?= Route::_('index.php?option=com_fisc_calculator&task=project.deleteItem&format=json'); ?>"
     data-projectlist-url="<?= htmlspecialchars($projectListUrl, ENT_QUOTES, 'UTF-8'); ?>"
     data-token-name="<?= htmlspecialchars($tokenName, ENT_QUOTES, 'UTF-8'); ?>">

  <!-- Header -->
  <div class="row">
    <div class="col-md-8"><h3>Preisberechnung Fallbeispiele</h3></div>
    <div class="col-md-4 item_menu text-md-end">
      <button id="btnSaveAndBack" type="button" class="btn btn-success">
        Speichern
      </button>      <a href="<? echo $projectListUrl; ?>" class="btn btn-primary">zurück zur Projektliste</a>
    </div>
  </div>

  <!-- Steuerbereich -->
  <div class="row g-3">
    <div class="col-md-4 border rounded bg-light p-3">
      <h2 class="mb-2">Projekt #<?= (int)$projectId ?></h2>      <h3><?= $customer ? htmlspecialchars($customer, ENT_QUOTES, 'UTF-8') : '—' ?></h3>      <p><?= $projDesc ? htmlspecialchars($projDesc, ENT_QUOTES, 'UTF-8') : '—' ?></p>
      
      <div id="saveStatus" class="form-text mt-1" style="display:none;"></div>
    </div>

    <div class="col-md-8">
      <!-- Ziel-Fall wählen ODER neuen Fall anlegen -->
      <div class="card mb-3">
        <div class="card-body">
          <div class="row g-3 align-items-end">
                        <div class="col-md-5">
              <label class="form-label">Fallbeispielbezeichnung</label>              <div class="controls">
              	<input type="text" class="form-control inputbox" id="inpCaseName" placeholder="z. B. Hüft-OP Musterfall">              </div>
              <button id="btnCreateCase" type="button" class="btn btn-success mt-2">Fallbeispiel erstellen</button>
            </div>
            <div class="col-md-2 d-flex justify-content-center"><strong>oder</strong></div>
            <div class="col-md-5">
             	<label class="form-label">Produkt (REF — Title)</label>
              <select id="selProduct" class="form-select">
                <option value="">— Bitte wählen —</option>
                <?php foreach ($products as $p):
                  $lbl = trim(($p->ref ?? '') . ' — ' . ($p->title ?? ''));
                  $lbl = $lbl === '—' ? ($p->title ?? $p->ref ?? '') : $lbl;
                ?>
                  <option value="<?= (int)$p->id ?>"
                          data-ref="<?= htmlspecialchars($p->ref ?? '', ENT_QUOTES, 'UTF-8') ?>"
                          data-title="<?= htmlspecialchars($p->title ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>             <button id="btnAddProduct" type="button" class="btn btn-outline-primary">Zum Fall hinzufügen</button>

            </div>
          </div>
        </div>
      </div>
<div class="hidden"> <label class="form-label">Ziel-Fall (für Produkt hinzufügen)</label>
              <select id="selTargetCase" class="form-select">
                <option value="">— Bitte Fall wählen —</option>
                <?php foreach ($cases as $c): ?>
                  <option value="<?= (int)$c->id ?>">#<?= (int)$c->id ?> — <?= htmlspecialchars($c->case_name, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select></div>


      <!-- STAGING: Karten-Entwürfe (oben, editierbar) -->
      <div class="card">
        <div class="card-header"><strong>Neue Fallliste (Entwurf)</strong></div>
        <div class="card-body">
          <div id="staging-cards" class="row g-3">
            <div class="col-12 staging-empty">
              <div class="text-center text-muted py-3">Noch keine neuen Fallbeispiele</div>
            </div>
          </div>
          <div class="text-end mt-3">
            <button id="btnStageCommit" type="button" class="btn btn-primary" disabled>Speichern & unten anzeigen</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bestehende Fälle (unten, 2 Spalten, read-only) -->
  <div class="row g-4 mt-4" id="cards-row">
    <?php if ($cases): foreach ($cases as $c):
      $cid   = (int)$c->id;
      $items = $caseItems[$cid] ?? [];
    ?>
      <div class="col-md-6">
        <div class="card h-100" data-case-id="<?= $cid ?>">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="h5 mb-0"><?= htmlspecialchars($c->case_name, ENT_QUOTES, 'UTF-8') ?></h3>
            <small class="text-muted">#<?= $cid ?></small>
          </div>
          <div class="card-body">
            <table class="table table-striped align-middle items-table">
              <thead>
                <tr>
                  <th style="width:10%">REF</th>
                  <th style="width:40%">Title</th>
                  <th style="width:10%" class="text-end">Preis</th>
                  <th style="width:20%">Preisinfo</th>
                  <th style="width:10%" class="text-end">COGS</th>
                  <th style="width:10%" class="text-end">Marge</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($items): foreach ($items as $it):
                  $min  = max(0, (float)($it->min_price  ?? 0));
                  $max  = (float)($it->max_price  ?? 0);
                  $med  = (float)($it->mean_price ?? (($min && $max) ? ($min + $max)/2 : $min));
                  $cogs = (float)($it->cogs_lc ?? 0);
                  $free = (float)($it->free_price1 ?? 0);
                  $pos  = ($max > $min) ? round(100 * ($med - $min) / ($max - $min), 1) : 0;
                  $rowId = isset($it->case_item_id) ? (int)$it->case_item_id : (int)$it->id;
                ?>
                  <tr data-item-id="<?= $rowId ?>">
                    <td><?= htmlspecialchars($it->ref ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($it->title ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">                    <div class="controls">
                      <input type="number" lang="de" inputmode="decimal" step="0.01"
                             class="form-control inputbox form-control-sm free-price"
                             value="<?= htmlspecialchars((string)$free, ENT_QUOTES, 'UTF-8') ?>">                     </div>
                    </td>
                    <td class="text-end">
                      <span style="float:left;width:49%;text-align:left;"  data-type="min"><?= number_format($min,2,',','.') ?></span>
                      <span style="float:right;width:49%;text-align:right;" data-type="max"><?= number_format($max,2,',','.') ?></span>
                      <div class="clearfix"></div>
                      <div class="position-relative mt-1" style="height:10px;background:#eee;border-radius:4px;">
                        <div class="position-absolute mean-marker" style="left:<?= $pos ?>%;top:-6px;width:4px;height:22px;background:#EE8157;"></div>
                      </div>
                      <small class="text-muted mean-value d-block text-center"><?= number_format($med,2,',','.') ?></small>
                    </td>
                    <td class="text-end" data-type="cogs"><?= number_format($cogs,2,',','.') ?></td>
                    <td class="text-end margin"><?= $cogs ? number_format($free - $cogs,2,',','.') : '-' ?></td>
                  </tr>
                <?php endforeach; else: ?>
                  <tr class="placeholder-row">
                    <td colspan="6" class="text-center text-muted py-3">Keine Einträge gefunden.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
              <tfoot>
                <tr class="fw-bold">
                  <td colspan="2">Summe</td>
                  <td class="text-end sum-free">0,00</td>
                  <td class="text-end">
                    <span class="sum-min" style="float:left;width:49%;text-align:left;">0,00</span>
                    <span class="sum-max" style="float:right;width:49%;text-align:right;">0,00</span>
                    <div class="clearfix"></div>
                  </td>
                  <td class="text-end sum-cogs">0,00</td>
                  <td class="text-end sum-margin">0,00</td>
                </tr>
              </tfoot>
            </table>
          </div>
          <div class="card-footer d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary btn-sm btn-case-edit">Bearbeiten</button>
            <button type="button" class="btn btn-outline-danger btn-sm btn-case-delete">Löschen</button>
          </div>
        </div>
      </div>
    <?php endforeach; else: ?>
      <div class="col-12"><div class="alert alert-info">Für dieses Projekt sind noch keine Fallbeispiele gespeichert.</div></div>
    <?php endif; ?>
  </div>

  <?= $tokenField ?>
</div>

<script>
(function(){
  const $  = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));

  const grid       = document.getElementById('cases-grid'); if (!grid) return;
  const epSave     = grid.dataset.endpointSavefree;
  const epDelete   = grid.dataset.endpointDeletecase;
  const epAddCase  = grid.dataset.endpointAddcase;
  const epAddItem  = grid.dataset.endpointAdditem;
  const epDelItem  = grid.dataset.endpointDelitem;
  const listURL    = grid.dataset.projectlistUrl || '';
  const tokenKey   = grid.dataset.tokenName;

  const selTargetCase  = document.getElementById('selTargetCase');
  const selProduct     = document.getElementById('selProduct');
  const inpCaseName    = document.getElementById('inpCaseName');
  const btnSaveAndBack = document.getElementById('btnSaveAndBack');
  const saveStatus     = document.getElementById('saveStatus');

  const stagingWrap    = document.getElementById('staging-cards');
  const btnStageCommit = document.getElementById('btnStageCommit');
  const cardsRow       = document.getElementById('cards-row');

  // ----- Locale Parser / Formatter -----
  function parseNumLocale(val){
    if (val && val.nodeType === 1 && val.tagName === 'INPUT') {
      const n = val.valueAsNumber;
      if (!Number.isNaN(n)) return n;
      val = val.value;
    }
    if (typeof val !== 'string') val = String(val ?? '');
    val = val.trim().replace(/\s|\u00A0/g, '');
    const hasComma = val.includes(',');
    const hasDot   = val.includes('.');
    if (hasComma && hasDot) {
      if (val.lastIndexOf(',') > val.lastIndexOf('.')) {
        return Number(val.replace(/\./g, '').replace(',', '.')) || 0;
      } else {
        return Number(val.replace(/,/g, '')) || 0;
      }
    }
    if (hasComma) return Number(val.replace(',', '.')) || 0;
    return Number(val) || 0;
  }
  function fmtDE(n){
    return (Number(n)||0).toLocaleString('de-DE',{minimumFractionDigits:2,maximumFractionDigits:2});
  }

  // ----- Summen je Card -----
  function recalcCard(card){
    const rows = card.querySelectorAll('.items-table tbody tr');
    let sMin=0,sMax=0,sCogs=0,sFree=0,sMargin=0;
    rows.forEach(tr=>{
      if(tr.classList.contains('placeholder-row')) return;
      const cMin=tr.querySelector('[data-type="min"]');
      const cMax=tr.querySelector('[data-type="max"]');
      const cC =tr.querySelector('[data-type="cogs"]');
      const inp=tr.querySelector('.free-price');
      const cM =tr.querySelector('.margin');
      if(!cMin||!cMax||!cC||!inp||!cM) return;
      const min = parseNumLocale(cMin.textContent);
      const max = parseNumLocale(cMax.textContent);
      const cogs= parseNumLocale(cC.textContent);
      const free= parseNumLocale(inp);
      sMin+=min; sMax+=max; sCogs+=cogs; sFree+=free; sMargin += cogs ? (free - cogs) : 0;
      cM.textContent = cogs ? fmtDE(free - cogs) : '-';
    });
    const set = (cls,val) => { const el = card.querySelector('.'+cls); if (el) el.textContent = fmtDE(val); };
    set('sum-min', sMin); set('sum-max', sMax); set('sum-cogs', sCogs); set('sum-free', sFree); set('sum-margin', sMargin);
  }

  // Init
  $$('#cards-row .card[data-case-id]').forEach(recalcCard);
  const toggleCommitBtn   = () => { btnStageCommit.disabled = !stagingWrap.querySelector('.card[data-case-id]'); };
  const removeStagingEmpty= () => stagingWrap.querySelector('.staging-empty')?.remove();

  // ----- Autosave + Live-Recalc -----
  const timers = new WeakMap();
  function queueSave(inputEl){
    if (!epSave) return;
    const tr = inputEl.closest('tr'); const id = tr?.dataset?.itemId; if (!id) return;
    const doSave = ()=>{
      const canonical = parseNumLocale(inputEl);
      const form = new FormData();
      form.append('item_id', id);
      form.append('field', 'free_price1');
      form.append('value', String(canonical));
      if (tokenKey) form.append(tokenKey, '1');
      return fetch(epSave, { method:'POST', body: form })
        .then(r=>r.json())
        .then(res=>{ if(!res?.success) console.warn('Autosave fehlgeschlagen', res); });
    };
    if (timers.has(inputEl)) clearTimeout(timers.get(inputEl));
    timers.set(inputEl, setTimeout(doSave, 300));
  }
  grid.addEventListener('input', (e)=>{
    if (!e.target.classList.contains('free-price')) return;
    const card = e.target.closest('.card'); if (!card) return;
    recalcCard(card); queueSave(e.target);
  });
  grid.addEventListener('change', (e)=>{
    if (!e.target.classList.contains('free-price')) return;
    const card = e.target.closest('.card'); if (!card) return;
    recalcCard(card); queueSave(e.target);
  });

  // ----- Card-HTML -----
  function cardHTML(cid, name){
    const title = name.replace(/</g,'&lt;').replace(/>/g,'&gt;');
    return `
      <div class="card h-100" data-case-id="${cid}">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="h5 mb-0">${title}</h3>
          <small class="text-muted">#${cid}</small>
        </div>
        <div class="card-body">
          <table class="table table-striped align-middle items-table">
            <thead>
              <tr>
                <th style="width:10%">REF</th>
                <th style="width:40%">Title</th>
                <th style="width:10%" class="text-end">Preis</th>
                <th style="width:20%">Preisinfo</th>
                <th style="width:10%" class="text-end">COGS</th>
                <th style="width:10%" class="text-end">Marge</th>
              </tr>
            </thead>
            <tbody>
              <tr class="placeholder-row">
                <td colspan="6" class="text-center text-muted py-3">Keine Einträge gefunden.</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="fw-bold">
                <td colspan="2">Summe</td>
                <td class="text-end sum-free">0,00</td>
                <td class="text-end">
                  <span class="sum-min" style="float:left;width:49%;text-align:left;">0,00</span>
                  <span class="sum-max" style="float:right;width:49%;text-align:right;">0,00</span>
                  <div class="clearfix"></div>
                </td>
                <td class="text-end sum-cogs">0,00</td>
                <td class="text-end sum-margin">0,00</td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between">
          <button type="button" class="btn btn-outline-secondary btn-sm btn-case-edit">Bearbeiten</button>
          <button type="button" class="btn btn-outline-danger btn-sm btn-case-delete">Löschen</button>
        </div>
      </div>`;
  }

  // Aktionen-Spalte (nur im Editmodus oben)
  function addActionsColumn(card){
    if (card.classList.contains('is-editing')) return;
    const table = card.querySelector('.items-table'); if (!table) return;

    const thRow = table.tHead?.rows?.[0];
    if (thRow && !thRow.querySelector('.th-actions')) {
      const th = document.createElement('th');
      th.className = 'text-end th-actions';
      th.textContent = 'Aktionen';
      thRow.appendChild(th);
    }
    table.querySelectorAll('tbody tr').forEach(tr=>{
      if (tr.classList.contains('placeholder-row')) return;
      if (tr.querySelector('.td-actions')) return;
      const td = document.createElement('td');
      td.className = 'text-end td-actions';
      td.innerHTML = `<button type="button" class="btn btn-sm btn-outline-danger btn-item-delete">Entfernen</button>`;
      tr.appendChild(td);
    });
    const tfRow = table.tFoot?.rows?.[0];
    if (tfRow && !tfRow.querySelector('.tf-actions')) {
      const td = document.createElement('td');
      td.className = 'text-end tf-actions';
      tfRow.appendChild(td);
    }
    card.classList.add('is-editing');
  }
  function removeActionsColumn(card){
    const table = card.querySelector('.items-table'); if (!table) return;
    table.querySelector('.th-actions')?.remove();
    table.querySelectorAll('.td-actions').forEach(td=>td.remove());
    table.querySelector('.tf-actions')?.remove();
    card.classList.remove('is-editing');
  }

  // Staging/New Cards
  function addStagedCard(cid, name){
    const col = document.createElement('div');
    col.className = 'col-12';
    col.innerHTML = cardHTML(cid, name);
    stagingWrap.querySelector('.staging-empty')?.remove();
    stagingWrap.prepend(col);
    const card = col.querySelector('.card');
    addActionsColumn(card);
    recalcCard(card);
    toggleCommitBtn();
  }
  function addBottomCard(cid, name){
    const col = document.createElement('div');
    col.className = 'col-md-6';
    col.innerHTML = cardHTML(cid, name);
    cardsRow.prepend(col);
    recalcCard(col.querySelector('.card'));
  }
  function moveCardToStaging(card){
    const cid = card.dataset.caseId;
    if (cid && selTargetCase) selTargetCase.value = String(cid);
    const currentCol = card.closest('.col-md-6, .col-12');
    const shell = document.createElement('div');
    shell.className = 'col-12';
    shell.appendChild(card);
    stagingWrap.querySelector('.staging-empty')?.remove();
    stagingWrap.prepend(shell);
    currentCol?.remove();
    addActionsColumn(card);
    recalcCard(card);
    toggleCommitBtn();
  }

  // Fall erstellen -> Card in STAGING
  document.getElementById('btnCreateCase')?.addEventListener('click', ()=>{
    const name = (inpCaseName?.value||'').trim();
    if (!name) return alert('Bitte einen Namen für das Fallbeispiel eingeben.');
    if (!epAddCase) return alert('Endpoint addCase fehlt.');
    const form = new FormData();
    form.append('project_id', grid.dataset.projectId || '0');
    form.append('case_name', name);
    if (tokenKey) form.append(tokenKey, '1');
    fetch(epAddCase, { method:'POST', body: form })
      .then(r=>r.json())
      .then(res=>{
        if (!res?.success || !res?.case_id) { console.warn(res); return alert('Fallbeispiel konnte nicht erstellt werden.'); }
        const cid = parseInt(res.case_id, 10);
        const opt = document.createElement('option');
        opt.value = String(cid);
        opt.textContent = '#' + cid + ' — ' + name;
        selTargetCase?.insertBefore(opt, selTargetCase.firstChild);
        selTargetCase.value = String(cid);
        addStagedCard(cid, name);
        inpCaseName.value = '';
      })
      .catch(console.error);
  });

  // Produkt zu Fall
  document.getElementById('btnAddProduct')?.addEventListener('click', ()=>{
    const opt = selProduct?.selectedOptions?.[0];
    const caseId = parseInt(selTargetCase?.value || '0', 10);
    if (!opt || !opt.value) return alert('Bitte ein Produkt wählen.');
    if (!caseId) return alert('Bitte ein Ziel-Fallbeispiel wählen.');
    if (!epAddItem) return alert('Endpoint addItem fehlt.');
    const productId = parseInt(opt.value, 10);
    const ref   = opt.getAttribute('data-ref') || '';
    const title = opt.getAttribute('data-title') || '';
    let card = grid.querySelector('#staging-cards .card[data-case-id="'+caseId+'"]');
    if (!card) card = grid.querySelector('#cards-row .card[data-case-id="'+caseId+'"]');
    if (!card) return alert('Ziel-Fall nicht gefunden.');

    const form = new FormData();
    form.append('case_id', String(caseId));
    form.append('product_id', String(productId));
    if (tokenKey) form.append(tokenKey, '1');

    fetch(epAddItem, { method:'POST', body: form })
      .then(r=>r.json())
      .then(res=>{
        if (!(res?.success && res?.item_id)) { console.warn(res); return alert('Produkt konnte nicht hinzugefügt werden.'); }
        const tbody = card.querySelector('tbody');
        card.querySelector('.placeholder-row')?.remove();

        const tr = document.createElement('tr');
        tr.dataset.itemId = String(res.item_id);
        tr.innerHTML = `
          <td>${ref.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</td>
          <td>${title.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</td>
          <td class="text-end">
            <input type="number" lang="de" inputmode="decimal" step="0.01"
                   class="form-control form-control-sm free-price" value="0">
          </td>
          <td class="text-end">
            <span style="float:left;width:49%;text-align:left;"  data-type="min">0,00</span>
            <span style="float:right;width:49%;text-align:right;" data-type="max">0,00</span>
            <div class="clearfix"></div>
            <div class="position-relative mt-1" style="height:10px;background:#eee;border-radius:4px;">
              <div class="position-absolute mean-marker" style="left:0%;top:-6px;width:4px;height:22px;background:#EE8157;"></div>
            </div>
            <small class="text-muted mean-value d-block text-center">0,00</small>
          </td>
          <td class="text-end" data-type="cogs">0,00</td>
          <td class="text-end margin">-</td>
        `;
        tbody.appendChild(tr);

        if (card.classList.contains('is-editing') && !tr.querySelector('.td-actions')) {
          const td = document.createElement('td');
          td.className = 'text-end td-actions';
          td.innerHTML = `<button type="button" class="btn btn-sm btn-outline-danger btn-item-delete">Entfernen</button>`;
          tr.appendChild(td);
        }

        if (res.stats) {
          const min = Math.max(0, Number(res.stats.min_price||0));
          const max = Number(res.stats.max_price||0);
          const med = Number(res.stats.mean_price||0);
          const cogs= Number(res.stats.cogs_lc||0);
          tr.querySelector('[data-type="min"]').textContent = fmtDE(min);
          tr.querySelector('[data-type="max"]').textContent = fmtDE(max);
          tr.querySelector('[data-type="cogs"]').textContent = fmtDE(cogs);
          tr.querySelector('.mean-value').textContent = fmtDE(med);
          const pos = (max>min) ? Math.round(100*(med-min)/(max-min)) : 0;
          tr.querySelector('.mean-marker').style.left = Math.max(0, Math.min(100, pos)) + '%';
          const inp = tr.querySelector('.free-price');
          const mar = tr.querySelector('.margin');
          const free= parseNumLocale(inp);
          mar.textContent = cogs ? fmtDE(free - cogs) : '-';
        }
        recalcCard(card);
      })
      .catch(console.error);
  });

  // Commit: Staging -> unten
  btnStageCommit.addEventListener('click', ()=>{
    const stagedCards = Array.from(stagingWrap.querySelectorAll('.card[data-case-id]'));
    if (!stagedCards.length) return;
    stagedCards.forEach(card=>{
      removeActionsColumn(card);
      const newCol = document.createElement('div');
      newCol.className = 'col-md-6';
      newCol.appendChild(card);
      cardsRow.prepend(newCol);
    });
    stagingWrap.innerHTML = '<div class="col-12 staging-empty"><div class="text-center text-muted py-3">Noch keine neuen Fallbeispiele</div></div>';
    btnStageCommit.setAttribute('disabled','disabled');
    $$('#cards-row .card[data-case-id]').forEach(recalcCard);
  });

  // Case löschen
  grid.addEventListener('click', (e)=>{
    const btn = e.target.closest('.btn-case-delete');
    if (!btn) return;
    const card = btn.closest('.card');
    const col  = card.closest('.col-12, .col-md-6');
    const caseId = parseInt(card?.dataset?.caseId || '0', 10);
    if (!caseId) return;
    if (!confirm('Dieses Fallbeispiel wirklich löschen?')) return;
    if (!epDelete) { alert('Endpoint deleteCase fehlt.'); return; }
    const form = new FormData();
    form.append('case_id', String(caseId));
    if (tokenKey) form.append(tokenKey, '1');
    fetch(epDelete, { method:'POST', body: form })
      .then(r=>r.json())
      .then(res=>{
        if (!res?.success) { console.warn(res); return alert('Löschen fehlgeschlagen.'); }
        col.remove();
        const opt = selTargetCase?.querySelector('option[value="'+caseId+'"]');
        if (opt) opt.remove();
        if (!stagingWrap.querySelector('.card[data-case-id]') && !stagingWrap.querySelector('.staging-empty')) {
          const empty = document.createElement('div');
          empty.className = 'col-12 staging-empty';
          empty.innerHTML = '<div class="text-center text-muted py-3">Noch keine neuen Fallbeispiele</div>';
          stagingWrap.appendChild(empty);
        }
        toggleCommitBtn();
      })
      .catch(console.error);
  });

  // Bearbeiten -> nach oben holen
  grid.addEventListener('click', (e)=>{
    const btn = e.target.closest('.btn-case-edit');
    if (!btn) return;
    const card = btn.closest('.card[data-case-id]'); if (!card) return;
    if (!card.closest('#staging-cards')) {
      moveCardToStaging(card);
    } else {
      addActionsColumn(card);
    }
    btnStageCommit.removeAttribute('disabled');
  });

  // Einzelnes Produkt löschen
  grid.addEventListener('click', (e)=>{
    const btn = e.target.closest('.btn-item-delete');
    if (!btn) return;
    const tr   = btn.closest('tr[data-item-id]');
    const card = btn.closest('.card[data-case-id]');
    const iid  = tr?.dataset?.itemId;
    if (!iid) return;
    if (!confirm('Dieses Produkt aus dem Fall entfernen?')) return;
    if (!epDelItem) { alert('Endpoint deleteItem fehlt.'); return; }
    const form = new FormData();
    form.append('item_id', String(iid));
    if (tokenKey) form.append(tokenKey, '1');
    fetch(epDelItem, { method:'POST', body: form })
      .then(r=>r.json())
      .then(res=>{
        if (!res?.success) { console.warn(res); return alert('Produkt konnte nicht entfernt werden.'); }
        tr.remove();
        const tbody = card.querySelector('tbody');
        if (!tbody.querySelector('tr[data-item-id]')) {
          const ph = document.createElement('tr');
          ph.className = 'placeholder-row';
          const colspan = card.classList.contains('is-editing') ? 7 : 6;
          ph.innerHTML = '<td colspan="'+colspan+'" class="text-center text-muted py-3">Keine Einträge gefunden.</td>';
          tbody.appendChild(ph);
        }
        recalcCard(card);
      })
      .catch(console.error);
  });

  // --------- SPEICHERN & ZURÜCK ----------
  function saveAllFreePricesNow(){
    if (!epSave) return Promise.resolve();
    // alle Debounce-Timer stoppen, damit wir "now" senden
    $$('.free-price').forEach(inp=>{
      if (timers.has(inp)) { clearTimeout(timers.get(inp)); timers.delete(inp); }
    });

    const promises = [];
    $$('#cards-row tr[data-item-id], #staging-cards tr[data-item-id]').forEach(tr=>{
      const id  = tr.dataset.itemId;
      const inp = tr.querySelector('.free-price');
      if (!id || !inp) return;
      const canonical = parseNumLocale(inp);
      const form = new FormData();
      form.append('item_id', id);
      form.append('field', 'free_price1');
      form.append('value', String(canonical));
      if (tokenKey) form.append(tokenKey, '1');
      promises.push(
        fetch(epSave, { method:'POST', body: form }).then(r=>r.json()).catch(()=>({success:false}))
      );
    });
    return Promise.all(promises);
  }

  btnSaveAndBack?.addEventListener('click', async ()=>{
    if (!confirm('Änderungen speichern und zur Projektliste zurückkehren?')) return;

    // UI sperren
    btnSaveAndBack.disabled = true;
    btnSaveAndBack.textContent = 'Speichere …';
    if (saveStatus) { saveStatus.style.display='block'; saveStatus.textContent = 'Bitte warten: Preise werden gespeichert …'; }

    try {
      await saveAllFreePricesNow();
    } catch(e) {
      console.warn(e);
    }

    // Redirect
    const url = listURL || 'index.php?option=com_fisc_calculator&view=projects';
    window.location.href = url;
  });

  // Init
  toggleCommitBtn();
})();
</script>
