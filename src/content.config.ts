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

const ageGroups = defineCollection({
  loader: glob({ pattern: '**/*.yaml', base: './src/content/age-groups' }),
  schema: z.object({
    name: z.string(),
    ageRange: z.string(),
    description: z.string(),
    image: z.string(),
    color: z.enum(['green', 'blue', 'orange', 'teal']),
    href: z.string(),
  }),
});

export const collections = { programs, ageGroups };
