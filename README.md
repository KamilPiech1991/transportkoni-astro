# transportkoni-24.pl — Astro

Strona transportkoni-24.pl (wcześniej WordPress + Astra + Elementor) przepisana 1:1 na statyczne Astro.
Wygląd odtworzony z oryginalnego HTML i oryginalnych arkuszy CSS motywu, porównany zrzutami
(desktop 1366 px, tablet 800 px, telefon 390 px) z kopią starej strony — wszystkie 16 podstron
pokrywają się co do piksela (wyjątki opisane niżej).

## Start

```bash
npm install
npm run dev      # podgląd: http://localhost:4321
npm run build    # gotowa strona statyczna w dist/
```

## Publikacja

- **GitHub Pages (podgląd)** — `.github/workflows/pages.yml` buduje i publikuje stronę po każdym pushu na `main`
  pod adresem https://kamilpiech1991.github.io/transportkoni-astro/.
  Jednorazowo trzeba włączyć: *Settings → Pages → Build and deployment → Source: GitHub Actions*.
  Podgląd ma `noindex`, żeby nie konkurował w Google z właściwą domeną.
- **Docelowy hosting (FTP)** — `.github/workflows/deploy.yml`, uruchamiany ręcznie z zakładki Actions.
  Szczegóły niżej w „Wdrożenie na transportkoni-24.pl”.

Adres bazowy ustawia zmienna `BASE_PATH` (domyślnie `/`). Integracja w `astro.config.mjs` dopisuje wtedy
prefiks do wszystkich linków `/...` w wygenerowanym HTML, więc w kodzie zawsze piszemy zwykłe `/kontakt/`.

## Struktura

- `src/layouts/Layout.astro` – `<head>` (meta/SEO/JSON-LD z oryginału, lista arkuszy CSS), skrypty
- `src/components/` – `Header`, `Footer`, `FacebookBox` (wysuwany boks FB), `ServiceIcon`
- `src/data/site.ts` – telefon, adres, menu
- `src/pages/*.astro` – podstrony; treść to oryginalny markup Elementora (klasy muszą zostać, bo od nich
  zależą style). Tekst, ceny w tabelach i obrazki edytuje się bezpośrednio w tych plikach.
  Na górze każdej strony jest obiekt `page` z tytułem, meta i listą arkuszy CSS (kolejność ma znaczenie).
- `public/wp-content/` – oryginalne pliki CSS, fonty, obrazy i skrypt menu motywu Astra
  (te same ścieżki co w WordPressie, więc stare adresy obrazków dalej działają)
- `public/wp-inline/` – style, które WordPress wstawiał inline w `<head>` (m.in. ustawienia motywu Astra)
- `public/form-handler.php` + `public/form.js` – obsługa formularzy (kontakt, szkolenie, zamów transport)

## Formularze

Działają na hostingu z PHP (`mail()`); adresy odbiorców ustawisz na górze `public/form-handler.php`.
Na GitHub Pages PHP nie działa — tam wysyłka zwróci komunikat o błędzie. Bez PHP: podmień `action`
formularzy na Formspree / Web3Forms itp.

## Wdrożenie na transportkoni-24.pl

1. **Sekrety** — *Settings → Secrets and variables → Actions → New repository secret*:
   `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` (dane z panelu hostingu).
   Opcjonalnie w zakładce *Variables*: `FTP_DIR` (katalog strony, domyślnie `public_html/`),
   `FTP_PROTOCOL` (`ftps` domyślnie, `ftp` gdy serwer nie obsługuje szyfrowania),
   `FTP_SECURITY` (`loose` domyślnie — sprawdzone na tym hostingu; `strict`, gdy certyfikat FTP pasuje do adresu serwera).
2. **Próba** — *Actions → Deploy transportkoni-24.pl (FTP) → Run workflow*, tryb `proba`.
   Robi kopię całej obecnej strony i symuluje wgranie; na serwerze nic się nie zmienia.
   W logu kroku „Przygotuj lftp” sprawdź, czy wylistowany katalog to faktycznie WordPress
   (`wp-config.php`, `wp-content/`…). Jeśli nie — popraw `FTP_DIR`.
3. **Wdrożenie** — tryb `wdrozenie` + zaznaczone „Przenieś pliki starego WordPressa”.
   Kolejno: nowa kopia → pliki WordPressa do `_stary_wp/` (niedostępne z sieci) → wgranie strony →
   automatyczny test (strony 200, `wp-login.php`/`xmlrpc.php` 404, formularz 405 na GET).
4. **Kolejne aktualizacje** — tryb `bez-kopii` (wgrywa tylko zmienione pliki).
   Można też dopisać w `deploy.yml` wyzwalacz `push: branches: [main]`.

**Kopia zapasowa** jest w zakładce Actions w danym uruchomieniu (artefakt `kopia-strony-N`, 90 dni).
Jest zaszyfrowana hasłem FTP, bo zawiera `wp-config.php`:
`gpg -d kopia-strony.tar.gz.gpg | tar xz`. Baza danych WordPressa nie jest ruszana — jeśli chcesz
mieć też jej kopię, zrób eksport w panelu hostingu (phpMyAdmin).

**Powrót do WordPressa** (awaryjnie): przez FTP / menedżer plików przenieś zawartość `_stary_wp/`
z powrotem do katalogu strony (nadpisując `.htaccess`) i usuń `index.html`.

**Po wdrożeniu**: wyślij testową wiadomość z formularza Kontakt (czy dochodzi na biuro@…), a po
kilku tygodniach bez problemów usuń `_stary_wp/` i bazę WordPressa.

## Bezpieczeństwo

- Strona jest w pełni statyczna (brak bazy danych i panelu logowania). Jedyny kod po stronie serwera to `public/form-handler.php`.
- **CSP** w `<meta>` (`src/layouts/Layout.astro`): skrypty tylko z własnej domeny i `connect.facebook.net`, ramki tylko Facebook / Google Maps. Dlatego wszystkie skrypty są w plikach (`public/js/`, `public/form.js`), nie inline — nowe skrypty dodawaj tak samo.
- **Nagłówki serwera** (`public/.htaccess`, Apache): HTTPS + HSTS, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, brak listingu katalogów, blokada plików ukrytych/kopii zapasowych oraz pozostałości po WordPressie (`wp-admin`, `wp-login.php`, `xmlrpc.php`, PHP w `wp-content`).
- **Formularze** (`form-handler.php`): tylko POST z własnej domeny (Origin/Referer), lista dozwolonych formularzy, honeypot, limit rozmiaru i liczby pól, maks. 5 wysyłek z jednego IP na 10 minut, walidacja e-maila w `Reply-To` (brak wstrzykiwania nagłówków).
- Zależności: `npm audit` — 0 podatności (Astro 7). Sprawdzaj okresowo `npm audit`.
- **Przy wdrożeniu na serwer usuń stary WordPress z `public_html`** (albo przenieś poza katalog publiczny) — `.htaccess` blokuje typowe ścieżki, ale nieaktualne wtyczki PHP na serwerze to główne ryzyko.
- Boks Facebooka i mapa Google ładują treści (i ciasteczka) firm trzecich — tak samo jak na starej stronie; jeśli potrzebny jest baner zgód RODO, trzeba go dodać.

## Adresy URL

Zachowane jak w WordPressie (z ukośnikiem na końcu). `/witaj-swiecie/` i `/category/bez-kategorii/`
przekierowują na stronę główną.

## Różnice względem oryginału (celowe)

- **Ikony „Usługi w Pakiecie”** — oryginalne były hotlinkowane z file.express.pl i ten serwer już ich nie
  zwraca (na starej stronie widać puste obrazki z napisem „express rent a car”). Zastąpione ikonami SVG
  (`src/components/ServiceIcon.astro`).
- **Formularz „Zamów transport”** — na starej stronie wtyczka od sekcji warunkowych nie działała i
  wyświetlały się surowe znaczniki `[group group-konie]` oraz wszystkie sekcje naraz. Teraz szczegóły
  pokazują się dopiero po zaznaczeniu gatunku zwierzęcia.
- Stopka bez dopisku „Powered by Astra WordPress Theme” (strona nie jest już na WordPressie).
- Brak reCAPTCHA — zamiast niego ukryte pole antyspamowe (honeypot).
- Galeria na „Przewóz zwierząt” ma własny lightbox (`src/components/Lightbox.astro`) zamiast skryptu Elementora.
