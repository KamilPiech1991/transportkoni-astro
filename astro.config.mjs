import { defineConfig } from 'astro/config';
import { readdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

// Docelowo strona działa w katalogu głównym domeny (transportkoni-24.pl).
// Podgląd na GitHub Pages działa pod /<repo>/ — wtedy workflow ustawia BASE_PATH,
// a poniższa integracja dopisuje prefiks do wszystkich adresów "/..." w HTML.
const base = process.env.BASE_PATH || '/';
const site = process.env.SITE_URL || 'https://transportkoni-24.pl';

function prefixBase() {
  return {
    name: 'prefix-base',
    hooks: {
      'astro:build:done': async ({ dir }) => {
        if (base === '/') return;
        const prefix = base.replace(/\/$/, '');
        const fix = (u) => (u.startsWith('/') && !u.startsWith('//') && !u.startsWith(prefix + '/') ? prefix + u : u);
        const walk = async (d) => {
          for (const e of await readdir(d, { withFileTypes: true })) {
            const p = join(d, e.name);
            if (e.isDirectory()) await walk(p);
            else if (e.name.endsWith('.html')) {
              let html = await readFile(p, 'utf8');
              html = html
                .replace(/\b(href|src|action)="([^"]*)"/g, (m, a, u) => `${a}="${fix(u)}"`)
                .replace(/\bsrcset="([^"]*)"/g, (m, v) => `srcset="${v.split(',').map((s) => s.trim().replace(/^\S+/, fix)).join(', ')}"`);
              await writeFile(p, html);
            }
          }
        };
        await walk(fileURLToPath(dir));
      },
    },
  };
}

export default defineConfig({
  site,
  base,
  // WordPress używał adresów z ukośnikiem na końcu — zachowujemy je dla SEO
  trailingSlash: 'always',
  build: { format: 'directory' },
  // odstępy między elementami inline (np. przyciski w nagłówku) muszą zostać jak w oryginale
  compressHTML: false,
  redirects: {
    // stare, puste strony WordPressa
    '/witaj-swiecie': '/',
    '/category/bez-kategorii': '/',
  },
  integrations: [prefixBase()],
});
