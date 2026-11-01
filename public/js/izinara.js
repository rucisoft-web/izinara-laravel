/* Izinara – interaksi ringan tanpa build step */
(function () {
  const IZ = window.IZ || (window.IZ = {});
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

  /* ---------- Gantt ---------- */
  IZ.gantt = function (el, rows, opts) {
    opts = opts || {};
    const holder = typeof el === 'string' ? document.querySelector(el) : el;
    if (!holder) return;
    const render = data => {
      if (!data.length) { holder.innerHTML = '<div class="empty">Belum ada jadwal.</div>'; return; }
      holder.innerHTML = '<svg></svg>';
      const g = new Gantt(holder.querySelector('svg'), data, {
        view_mode: opts.mode || 'Week',
        date_format: 'YYYY-MM-DD',
        bar_height: 20, padding: 14, language: 'en',
        custom_popup_html: t => '<div class="gp"><b>' + esc(t.name) + '</b>' +
          esc(t._start.toLocaleDateString('id-ID')) + ' – ' + esc(new Date(t._end - 1).toLocaleDateString('id-ID')) +
          '<br>Status: ' + esc(t.status) + ' · ' + t.progress + '%' +
          (t.pic ? '<br>PIC: ' + esc(t.pic) : '') + (t.party ? '<br>' + esc(t.party) : '') +
          (t.baseline ? '<br>Baseline: ' + esc(t.baseline) : '') +
          (t.kind === 'task' || t.kind === 'milestone' ? '<br>Slack: ' + t.slack + ' hari' + (t.custom_class.includes('kritis') ? ' · <b style="display:inline;color:#c0392b">jalur kritis</b>' : '') : '') +
          '<br><a href="' + t.url + '">Buka »</a></div>',
        on_click: t => { if (opts.clickOpens !== false && t.url) window.location = t.url; },
        on_date_change: (t, start, end) => {
          if (!opts.editable || !opts.datesUrl) { setTimeout(() => render(data), 50); return; }
          const id = t.id.replace(/^t/, '');
          const fmt = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
          const realEnd = new Date(end.getTime() - 1);
          fetch(opts.datesUrl.replace('__ID__', id), {
            method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': IZ.csrf, 'Accept': 'application/json'},
            body: JSON.stringify({start: fmt(start), end: fmt(realEnd)})
          }).then(r => { if (!r.ok) throw r; return load(); }).catch(() => alert('Gagal menyimpan jadwal. Hanya manajer yang bisa menggeser jadwal.'));
        }
      });
      holder._gantt = g;
      const today = holder.querySelector('.today-highlight');
      if (today && opts.scrollToday !== false) {
        const sc = holder.closest('.gantt-wrap') || holder;
        sc.scrollLeft = Math.max(0, today.getAttribute('x') - 200);
      }
    };
    const load = () => typeof rows === 'string'
      ? fetch(rows, {headers: {Accept: 'application/json'}}).then(r => r.json()).then(d => { holder._data = d; render(d); })
      : Promise.resolve(render(rows));
    load();
    document.querySelectorAll('[data-gantt-mode]').forEach(b => b.addEventListener('click', () => {
      document.querySelectorAll('[data-gantt-mode]').forEach(x => x.classList.toggle('ghost', x !== b));
      holder._gantt && holder._gantt.change_view_mode(b.dataset.ganttMode);
    }));
  };

  /* ---------- @mention autocomplete ---------- */
  function mentions() {
    document.querySelectorAll('textarea[data-mention]').forEach(ta => {
      if (ta._m) return; ta._m = true;
      const box = document.createElement('div'); box.className = 'mention-list';
      ta.parentNode.classList.add('mention-box'); ta.parentNode.appendChild(box);
      let items = [], idx = 0;
      const close = () => { box.style.display = 'none'; items = []; };
      const pick = h => {
        const pos = ta.selectionStart, before = ta.value.slice(0, pos).replace(/@([\w.-]*)$/, '@' + h + ' ');
        ta.value = before + ta.value.slice(pos); ta.focus(); ta.selectionStart = ta.selectionEnd = before.length; close();
      };
      ta.addEventListener('input', () => {
        const m = ta.value.slice(0, ta.selectionStart).match(/(?:^|\s)@([\w.-]*)$/);
        if (!m) return close();
        const q = m[1].toLowerCase();
        items = (IZ.people || []).filter(p => p.h.toLowerCase().includes(q) || p.n.toLowerCase().includes(q)).slice(0, 8);
        if (!items.length) return close();
        idx = 0;
        box.innerHTML = items.map((p, i) => '<div data-h="' + esc(p.h) + '" class="' + (i === 0 ? 'on' : '') + '"><b>@' + esc(p.h) + '</b> · ' + esc(p.n) + '</div>').join('');
        box.style.display = 'block'; box.style.left = '0'; box.style.top = (ta.offsetTop + ta.offsetHeight) + 'px';
      });
      box.addEventListener('mousedown', e => { const d = e.target.closest('[data-h]'); if (d) { e.preventDefault(); pick(d.dataset.h); } });
      ta.addEventListener('keydown', e => {
        if (box.style.display !== 'block') return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
          e.preventDefault(); idx = (idx + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
          box.querySelectorAll('div').forEach((d, i) => d.classList.toggle('on', i === idx));
        } else if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); pick(items[idx].h); }
        else if (e.key === 'Escape') close();
      });
      ta.addEventListener('blur', () => setTimeout(close, 150));
    });
  }

  /* ---------- unggah file: drag & drop + daftar nama ---------- */
  function drops() {
    document.querySelectorAll('.drop').forEach(d => {
      if (d._d) return; d._d = true;
      const input = d.querySelector('input[type=file]'), list = d.querySelector('.drop-list');
      const show = () => { if (list) list.innerHTML = Array.from(input.files).map(f => esc(f.name) + ' (' + Math.max(1, Math.round(f.size / 1024)) + ' KB)').join('<br>'); };
      input.addEventListener('change', show);
      ['dragenter', 'dragover'].forEach(ev => d.addEventListener(ev, e => { e.preventDefault(); d.classList.add('over'); }));
      ['dragleave', 'drop'].forEach(ev => d.addEventListener(ev, e => { e.preventDefault(); d.classList.remove('over'); }));
      d.addEventListener('drop', e => { input.files = e.dataTransfer.files; show(); });
    });
  }

  /* ---------- grafik ---------- */
  IZ.chart = function (id, cfg) {
    const el = document.getElementById(id);
    if (!el || !window.Chart) return;
    Chart.defaults.font.family = '"Inter","Segoe UI",system-ui,sans-serif';
    Chart.defaults.color = '#64736e';
    cfg.options = Object.assign({responsive: true, maintainAspectRatio: false, plugins: {legend: {position: 'bottom', labels: {boxWidth: 12}}}}, cfg.options || {});
    return new Chart(el, cfg);
  };
  IZ.colors = ['#0f6b5c', '#d98e04', '#2563a8', '#c0392b', '#7b5cc7', '#1f8a4c', '#8a6d3b', '#4e6e7a'];

  /* ---------- konfirmasi ---------- */
  document.addEventListener('submit', e => {
    const m = e.target.dataset.confirm; if (m && !confirm(m)) e.preventDefault();
  });

  /* ---------- pilihan sumber WBS di form proyek baru ---------- */
  function sourceSwitch() {
    const radios = document.querySelectorAll('input[name=source]');
    if (!radios.length) return;
    const upd = () => {
      const v = document.querySelector('input[name=source]:checked')?.value;
      document.querySelectorAll('[data-source]').forEach(x => x.style.display = x.dataset.source === v ? '' : 'none');
    };
    radios.forEach(r => r.addEventListener('change', upd)); upd();
  }

  /* ---------- filter item di form ---------- */
  function itemFilter() {
    const f = document.getElementById('item-filter'); if (!f) return;
    f.addEventListener('input', () => {
      const q = f.value.toLowerCase();
      document.querySelectorAll('[data-item]').forEach(r => r.style.display = r.dataset.item.includes(q) ? '' : 'none');
    });
  }

  /* ---------- panel bantuan ---------- */
  function helpPanel() {
    const panel = document.getElementById('help-panel'); if (!panel) return;
    const set = open => { panel.classList.toggle('open', open); panel.setAttribute('aria-hidden', open ? 'false' : 'true'); };
    document.querySelectorAll('[data-help-open]').forEach(b => b.addEventListener('click', () => set(true)));
    panel.querySelectorAll('[data-help-close]').forEach(b => b.addEventListener('click', () => set(false)));
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') set(false);
      const typing = /INPUT|TEXTAREA|SELECT/.test(document.activeElement?.tagName || '');
      if (e.key === '?' && !typing) { e.preventDefault(); set(!panel.classList.contains('open')); }
    });
    if (location.hash === '#bantuan') set(true);
  }

  document.addEventListener('DOMContentLoaded', () => { mentions(); drops(); sourceSwitch(); itemFilter(); helpPanel(); });
})();
