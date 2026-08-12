/* Herní deník – front-end interaktivita (filtry Herny + okno zápisu partie) */
(function () {
  'use strict';

  /* ---------- FILTRY / ŘAZENÍ / ZOBRAZENÍ na Herně ---------- */
  var grid = document.getElementById('hdGrid');
  if (grid) {
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.game-card'));
    var fSearch = document.getElementById('hdSearch');
    var fPlayers = document.getElementById('hdPlayers');
    var fTime = document.getElementById('hdTime');
    var fDiff = document.getElementById('hdDiff');
    var fPub = document.getElementById('hdPublisher');
    var fSort = document.getElementById('hdSort');
    var countEl = document.getElementById('hdCount');

    function norm(s) { return (s || '').toString().toLowerCase(); }

    function matches(card) {
      // hledání podle názvu
      if (fSearch && fSearch.value.trim()) {
        if (norm(card.dataset.name).indexOf(norm(fSearch.value.trim())) === -1) return false;
      }
      // počet hráčů
      if (fPlayers && fPlayers.value) {
        var n = parseInt(fPlayers.value, 10);
        var mn = parseInt(card.dataset.pmin, 10) || 0;
        var mx = parseInt(card.dataset.pmax, 10) || mn || 99;
        if (!(mn <= n && n <= mx)) return false;
      }
      // délka
      if (fTime && fTime.value) {
        var t = parseInt(card.dataset.time, 10) || 0;
        if (fTime.value === 's' && !(t && t <= 30)) return false;
        if (fTime.value === 'm' && !(t > 30 && t <= 60)) return false;
        if (fTime.value === 'l' && !(t > 60)) return false;
      }
      // obtížnost
      if (fDiff && fDiff.value && card.dataset.diff !== fDiff.value) return false;
      // vydavatel
      if (fPub && fPub.value && card.dataset.pub !== fPub.value) return false;
      return true;
    }

    function apply() {
      var shown = 0;
      cards.forEach(function (c) {
        var ok = matches(c);
        c.style.display = ok ? '' : 'none';
        if (ok) shown++;
      });
      if (countEl) countEl.textContent = shown;
      sort();
    }

    function sort() {
      if (!fSort) return;
      var mode = fSort.value;
      var vis = cards.filter(function (c) { return c.style.display !== 'none'; });
      vis.sort(function (a, b) {
        if (mode === 'diff') return (parseFloat(a.dataset.weight) || 0) - (parseFloat(b.dataset.weight) || 0) || a.dataset.name.localeCompare(b.dataset.name, 'cs');
        if (mode === 'plays') return (parseInt(b.dataset.plays, 10) || 0) - (parseInt(a.dataset.plays, 10) || 0) || a.dataset.name.localeCompare(b.dataset.name, 'cs');
        return a.dataset.name.localeCompare(b.dataset.name, 'cs');
      });
      vis.forEach(function (c) { grid.appendChild(c); });
    }

    [fSearch, fPlayers, fTime, fDiff, fPub, fSort].forEach(function (el) {
      if (!el) return;
      el.addEventListener('input', apply);
      el.addEventListener('change', apply);
    });

    // přepínač dlaždice / seznam
    var toggles = document.querySelectorAll('.hd-view-btn');
    function setView(v) {
      grid.classList.toggle('list', v === 'list');
      toggles.forEach(function (b) { b.classList.toggle('active', b.dataset.view === v); });
      try { localStorage.setItem('hd_view', v); } catch (e) {}
    }
    toggles.forEach(function (b) { b.addEventListener('click', function () { setView(b.dataset.view); }); });
    var saved = 'grid';
    try { saved = localStorage.getItem('hd_view') || 'grid'; } catch (e) {}
    setView(saved);

    apply();
  }

  /* ---------- OKNO „ZAPSAT PARTII" ---------- */
  var modal = document.getElementById('hdPlayModal');
  if (modal) {
    var gameSel = document.getElementById('hdPlayGame');
    var playId = document.getElementById('hdPlayId');
    var playTitle = document.getElementById('hdPlayTitle');
    var dateInput = modal.querySelector('input[name="play_date"]');
    var noteInput = modal.querySelector('textarea[name="note"]');
    var defaultDate = dateInput ? dateInput.value : '';

    var extList = document.getElementById('hdExtList');
    var extName = document.getElementById('hdExtName');

    function setWon(row) {
      var p = row.querySelector('.js-played'), w = row.querySelector('.js-won');
      if (p && w) { w.disabled = !p.checked; if (!p.checked) w.checked = false; }
    }
    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    function addExtRow(name, isWinner) {
      if (!extList || !name) return;
      var init = name.trim().charAt(0).toUpperCase() || '?';
      var row = document.createElement('div');
      row.className = 'hd-prow ext';
      row.innerHTML =
        '<span class="hd-pchk"><input type="hidden" name="ext_players[]" value="' + esc(name) + '">' +
        '<span class="avatar ext-av" style="width:24px;height:24px;font-size:11px">' + esc(init) + '</span> ' + esc(name) + ' <span class="ext-tag">host</span></span>' +
        '<label class="hd-wchk" title="Vyhrál"><input type="checkbox" class="js-won-ext" name="ext_winners[]" value="' + esc(name) + '"' + (isWinner ? ' checked' : '') + '> 🏆</label>' +
        '<button type="button" class="js-rm-ext" title="Odebrat">×</button>';
      extList.appendChild(row);
    }
    function resetPlay() {
      if (playId) playId.value = '';
      if (gameSel) gameSel.value = '';
      if (dateInput) dateInput.value = defaultDate;
      if (noteInput) noteInput.value = '';
      modal.querySelectorAll('.js-played').forEach(function (c) { c.checked = false; });
      modal.querySelectorAll('.js-won').forEach(function (c) { c.checked = false; c.disabled = true; });
      if (extList) extList.innerHTML = '';
      if (extName) extName.value = '';
      if (playTitle) playTitle.textContent = '🎲 Zapsat partii';
    }
    function openModal(gameId) {
      resetPlay();
      if (gameId && gameSel) gameSel.value = gameId;
      modal.hidden = false;
      document.body.style.overflow = 'hidden';
    }
    function editPlay(data) {
      resetPlay();
      if (playId) playId.value = data.id || '';
      if (gameSel) gameSel.value = data.game || '';
      if (dateInput && data.play_date) dateInput.value = data.play_date;
      if (noteInput) noteInput.value = data.note || '';
      var players = (data.players || []).map(String), winners = (data.winners || []).map(String);
      modal.querySelectorAll('.hd-prow:not(.ext)').forEach(function (row) {
        var p = row.querySelector('.js-played'), w = row.querySelector('.js-won');
        if (p && players.indexOf(p.value) > -1) {
          p.checked = true;
          if (w) { w.disabled = false; if (winners.indexOf(w.value) > -1) w.checked = true; }
        }
      });
      var extP = data.ext_players || [], extW = (data.ext_winners || []).map(String);
      extP.forEach(function (n) { addExtRow(n, extW.indexOf(String(n)) > -1); });
      if (playTitle) playTitle.textContent = '✏️ Upravit partii';
      modal.hidden = false;
      document.body.style.overflow = 'hidden';
    }
    function closeModal() {
      modal.hidden = true;
      document.body.style.overflow = '';
    }

    document.addEventListener('click', function (e) {
      var opener = e.target.closest('.js-open-play');
      if (opener) { e.preventDefault(); openModal(opener.dataset.game || ''); return; }
      var ed = e.target.closest('.js-edit-play');
      if (ed) { e.preventDefault(); try { editPlay(JSON.parse(ed.dataset.hd || '{}')); } catch (_) {} return; }
      if (e.target.closest('.js-add-ext')) {
        e.preventDefault();
        var n = extName ? extName.value.trim() : '';
        if (n) { addExtRow(n, false); extName.value = ''; extName.focus(); }
        return;
      }
      var rm = e.target.closest('.js-rm-ext');
      if (rm) { e.preventDefault(); var r = rm.closest('.hd-prow'); if (r) r.remove(); return; }
      if (e.target.closest('.js-close-play')) { e.preventDefault(); closeModal(); }
    });
    if (extName) extName.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); var n = extName.value.trim(); if (n) { addExtRow(n, false); extName.value = ''; } } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

    // vítěze lze zaškrtnout jen u toho, kdo hrál
    modal.querySelectorAll('.hd-prow').forEach(function (row) {
      var played = row.querySelector('.js-played');
      if (played) played.addEventListener('change', function () { setWon(row); });
    });
  }

  /* ---------- FORMULÁŘ HRY (Nová hra / úprava) ---------- */
  var gameModal = document.getElementById('hdGameModal');
  var GF = { // mapa: klíč dat -> id pole
    name: 'gfName', players_min: 'gfPmin', players_max: 'gfPmax', time_min: 'gfTmin',
    time_max: 'gfTmax', difficulty: 'gfDiff', year: 'gfYear', publisher: 'gfPub',
    image_url: 'gfImg', bgg_url: 'gfBgg', pub_url: 'gfPubUrl',
    desc_priprava: 'gfDP', desc_prubeh: 'gfDPr', desc_konec: 'gfDK'
  };
  function gfEl(id) { return document.getElementById(id); }
  function openGameForm() { if (gameModal) { gameModal.hidden = false; document.body.style.overflow = 'hidden'; } }
  function closeGameForm() { if (gameModal) { gameModal.hidden = true; document.body.style.overflow = ''; } }
  function resetGameForm() {
    if (!gameModal) return;
    Object.keys(GF).forEach(function (k) { var el = gfEl(GF[k]); if (el) { el.value = ''; el.classList.remove('imp-changed'); } });
    var idEl = gfEl('gfId'); if (idEl) idEl.value = '';
    var t = gfEl('gfTitle'); if (t) t.textContent = 'Nová hra';
  }
  function fillGameForm(data, highlight) {
    resetGameForm();
    var idEl = gfEl('gfId'); if (idEl) idEl.value = data.id || '';
    var t = gfEl('gfTitle'); if (t) t.textContent = data.id ? 'Upravit hru' : 'Nová hra';
    Object.keys(GF).forEach(function (k) {
      var el = gfEl(GF[k]);
      if (!el) return;
      var val = data[k] != null ? String(data[k]) : '';
      el.value = val;
      if (highlight && val && k.indexOf('desc_') !== 0) el.classList.add('imp-changed');
    });
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('.js-open-gameform')) { e.preventDefault(); resetGameForm(); openGameForm(); }
    if (e.target.closest('.js-close-gameform')) { e.preventDefault(); closeGameForm(); }
    var editBtn = e.target.closest('.js-edit-game');
    if (editBtn) {
      e.preventDefault();
      try { fillGameForm(JSON.parse(editBtn.dataset.hd || '{}'), false); openGameForm(); } catch (_) {}
    }
  });

  /* ---------- IMPORT (načtení přes AJAX -> formulář) ---------- */
  var importModal = document.getElementById('hdImportModal');
  document.addEventListener('click', function (e) {
    if (e.target.closest('.js-open-import') && importModal) { e.preventDefault(); importModal.hidden = false; document.body.style.overflow = 'hidden'; }
    if (e.target.closest('.js-close-import') && importModal) { e.preventDefault(); importModal.hidden = true; document.body.style.overflow = ''; }

    var parseBtn = e.target.closest('.js-import-parse');
    if (parseBtn && typeof HD !== 'undefined') {
      e.preventDefault();
      var text = (gfEl('hdImportText') || {}).value || '';
      var url = (gfEl('hdImportUrl') || {}).value || '';
      if (!text.trim() && !url.trim()) { alert('Vlož obsah stránky (nebo aspoň odkaz na hru).'); return; }
      var orig = parseBtn.textContent;
      parseBtn.textContent = 'Načítám…'; parseBtn.disabled = true;
      var body = new URLSearchParams();
      body.set('action', 'hd_import_parse');
      body.set('nonce', HD.parseNonce);
      body.set('content', text);
      body.set('url', url);
      fetch(HD.ajax, { method: 'POST', body: body, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          parseBtn.textContent = orig; parseBtn.disabled = false;
          if (!res || !res.success) { alert((res && res.data && res.data.msg) || 'Načtení se nepodařilo.'); return; }
          fillGameForm(res.data, true);
          if (importModal) importModal.hidden = true;
          openGameForm();
        })
        .catch(function () { parseBtn.textContent = orig; parseBtn.disabled = false; alert('Chyba při načítání.'); });
    }
  });

  /* ---------- EDITOR OBRÁZKU ---------- */
  var cover = document.getElementById('hdCoverModal');
  if (cover) {
    var stage = document.getElementById('hdStage');
    var iGame = document.getElementById('hdCoverGame');
    var iX = document.getElementById('hdCoverX');
    var iY = document.getElementById('hdCoverY');
    var iZoom = document.getElementById('hdCoverZoom');
    var iSize = document.getElementById('hdCoverSize');
    var iRemove = document.getElementById('hdCoverRemove');
    var fileInput = document.getElementById('hdCoverFile');
    var urlInput = document.getElementById('hdCoverUrl');
    var st = { image: '', x: 50, y: 50, zoom: 1, a: null };

    function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
    function sizeCss() { return st.a >= 1 ? 'auto ' + (st.zoom * 100) + '%' : (st.zoom * 100) + '% auto'; }

    function draw() {
      if (st.image && st.a) {
        stage.innerHTML = '<div class="cover" style="background-image:url(\'' + st.image.replace(/'/g, "%27") + '\');background-size:' + sizeCss() + ';background-position:' + st.x + '% ' + st.y + '%"></div>';
      } else {
        stage.innerHTML = '<div class="ph">🎲</div>';
      }
      // zapiš do skrytých polí
      iX.value = st.x; iY.value = st.y; iZoom.value = st.zoom;
      iSize.value = (st.image && st.a) ? sizeCss() : '';
    }
    function dims() {
      var S = stage.clientWidth || 280;
      if (!st.a) return null;
      var DW, DH;
      if (st.a >= 1) { DH = st.zoom * S; DW = DH * st.a; } else { DW = st.zoom * S; DH = DW / st.a; }
      return { OW: Math.max(0, DW - S), OH: Math.max(0, DH - S) };
    }
    function loadAspect(cb) {
      if (!st.image) { st.a = null; cb && cb(); return; }
      var im = new Image();
      im.onload = function () { st.a = im.naturalWidth / im.naturalHeight || 1; cb && cb(); };
      im.onerror = function () { st.a = null; cb && cb(); };
      im.src = st.image;
    }
    function refresh() { loadAspect(draw); }

    function openCover(btn) {
      st.image = btn.dataset.img || '';
      st.x = parseFloat(btn.dataset.x); if (isNaN(st.x)) st.x = 50;
      st.y = parseFloat(btn.dataset.y); if (isNaN(st.y)) st.y = 50;
      st.zoom = parseFloat(btn.dataset.zoom) || 1;
      iGame.value = btn.dataset.game || '';
      iRemove.value = '';
      urlInput.value = '';
      if (fileInput) fileInput.value = '';
      refresh();
      cover.hidden = false;
      document.body.style.overflow = 'hidden';
    }
    function closeCover() { cover.hidden = true; document.body.style.overflow = ''; }

    document.addEventListener('click', function (e) {
      var op = e.target.closest('.js-edit-cover');
      if (op) { e.preventDefault(); openCover(op); return; }
      if (e.target.closest('.js-close-cover')) { e.preventDefault(); closeCover(); }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !cover.hidden) closeCover(); });

    // tažení
    var dragging = false, lx = 0, ly = 0;
    stage.addEventListener('pointerdown', function (e) {
      if (!st.image || !st.a) return;
      dragging = true; lx = e.clientX; ly = e.clientY; stage.classList.add('dragging');
      try { stage.setPointerCapture(e.pointerId); } catch (_) {}
    });
    stage.addEventListener('pointermove', function (e) {
      if (!dragging) return;
      var d = dims(); if (!d) return;
      var dx = e.clientX - lx, dy = e.clientY - ly; lx = e.clientX; ly = e.clientY;
      if (d.OW > 0) st.x = clamp(st.x - dx / d.OW * 100, 0, 100);
      if (d.OH > 0) st.y = clamp(st.y - dy / d.OH * 100, 0, 100);
      draw();
    });
    function endDrag() { dragging = false; stage.classList.remove('dragging'); }
    stage.addEventListener('pointerup', endDrag);
    stage.addEventListener('pointercancel', endDrag);
    stage.addEventListener('wheel', function (e) {
      if (!st.image || !st.a) return;
      e.preventDefault();
      st.zoom = clamp(st.zoom + (e.deltaY < 0 ? 0.12 : -0.12), 1, 4);
      draw();
    }, { passive: false });
    function zoomBy(dz) { if (!st.image || !st.a) return; st.zoom = clamp(st.zoom + dz, 1, 4); draw(); }
    document.getElementById('hdZoomIn').addEventListener('click', function () { zoomBy(0.2); });
    document.getElementById('hdZoomOut').addEventListener('click', function () { zoomBy(-0.2); });

    urlInput.addEventListener('input', function () {
      st.image = urlInput.value.trim(); st.x = 50; st.y = 50; st.zoom = 1; iRemove.value = '';
      if (fileInput) fileInput.value = '';
      refresh();
    });
    if (fileInput) fileInput.addEventListener('change', function () {
      var f = fileInput.files[0]; if (!f) return;
      st.image = URL.createObjectURL(f); st.x = 50; st.y = 50; st.zoom = 1; iRemove.value = '';
      urlInput.value = '';
      refresh();
    });
    document.getElementById('hdCoverClear').addEventListener('click', function () {
      st.image = ''; st.a = null; iRemove.value = '1'; urlInput.value = '';
      if (fileInput) fileInput.value = '';
      draw();
    });
  }

  /* ---------- FORMULÁŘ HRÁČE ---------- */
  var playerModal = document.getElementById('hdPlayerModal');
  if (playerModal) {
    var pfId = gfEl('pfId'), pfNick = gfEl('pfNick'), pfName = gfEl('pfName'),
        pfColor = gfEl('pfColor'), pfEmoji = gfEl('pfEmoji'), pfAvatar = gfEl('pfAvatar'), pfTitle = gfEl('pfTitle');
    function pv() {
      if (!pfAvatar) return;
      var label = (pfNick.value.trim() || pfName.value.trim() || '?');
      pfAvatar.style.background = pfColor.value || '#eeb088';
      pfAvatar.textContent = pfEmoji.value.trim() || label.charAt(0).toUpperCase() || '?';
    }
    function resetPlayer() {
      pfId.value = ''; pfNick.value = ''; pfName.value = ''; pfColor.value = '#eeb088'; pfEmoji.value = '';
      if (pfTitle) pfTitle.textContent = 'Nový hráč'; pv();
    }
    function openPlayer() { playerModal.hidden = false; document.body.style.overflow = 'hidden'; }
    function closePlayer() { playerModal.hidden = true; document.body.style.overflow = ''; }
    [pfNick, pfName, pfColor, pfEmoji].forEach(function (el) { if (el) el.addEventListener('input', pv); });

    document.addEventListener('click', function (e) {
      if (e.target.closest('.js-open-player')) { e.preventDefault(); resetPlayer(); openPlayer(); return; }
      if (e.target.closest('.js-close-player')) { e.preventDefault(); closePlayer(); return; }
      var ed = e.target.closest('.js-edit-player');
      if (ed) {
        e.preventDefault();
        try {
          var d = JSON.parse(ed.dataset.hd || '{}');
          pfId.value = d.id || ''; pfNick.value = d.nick || ''; pfName.value = d.name || '';
          pfColor.value = d.color || '#eeb088'; pfEmoji.value = d.emoji || '';
          if (pfTitle) pfTitle.textContent = 'Upravit hráče'; pv(); openPlayer();
        } catch (_) {}
        return;
      }
      var sw = e.target.closest('.js-swatch');
      if (sw) { e.preventDefault(); pfColor.value = sw.dataset.c; pv(); return; }
      var em = e.target.closest('.js-emoji');
      if (em) { e.preventDefault(); pfEmoji.value = em.dataset.e || ''; pv(); return; }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !playerModal.hidden) closePlayer(); });
  }

  /* ---------- POTVRZENÍ SMAZÁNÍ (hra i hráč) ---------- */
  document.addEventListener('click', function (e) {
    var del = e.target.closest('.js-del-game');
    if (del) {
      if (!window.confirm('Opravdu smazat „' + (del.dataset.name || 'tuto hru') + '"? Přesune se do koše.')) e.preventDefault();
      return;
    }
    var delP = e.target.closest('.js-del-player');
    if (delP) {
      if (!window.confirm('Opravdu smazat hráče „' + (delP.dataset.name || '') + '"? Přesune se do koše.')) e.preventDefault();
    }
  });
})();
