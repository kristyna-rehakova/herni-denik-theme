<?php
/**
 * Import hry vložením obsahu stránky ze Zatrolených / Mindoku.
 * Parsery přeneseny z původní HTML appky.
 */
if (!defined('ABSPATH')) exit;

/* ---------------- detekce zdroje ---------------- */
function hd_detect_source($text, $url = '') {
    // Nejspolehlivější je odkaz
    if ($url) {
        if (stripos($url, 'mindok') !== false) return 'mindok';
        if (stripos($url, 'zatrolene') !== false) return 'zatrolene';
    }
    $zat = preg_match('/Zatrolené hry|hodnoceno\s+\d+\s*x|Seznam vlastníků/iu', $text);
    $mindok = preg_match('/Doba hraní:|Náročnost:|Přidat na seznam přání|DOKŮ/iu', $text);
    return ($mindok && !$zat) ? 'mindok' : 'zatrolene';
}

/* ---------------- Zatrolené: název ---------------- */
function hd_z_name($text) {
    $raw = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $text)), function ($s) { return $s !== ''; }));
    $JUNK = ['/^Zatrolené hry$/iu','/^hodnoceno/iu','/nehodnoceno/iu','/Seznam vlastníků/iu','/^Hodnocení/iu','/^Hru mám/iu','/^Hru chci/iu','/^Přidat na seznam/iu','/^přihlásit/iu','/^registrovat/iu'];
    $isJunk = function ($l) use ($JUNK) {
        if (strpos($l, "\t") !== false) return true;
        if (mb_strlen($l) > 80) return true;
        foreach ($JUNK as $re) if (preg_match($re, $l)) return true;
        return false;
    };
    $clean = function ($s) { return trim(preg_replace('/\s*[-–—]\s*obr[áa]zek\.?\s*$/iu', '', $s)); };
    $cand = '';
    $iP = -1; foreach ($raw as $i => $l) { if (preg_match('/Počet hráčů/iu', $l)) { $iP = $i; break; } }
    if ($iP > 0) { for ($i = $iP - 1; $i >= 0 && $i >= $iP - 8; $i--) { if (!$isJunk($raw[$i])) { $cand = $raw[$i]; break; } } }
    if ($cand === '') { $iZ = -1; foreach ($raw as $i => $l) { if (preg_match('/^Zatrolené hry$/iu', $l)) { $iZ = $i; break; } } if ($iZ >= 0 && isset($raw[$iZ + 1]) && !$isJunk($raw[$iZ + 1])) $cand = $raw[$iZ + 1]; }
    if ($cand === '') { $iH = -1; foreach ($raw as $i => $l) { if (preg_match('/^hodnoceno\s+\d+/iu', $l)) { $iH = $i; break; } } if ($iH > 0 && !$isJunk($raw[$iH - 1])) $cand = $raw[$iH - 1]; }
    return $clean($cand);
}

/* ---------------- Zatrolené: popis (Příprava/Průběh/Konec) ---------------- */
function hd_z_desc($text) {
    $rawLines = explode("\n", preg_replace('/\r/', '', $text));
    $lines = array_map('trim', $rawLines);
    $HEAD = [
        ['priprava', '/^P[řr][íi]prava(\s+hry)?\s*:?$/iu'],
        ['prubeh',   '/^Pr[ůu]b[ěe]h(\s+hry)?\s*:?$/iu'],
        ['konec',    '/^Konec(\s+hry)?\s*:?$/iu'],
        ['bodovani', '/^Bodov[áa]n[íi]\s*:?$/iu'],
    ];
    $STOP = '/^(Autor|Rozší[řr]en|R[ůu]zn[éeě]|Video|Odkaz|Diskuse|Koment|Fotk|Galer|Nov[áa] hodnocen|Pod lupou|Vydavatel|Distributor|Sd[íi]let|Podobn|Po[čc]et hr[áa]|Doporu[čc]en|Rok vyd[áa]n|[čČ]e[šs]tina)/iu';
    $found = [];
    foreach ($lines as $i => $l) {
        foreach ($HEAD as $h) {
            if (preg_match($h[1], $l) && !in_array($h[0], array_column($found, 'key'), true)) { $found[] = ['key' => $h[0], 'line' => $i]; break; }
        }
    }
    if (!$found) {
        // Žádné strukturované sekce (Příprava/Průběh/Konec) – vezmi obecný „Popis hry" a vlož ho celý do Průběhu.
        $iP = -1;
        foreach ($lines as $i => $l) { if (preg_match('/^Popis hry\s*:?$/iu', $l)) { $iP = $i; break; } }
        if ($iP < 0) return null;
        $STOP2 = '/^(Autor\b|Design|Ilustr|Rozší[řr]en[íi] hry|Dal[šs][íi] n[áa]zvy|Vyb[íi]r[áa]me z Bazaru|Nejnov[ěe]j|Kde se disku|Velk[ée] hern|O Zatrolen|Celkov[ée] hodnocen|N[áa]hled hry|Sd[íi]lej)/iu';
        $start = $iP + 1; $end = count($lines);
        for ($j = $start; $j < count($lines); $j++) { if ($lines[$j] !== '' && preg_match($STOP2, $lines[$j])) { $end = $j; break; } }
        $chunk = trim(preg_replace('/\n{3,}/', "\n\n", implode("\n", array_slice($rawLines, $start, $end - $start))));
        return $chunk !== '' ? ['prubeh' => $chunk] : null;
    }
    usort($found, function ($a, $b) { return $a['line'] - $b['line']; });
    $out = [];
    for ($i = 0; $i < count($found); $i++) {
        $start = $found[$i]['line'] + 1;
        if ($i + 1 < count($found)) { $end = $found[$i + 1]['line']; }
        else { $end = count($lines); for ($j = $start; $j < count($lines); $j++) { if (preg_match($STOP, $lines[$j])) { $end = $j; break; } } }
        $chunk = trim(preg_replace('/\n{3,}/', "\n\n", implode("\n", array_slice($rawLines, $start, $end - $start))));
        if ($chunk !== '') $out[$found[$i]['key']] = $chunk;
    }
    unset($out['bodovani']); // bodování se z importu neplní
    return $out ?: null;
}

/* ---------------- Zatrolené: hlavní parser ---------------- */
function hd_parse_zatrolene($text, $url) {
    $joined = preg_replace('/\s+/u', ' ', $text);
    $out = [];
    $name = hd_z_name($text); if ($name) $out['name'] = $name;
    if (preg_match('/Počet hráčů:?\s*(\d+)\s*(?:[–—\-]\s*(\d+))?/u', $joined, $m)) { $out['minPlayers'] = (int)$m[1]; $out['maxPlayers'] = !empty($m[2]) ? (int)$m[2] : (int)$m[1]; }
    if (preg_match('/Herní doba:?\s*(\d+)\s*(?:[–—\-]\s*(\d+))?\s*min/u', $joined, $m)) { $out['minTime'] = (int)$m[1]; $out['maxTime'] = !empty($m[2]) ? (int)$m[2] : (int)$m[1]; }
    if (preg_match('/Rok vydání:?\s*(\d{4})/u', $joined, $r)) $out['year'] = (int)$r[1];
    $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $text)), function ($s) { return $s !== ''; }));
    $cleanPub = function ($s) { return trim(preg_replace('/\s*[-–—]\s*(logo|obr[áa]zek)\.?\s*$/iu', '', $s)); };
    $iV = -1; foreach ($lines as $i => $l) { if (preg_match('/^Vydavatel/iu', $l)) { $iV = $i; break; } }
    if ($iV >= 0) {
        if (strpos($lines[$iV], "\t") !== false) { $parts = explode("\t", $lines[$iV]); array_shift($parts); $out['publisher'] = $cleanPub(implode(' ', $parts)); }
        else { for ($i = $iV + 1; $i < count($lines) && $i <= $iV + 3; $i++) { $l = $lines[$i]; if ($l && strpos($l, "\t") === false && !preg_match('/^(Rok vydání|Herní|Počet|Doporučený|Čeština|Autor|Design|Ilustr|Distributor)/iu', $l)) { $out['publisher'] = $cleanPub($l); break; } } }
    }
    if ($url && trim($url)) $out['bggUrl'] = trim($url);
    $dparts = hd_z_desc($text); if ($dparts) $out['desc'] = $dparts;
    return $out;
}

/* ---------------- Mindok parser ---------------- */
function hd_parse_mindok($text) {
    $lines = array_map('trim', explode("\n", preg_replace('/\r/', '', $text)));
    $out = ['publisher' => 'MINDOK'];
    // hodnotu bere buď inline za popiskem (na stejném řádku), nebo z dalšího řádku
    $valAfter = function ($labelRe) use ($lines) {
        foreach ($lines as $k => $l) {
            if (preg_match($labelRe, $l)) {
                $after = trim(preg_replace($labelRe, '', $l, 1));
                if ($after !== '') return $after;
                for ($j = $k + 1; $j < count($lines) && $j <= $k + 3; $j++) { if (trim($lines[$j]) !== '') return trim($lines[$j]); }
                return null;
            }
        }
        return null;
    };
    $iWish = -1; foreach ($lines as $k => $l) { if (preg_match('/^Přidat na seznam přání/iu', $l)) { $iWish = $k; break; } }
    $BADGE = '/^(Vyrobeno|Novink|Bestseller|Výprodej|Akce\b|Sleva|Doprava|V prodeji|Poslední|Skladem|Předprodej|Výhodně|Oceněn|Cena )/iu';
    if ($iWish > 0) { for ($j = $iWish - 1; $j >= 0; $j--) { $l = $lines[$j]; if ($l !== '' && !preg_match($BADGE, $l)) { $out['name'] = $l; break; } } }
    $pv = $valAfter('/^Počet hráčů:?\s*/iu'); if ($pv) { if (preg_match('/(\d+)\s*[–—-]\s*(\d+)/u', $pv, $m)) { $out['minPlayers'] = (int)$m[1]; $out['maxPlayers'] = (int)$m[2]; } elseif (preg_match('/(\d+)/', $pv, $s)) { $out['minPlayers'] = (int)$s[1]; $out['maxPlayers'] = (int)$s[1]; } }
    $tv = $valAfter('/^(Doba hraní|Hrací doba|Herní doba):?\s*/iu'); if ($tv) { if (preg_match('/(\d+)\s*(?:[–—-]\s*(\d+))?/u', $tv, $m)) { $out['minTime'] = (int)$m[1]; $out['maxTime'] = !empty($m[2]) ? (int)$m[2] : (int)$m[1]; } }
    $dv = $valAfter('/^(Náročnost|Obtížnost):?\s*/iu'); if ($dv) { $s = mb_strtolower($dv); $w = null; if (preg_match('/snadn|lehk/u', $s)) $w = 1.5; elseif (preg_match('/st[řr]edn/u', $s)) $w = 3; elseif (preg_match('/t[ěe][žz]k|n[áa]ro[čc]/u', $s)) $w = 4; if ($w) $out['weight'] = $w; }
    $rv = $valAfter('/^Rok vydání:?\s*/iu'); if ($rv) { if (preg_match('/(\d{4})/', $rv, $m)) $out['year'] = (int)$m[1]; }
    return $out;
}

/* ---------------- dohledání obálky ze Zatrolených ---------------- */
function hd_resolve_zatrolene_cover($url) {
    if (!preg_match('/-(\d+)\/?(?:[?#].*)?$/', $url, $idm)) return '';
    $id = $idm[1];
    foreach (['main_cz.large', 'main.large'] as $b) {
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $e) {
            $u = "https://www.zatrolene-hry.cz/galerie/$id/$b.$e";
            $r = wp_remote_head($u, ['timeout' => 6, 'redirection' => 2]);
            if (!is_wp_error($r) && wp_remote_retrieve_response_code($r) == 200) return $u;
        }
    }
    return '';
}

/* ---------------- modální okno importu (krok 1: vložení obsahu) ---------------- */
function hd_import_modal() {
    if (!is_user_logged_in() || !(is_front_page() || is_singular('hra'))) return;
    ?>
    <div class="hd-modal" id="hdImportModal" hidden>
      <div class="hd-modal-bg js-close-import"></div>
      <div class="hd-modal-card" role="dialog" aria-modal="true" aria-label="Import hry">
        <button type="button" class="hd-modal-x js-close-import">×</button>
        <h2>📋 Import hry</h2>
        <div class="hd-import-help">
          <strong>Postup:</strong>
          <ol>
            <li>Otevři stránku hry na Zatrolených nebo Mindoku.</li>
            <li>Označ vše (<strong>Ctrl+A</strong>) a zkopíruj (<strong>Ctrl+C</strong>).</li>
            <li>Vlož obsah dolů a dej „Načíst".</li>
          </ol>
        </div>
        <label class="hd-fld">Obsah stránky
          <textarea id="hdImportText" rows="6" placeholder="Sem vlož zkopírovaný obsah stránky (Ctrl+V)…"></textarea>
        </label>
        <label class="hd-fld">Odkaz na stránku hry <span class="hd-hint">(nepovinné — u Zatrolených doplní obálku a odkaz)</span>
          <input type="url" id="hdImportUrl" placeholder="https://…">
        </label>
        <p class="hd-hint">Rozpozná automaticky Zatrolené i Mindok. Načtené údaje pak zkontroluješ v okně „Nová hra".</p>
        <div class="hd-modal-actions">
          <button type="button" class="btn back js-close-import">Zrušit</button>
          <button type="button" class="btn js-import-parse">Načíst údaje →</button>
        </div>
      </div>
    </div>
    <?php
}
add_action('wp_footer', 'hd_import_modal');

/* ---------------- AJAX: rozparsuj vložený obsah a vrať data pro formulář ---------------- */
function hd_ajax_import_parse() {
    if (!is_user_logged_in()) wp_send_json_error(['msg' => 'Nemáš oprávnění.'], 403);
    check_ajax_referer('hd_import_parse', 'nonce');
    $text = wp_unslash($_POST['content'] ?? '');
    $url  = trim(wp_unslash($_POST['url'] ?? ''));
    if (trim($text) === '' && $url === '') wp_send_json_error(['msg' => 'Vlož obsah stránky nebo aspoň odkaz na hru.']);

    $src = hd_detect_source($text, $url);
    $data = ($src === 'mindok') ? hd_parse_mindok($text) : hd_parse_zatrolene($text, $url);
    if ($src === 'mindok' && $url) $data['pubUrl'] = $url;
    $cover = ($src !== 'mindok' && $url) ? hd_resolve_zatrolene_cover($url) : '';

    if (empty($data['name']) && empty($data['minPlayers']) && empty($data['minTime'])) {
        wp_send_json_error(['msg' => 'Z vloženého textu se nepodařilo nic rozpoznat. Zkontroluj, že jsi zkopírovala celou stránku hry.']);
    }

    wp_send_json_success([
        'name'          => $data['name'] ?? '',
        'players_min'   => $data['minPlayers'] ?? '',
        'players_max'   => $data['maxPlayers'] ?? '',
        'time_min'      => $data['minTime'] ?? '',
        'time_max'      => $data['maxTime'] ?? '',
        'difficulty'    => isset($data['weight']) ? hd_import_diff($data['weight']) : '',
        'year'          => $data['year'] ?? '',
        'publisher'     => $data['publisher'] ?? '',
        'image_url'     => $cover,
        'bgg_url'       => $data['bggUrl'] ?? '',
        'pub_url'       => $data['pubUrl'] ?? '',
        'desc_priprava' => $data['desc']['priprava'] ?? '',
        'desc_prubeh'   => $data['desc']['prubeh'] ?? '',
        'desc_konec'    => $data['desc']['konec'] ?? '',
        'source'        => $src,
    ]);
}
add_action('wp_ajax_hd_import_parse', 'hd_ajax_import_parse');
