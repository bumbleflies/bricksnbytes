import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

const programs = defineCollection({
  loader: glob({ pattern: '**/*.yaml', base: './src/content/programs' }),
  schema: z.object({
    name: z.string(),
    slug: z.string(),
    description: z.string(),
    longDescription: z.string(),
    ageGroup: z.string(),
    ageGroupDe: z.string(),
    duration: z.string(),
    durationDe: z.string(),
    price: z.number(),
    location: z.string(),
    locationDe: z.string(),
    instructor: z.string(),
    image: z.string(),
    featured: z.boolean().default(false),
    whatYouLearn: z.array(z.string()).optional(),
    requirements: z.array(z.string()).optional(),
    whatIncluded: z.array(z.string()).optional(),
  }),
});

const offers = defineCollection({
  loader: glob({ pattern: '**/*.yaml', base: './src/content/offers' }),
  schema: z.object({
    name: z.string(),
    slug: z.string(),
    order: z.number(),
    description: z.string(),
    audience: z.string().optional(),
    // Fallback image; public/images/angebote/angebot-<slug>.webp wins when present
    image: z.string(),
    imageAlt: z.string(),
    color: z.enum(['green', 'blue', 'orange', 'teal']),
    // Slugs of existing program pages shown as course tiles
    programs: z.array(z.string()).default([]),
    // Titles of "Beschreibung folgt" tiles until real courses exist
    placeholders: z.array(z.string()).default([]),
  }),
});

// Course tiles on /kurse; dates and prices live in the shop, not here
const courses = defineCollection({
  loader: glob({ pattern: '**/*.yaml', base: './src/content/courses' }),
  schema: z.object({
    name: z.string(),
    order: z.number(),
    age: z.string().optional(),
    description: z.string(),
    shopUrl: z.url(),
    image: z.string(),
    imageAlt: z.string(),
    color: z.enum(['green', 'blue', 'orange', 'teal']),
  }),
});

export const collections = { programs, offers, courses };
