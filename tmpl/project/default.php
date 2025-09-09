<?phpdefined('_JEXEC') or die;

// Daten für JS vorbereiten
$itemsJson = json_encode($this->items);
?>
<div id="fisc-calculator" data-items='<?php echo $itemsJson; ?>'>
  <!-- JS rendert hier Balken, Eingabefelder etc. -->
</div>
<script type="module">
  const root = document.getElementById('fisc-calculator');
  const items = JSON.parse(root.dataset.items);
  const container = document.createElement('div');
  // Beispielhafte JS‑UI: Tabelle mit min, max, mean, EK, freier Preis & Marge
  container.innerHTML = items.map((item, idx) => {
    const mean = item.max_price ? (item.min_price + item.max_price)/2 : item.min_price;
    const freePrice = item.max_price;
    const margin = item.cogs_lc ? (freePrice - item.cogs_lc).toFixed(2) : '-';
    return `
      <div>
        <strong>${item.title}</strong> (Ref: ${item.ref})<br>
        Min: ${item.min_price ?? '–'}, Max: ${item.max_price ?? '–'}, EK: ${item.cogs_lc ?? '–'}<br>
        <input type="number" class="free-price" data-idx="${idx}" value="${freePrice}" />
        Margin: <span class="margin" data-idx="${idx}">${margin}</span>
      </div>`;
  }).join('');
  root.appendChild(container);

  root.addEventListener('input', (e) => {
    if (!e.target.matches('.free-price')) return;
    const idx = e.target.dataset.idx;
    const val = parseFloat(e.target.value) || 0;
    const item = items[idx];
    const marginEl = root.querySelector(`.margin[data-idx="${idx}"]`);
    marginEl.textContent = item.cogs_lc ? (val - item.cogs_lc).toFixed(2) : '-';
    // Optional: Gesamt-Min, Max, Mean berechnen und darstellen
  });
</script>
