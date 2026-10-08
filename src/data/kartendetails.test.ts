import { describe, it, expect } from 'vitest';
import { readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { parse } from 'yaml';
import { kartendetails, isTodo } from './kartendetails';

const tilesDir = join(process.cwd(), 'src/content/tiles');
const tileIds = readdirSync(tilesDir).flatMap((page) =>
  readdirSync(join(tilesDir, page))
    .filter((file) => file.endsWith('.yaml'))
    .map((file) => `${page}/${file.replace(/\.yaml$/, '')}`),
);

describe('kartendetails', () => {
  it('has an entry for every tile and no entry without a tile', () => {
    expect(Object.keys(kartendetails).sort()).toEqual([...tileIds].sort());
  });

  it.each(tileIds)('%s has the fields the pop-up needs', (id) => {
    const d = kartendetails[id];
    expect(d.lernziele.titel.trim()).not.toBe('');
    expect(d.intro.length).toBeGreaterThan(0);
    expect(d.inhalte.length).toBeGreaterThan(0);
    expect(d.lernziele.absaetze.length).toBeGreaterThan(0);
  });

  it.each(tileIds)('%s keeps pretix facts out unless it is a request offer', (id) => {
    const tile = parse(readFileSync(join(tilesDir, `${id}.yaml`), 'utf8'));
    const d = kartendetails[id];
    if (tile.shopUrl) {
      expect(d.termine).toBeUndefined();
      expect(d.ort).toBeUndefined();
    } else {
      expect(d.termine?.trim()).toBeTruthy();
      expect(d.ort?.trim()).toBeTruthy();
    }
  });

  it.each(tileIds)('%s has an age (tile bullet or override)', (id) => {
    const tile = parse(readFileSync(join(tilesDir, `${id}.yaml`), 'utf8'));
    const hasAgeBullet = tile.bullets.some((b: { icon: string }) => b.icon === 'users');
    expect(hasAgeBullet || Boolean(kartendetails[id].alter)).toBe(true);
  });

  it('recognises placeholders', () => {
    expect(isTodo('TODO: Preis ergänzen')).toBe(true);
    expect(isTodo('35 €')).toBe(false);
    expect(isTodo(undefined)).toBe(true);
  });
});
