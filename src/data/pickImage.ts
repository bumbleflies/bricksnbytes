import { existsSync } from 'node:fs';
import { join } from 'node:path';

// Returns the first of `candidates` (paths under public/) that exists, else `fallback`.
// Lets new photos be uploaded later without touching code or data.
export function pickImage(candidates: string[], fallback: string): string {
  return candidates.find((path) => existsSync(join(process.cwd(), 'public', path))) ?? fallback;
}

// Same base name in any of the usual web formats
export function withFormats(basePath: string): string[] {
  return ['webp', 'svg', 'png', 'jpg'].map((ext) => `${basePath}.${ext}`);
}
