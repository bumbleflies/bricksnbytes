import { existsSync } from 'node:fs';
import { join } from 'node:path';

// New tile photos are uploaded as angebot-<slug>.webp, either directly in
// public/images/ or in public/images/angebote/. Until one exists, keep showing
// the offer's previous image.
export function offerImage(slug: string, fallback: string): string {
  const candidates = [`/images/angebot-${slug}.webp`, `/images/angebote/angebot-${slug}.webp`];
  return candidates.find((path) => existsSync(join(process.cwd(), 'public', path))) ?? fallback;
}
