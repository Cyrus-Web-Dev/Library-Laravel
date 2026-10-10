import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Merge Tailwind classes intelligently (shadcn-style cn helper).
 * Usage: cn('px-4 py-2', condition && 'bg-brand', className)
 */
export function cn(...inputs) {
    return twMerge(clsx(inputs));
}
