import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

const offers = defineCollection({
  loader: glob({ pattern: '**/*.yaml', base: './src/content/offers' }),
  schema: z.object({
    name: z.string(),
    slug: z.string(),
    order: z.number(),
    description: z.string(),
    audience: z.string().optional(),
    // Offer page the home tile links to
    href: z.string(),
    // Fallback image; public/images/angebot-<slug>.(webp|svg|…) wins when present
    image: z.string(),
    imageAlt: z.string(),
    color: z.enum(['green', 'blue', 'orange', 'teal']),
  }),
});

const icon = z.enum(['users', 'clock', 'blocks', 'trophy', 'star', 'check']);

// Tiles on /kurse and the request-only offer pages; dates and prices live in the shop
const tiles = defineCollection({
  loader: glob({ pattern: '**/*.yaml', base: './src/content/tiles' }),
  schema: z
    .object({
      page: z.enum(['kurse', 'schulprojekttage', 'geburtstage', 'vorschule-hort']),
      name: z.string(),
      order: z.number(),
      bullets: z.array(z.object({ icon, text: z.string() })),
      // Optional "Inklusive: …" line
      includes: z.string().optional(),
      // Booked in the shop …
      shopUrl: z.url().optional(),
      // … or requested by e-mail with this subject
      requestSubject: z.string().optional(),
      // New photo without extension (e.g. /images/kurse/kurs-online); `image` is the fallback
      preferredImage: z.string().optional(),
      image: z.string(),
      imageAlt: z.string(),
      color: z.enum(['green', 'blue', 'orange', 'teal']),
    })
    .refine((t) => Boolean(t.shopUrl) !== Boolean(t.requestSubject), {
      message: 'A tile needs exactly one of shopUrl or requestSubject',
    }),
});

export const collections = { offers, tiles };
