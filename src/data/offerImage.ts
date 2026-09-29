import { existsSync } from 'node:fs';
import { join } from 'node:path';

// New tile photos are uploaded as public/images/angebote/angebot-<slug>.webp.
// Until a file exists, keep showing the offer's previous image.
export function offerImage(slug: string, fallback: string): string {
  const path = `/images/angebote/angebot-${slug}.webp`;
  return existsSync(join(process.cwd(), 'public', path)) ? path : fallback;
}
