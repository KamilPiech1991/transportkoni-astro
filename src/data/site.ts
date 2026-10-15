export const site = {
  name: 'Transportkoni-24.pl',
  url: 'https://transportkoni-24.pl',
  phone: '519 717 868',
  address: 'Łąkowa 3, 05-552 Jastrzębiec',
  facebook:
    'https://www.facebook.com/Horse-Teleport-Transport-Koni-Wypożyczalnia-Aut-108039973992544/',
};

// Menu główne (nagłówek i stopka). `id` = numer pozycji menu z WordPressa (klasy CSS motywu).
export const mainMenu = [
  { label: 'Strona Główna', href: '/', id: 22, pageId: 18, home: true },
  { label: 'Transport zwierząt', href: '/transport-zwierzat/', id: 27, pageId: 25 },
  { label: 'Rehabilitacja koni', href: '/rehabilitacja-koni-2/', id: 668, pageId: 663 },
  { label: 'Przewóz zwierząt', href: '/przewoz-zwierzat/', id: 650, pageId: 636 },
  { label: 'Kontakt', href: '/kontakt/', id: 23, pageId: 19 },
];

export const headerButtons = [
  { label: 'Szkolenie licencyjne', href: '/szkolenie-licencyjne-konwojent/', style: 'color: white' },
  { label: 'Zarezerwuj auto', href: '/kontakt/', style: 'color: white;position: center' },
];

/** Klasy <li> tak jak generował je WordPress (potrzebne dla stylów Astry). */
export function menuItemClass(item: (typeof mainMenu)[number], current: boolean) {
  return [
    'menu-item menu-item-type-post_type menu-item-object-page',
    item.home && 'menu-item-home',
    current && `current-menu-item page_item page-item-${item.pageId} current_page_item`,
    `menu-item-${item.id}`,
  ]
    .filter(Boolean)
    .join(' ');
}

export function normalizePath(pathname: string) {
  const base = import.meta.env.BASE_URL.replace(/\/$/, '');
  let p = pathname.startsWith(base) ? pathname.slice(base.length) : pathname;
  if (!p.endsWith('/')) p += '/';
  return p || '/';
}
