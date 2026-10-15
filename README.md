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
- **Docelowy hosting (FTP)** — `.github/workflows/deploy.yml`, uruchamiany ręcznie z zakładki Actions
  (sekrety `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`).

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
- Galeria na „Przewóz zwierząt” otwiera zdjęcia bezpośrednio (bez lightboxa Elementora).
