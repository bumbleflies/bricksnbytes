import { defineConfig } from 'astro/config';
import yaml from 'yaml';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import { readdirSync, readFileSync } from 'fs';

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

// Old /programs pages now live on /kurse (nginx answers these with a 301 in production;
// these static redirect pages cover dev, preview and any other static host)
const programSlugs = readdirSync(join(__dirname, 'src/content/programs'))
  .filter((f) => f.endsWith('.yaml'))
  .map((f) => yaml.parse(readFileSync(join(__dirname, 'src/content/programs', f), 'utf-8')).slug);
const programRedirects = Object.fromEntries([
  ['/programs', '/kurse'],
  ['/privacy-policy', '/datenschutz'],
  ...programSlugs.map((slug) => [`/programs/${slug}`, '/kurse']),
]);

export default defineConfig({
  output: 'static',
  redirects: programRedirects,
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
