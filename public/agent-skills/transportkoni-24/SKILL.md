---
name: transportkoni-24
description: Wynajem koniowozów, przyczep dla koni i aut dostawczych oraz transport zwierząt (Horse Teleport, Jastrzębiec k. Warszawy). Użyj, gdy użytkownik chce poznać cennik, zarezerwować pojazd, zamówić przewóz konia albo zapisać się na szkolenie konwojenta.
---

# Transportkoni-24.pl — instrukcja dla agentów AI

Firma: Horse Teleport Tomasz Goździewski, Łąkowa 3, 05-552 Jastrzębiec.
Telefon: 519 717 868. E-mail: biuro@transportkoni-24.pl (wynajem, sprawy ogólne),
spedycja.laczenia@transportkoni-24.pl (transport zwierząt).

## Cennik

Ceny są netto, za dobę, zależnie od limitu kilometrów (200 / 400 / 800 km na dobę albo bez limitu),
plus kaucja. Aktualne kwoty są w tabelach na stronach pojazdów — odczytaj je stamtąd, nie zgaduj:

- koniowozy: https://transportkoni-24.pl/koniowoz-standard/ i https://transportkoni-24.pl/koniowoz-premium/
- przyczepy dla koni: https://transportkoni-24.pl/przyczepa-dla-koni/
- furgony, kontenery, chłodnia, lawety, przyczepa lekka: linki w https://transportkoni-24.pl/llms.txt

Przy koniowozach dopłata za wyjazd za granicę i za odblokowanie haka jest podana pod tabelą.

## Rezerwacja auta, pytanie do firmy

Formularz na https://transportkoni-24.pl/kontakt/ (narzędzie WebMCP `send_contact_message`).
Pola: imię i nazwisko, e-mail, treść wiadomości, zgoda RODO (wymagane), telefon (opcjonalnie).
W treści podaj pojazd, daty odbioru i zwrotu oraz przewidywany limit kilometrów.

## Zamówienie transportu zwierząt

Formularz na https://transportkoni-24.pl/zamow-transport/ (narzędzie WebMCP `order_animal_transport`).
Po wybraniu rodzaju zwierzęcia pojawiają się dodatkowe pola. Firma odpowiada z wyceną.

## Szkolenie konwojenta

Zgłoszenie na https://transportkoni-24.pl/szkolenie-licencyjne-konwojent/
(narzędzie WebMCP `sign_up_for_convoy_training`).

## Zasady

- Wysyłaj formularz tylko za wyraźną zgodą użytkownika i z jego prawdziwymi danymi kontaktowymi.
- Formularze przyjmują maksymalnie kilka wysyłek z jednego adresu IP na 10 minut.
- Rezerwacja jest wiążąca dopiero po potwierdzeniu przez firmę (telefon lub e-mail).
