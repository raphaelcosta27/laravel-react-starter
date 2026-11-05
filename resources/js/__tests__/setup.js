import { cleanup } from '@testing-library/react';
import { toHaveNoViolations } from 'jest-axe';
import { afterEach, beforeEach, expect, vi } from 'vitest';
import { resetPageProps } from './helpers/inertia';

expect.extend(toHaveNoViolations);

vi.mock('@inertiajs/react', () => import('./helpers/inertia'));

globalThis.route = (name) =>
    name === undefined ? { current: () => false } : `/${name}`;

beforeEach(() => resetPageProps());
afterEach(() => cleanup());
