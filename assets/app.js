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

    function openModal(gameId) {
      if (gameId && gameSel) gameSel.value = gameId;
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
      if (e.target.closest('.js-close-play')) { e.preventDefault(); closeModal(); }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

    // vítěze lze zaškrtnout jen u toho, kdo hrál
    modal.querySelectorAll('.hd-prow').forEach(function (row) {
      var played = row.querySelector('.js-played');
      var won = row.querySelector('.js-won');
      if (!played || !won) return;
      played.addEventListener('change', function () {
        won.disabled = !played.checked;
        if (!played.checked) won.checked = false;
      });
    });
  }
})();
