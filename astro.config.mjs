import { defineConfig } from 'astro/config';

export default defineConfig({
  output: 'static',
  outDir: 'dist',
  vite: {
    ssr: {
      external: []
    }
  }
});
