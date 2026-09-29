import { defineConfig } from 'astro/config';
import yaml from 'yaml';
import { fileURLToPath } from 'url';
import { dirname } from 'path';

const __dirname = dirname(fileURLToPath(import.meta.url));

// YAML Loader Plugin - allows importing .yaml files as JavaScript objects
const yamlLoaderPlugin = {
  name: 'yaml-loader',
  enforce: 'pre',
  async resolveId(id, importer) {
    if (id.endsWith('.yaml') || id.endsWith('.yml')) {
      // Handle relative imports from pages/layouts
      if (importer && (id.startsWith('.') || id.startsWith('..'))) {
        const path = await import('path');
        const resolvedPath = path.resolve(path.dirname(importer), id);
        return resolvedPath;
      }
      return id;
    }
  },
  async load(id) {
    if (id.endsWith('.yaml') || id.endsWith('.yml')) {
      const fs = await import('fs');
      const path = await import('path');
      // Handle both absolute and relative paths
      const resolvedPath = path.isAbsolute(id) ? id : path.resolve(__dirname, id);
      const content = fs.readFileSync(resolvedPath, 'utf-8');
      const data = yaml.parse(content);
      return `export default ${JSON.stringify(data)}`;
    }
  }
};

// Old URLs -> current pages. nginx answers these with a 301 in production (nginx.conf);
// these static redirect pages cover dev, preview and any other static host.
const legacyProgramSlugs = ['eltern-kind', 'lego-spike', 'minecraft-edu', 'python-basics', 'robotik-advanced', 'scratch-online'];
const legacyRedirects = Object.fromEntries([
  ['/programs', '/kurse/'],
  ...legacyProgramSlugs.map((slug) => [`/programs/${slug}`, '/kurse/']),
  ['/privacy-policy', '/datenschutz/'],
  ['/angebote', '/'],
  ['/angebote/programmierkurse', '/kurse/'],
  ['/angebote/schulprojekttage', '/schulprojekttage/'],
  ['/angebote/geburtstage', '/geburtstage/'],
  ['/angebote/vorschule-hort', '/vorschule-hort/'],
]);

export default defineConfig({
  output: 'static',
  redirects: legacyRedirects,
  outDir: 'dist',
  vite: {
    plugins: [yamlLoaderPlugin],
    // `npm run dev`: forward the contact form to a local mailer (cd mailer && npm run dev)
    server: {
      proxy: { '/api/contact': 'http://127.0.0.1:3001' },
    },
    ssr: {
      external: []
    }
  }
});
