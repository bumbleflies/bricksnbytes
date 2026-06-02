import { defineConfig } from 'astro/config';
import yaml from 'yaml';

// YAML Loader Plugin - allows importing .yaml files as JavaScript objects
const yamlLoaderPlugin = {
  name: 'yaml-loader',
  enforce: 'pre',
  async resolveId(id) {
    if (id.endsWith('.yaml') || id.endsWith('.yml')) {
      return id;
    }
  },
  async load(id) {
    if (id.endsWith('.yaml') || id.endsWith('.yml')) {
      const fs = await import('fs');
      const path = await import('path');
      const resolvedPath = path.resolve(id);
      const content = fs.readFileSync(resolvedPath, 'utf-8');
      const data = yaml.parse(content);
      return `export default ${JSON.stringify(data)}`;
    }
  }
};

export default defineConfig({
  output: 'static',
  outDir: 'dist',
  vite: {
    plugins: [yamlLoaderPlugin],
    ssr: {
      external: []
    }
  }
});
