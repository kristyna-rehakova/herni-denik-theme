# Herní deník — WordPress šablona

Skupinový deník deskových her jako **nativní WordPress šablona**. Sbírka her (Herna), odehrané partie (Deník) a hráči jsou vlastní typy obsahu; přihlášení, účty a role řeší přímo WordPress. Nasazuje se z GitHubu přes **GitHub Updater** nebo **WP Pusher**.

> ⚠️ **Fáze 1 (základ).** Správa dat zatím probíhá v administraci WordPressu (wp-admin). Veřejné stránky (Herna, detail hry, Deník) jsou hotové. Formuláře pro přidávání přímo z webu a import z Mindok/Zatrolené přijdou v další fázi.

---

## Co je potřeba
- Vlastní **self-hosted WordPress** (WordPress.com Business/vyšší, nebo běžný hosting s WordpressEm) — verze 6.0+.
- Přístup do administrace jako správce.

## Instalace přes GitHub

### 1) Nahraj šablonu na GitHub
1. Vytvoř si na GitHubu nový **veřejný** repozitář, např. `herni-denik-theme`.
2. Nahraj do něj obsah této složky (soubory `style.css`, `functions.php`, složku `inc/` atd.) tak, aby `style.css` byl v kořeni repozitáře.
3. V souboru **`style.css`** nahoře přepiš tři řádky `VASE-JMENO` na svoje uživatelské jméno na GitHubu:
   ```
   Theme URI: https://github.com/TVUJ-UCET/herni-denik-theme
   GitHub Theme URI: https://github.com/TVUJ-UCET/herni-denik-theme
   Update URI: https://github.com/TVUJ-UCET/herni-denik-theme
   ```
4. (Doporučeno) Vytvoř na GitHubu **Release / tag** `v0.1.0` — updater podle tagů pozná novou verzi.

### 2) Nainstaluj do WordPressu
**Varianta A — WP Pusher**
1. Nainstaluj plugin **WP Pusher** (wppusher.com) → aktivuj.
2. WP Pusher → *Install Theme* → zadej `TVUJ-UCET/herni-denik-theme` → *Install Theme*.
3. Vzhled → Šablony → aktivuj **Herní deník**.

**Varianta B — GitHub Updater**
1. Nainstaluj plugin **Git Updater** (git-updater.com).
2. Přidej repozitář, pak Vzhled → Šablony → aktivuj **Herní deník**.

**Varianta C — bez pluginu (ruční)**
1. Na GitHubu *Code → Download ZIP* (nebo ZIP z Release).
2. WordPress: Vzhled → Šablony → *Přidat novou → Nahrát šablonu* → vyber ZIP → aktivuj.
   (Aktualizace pak musíš nahrávat ručně; s pluginem se tahají automaticky.)

### 3) Nastavení po aktivaci
1. **Trvalé odkazy:** Nastavení → Trvalé odkazy → zvol *Název příspěvku* → Uložit (kvůli hezkým URL `/hra/...`, `/partie/`). Stačí jednou uložit.
2. **Domovská stránka = Herna:** funguje automaticky (`front-page.php`). Není potřeba zakládat žádnou stránku.
3. **Menu (nepovinné):** Vzhled → Menu → vytvoř menu a přiřaď ho k pozici *Hlavní menu*. Bez menu se v hlavičce zobrazí výchozí odkazy Herna / Deník / Hráči.

## Jak se to používá (Fáze 1)
V administraci přibydou tři položky:
- **🎲 Hry (Herna)** — přidej hru: název, obálku dej jako *Náhledový obrázek*, vyplň počty hráčů, délku, obtížnost, vydavatele, odkazy, video a popis (Příprava / Průběh / Konec / Bodování).
- **📖 Deník** — *Zapsat do Deníku*: vyber hru, datum, kdo hrál, kdo vyhrál 🏆 a poznámku. (Název záznamu se doplní sám.)
- **👥 Hráči** — profily hráčů: přezdívka, barva ikonky, emoji a případné propojení s WordPress účtem.

## Přístup a role
- Ve výchozím stavu je celý web **jen pro přihlášené** (nepřihlášený návštěvník je poslán na přihlášení).
- Chceš-li Herní deník **veřejně** (bez přihlášení), přidej do `wp-config.php`:
  ```php
  define('HD_PUBLIC', true);
  ```
- Kdo smí přidávat/upravovat obsah, se řídí běžnými **rolemi WordPressu** (Redaktor/Autor přidávat mohou, Předplatitel ne). Kamarády pozvi přes *Uživatelé → Přidat nového*.

## Struktura šablony
```
style.css              hlavička šablony + veškeré styly (zelený motiv)
functions.php          nastavení, načtení stylů, gate přihlášení, includy
inc/cpt.php            vlastní typy: hra / partie / hrac
inc/meta.php           editační pole v adminu + ukládání
inc/helpers.php        pomocné funkce pro zobrazení
header.php / footer.php hlavička s navigací + patička
front-page.php         Herna (mřížka her)
single-hra.php         detail hry
archive-partie.php     Deník (partie po dnech)
template-parts/game-card.php  karta hry
index.php              obecný fallback
```

## Plán dalších fází
- **Fáze 2:** přidávání a úprava her/partií přímo z webu (front-end formuláře) bez wp-adminu.
- **Fáze 3:** import údajů vložením obsahu stránky (Zatrolené / Mindok), jako v původní HTML appce.
- **Fáze 4:** rozšíření, nahraná pravidla (PDF/obrázky), fotky z partií, statistiky, osobní profily.
