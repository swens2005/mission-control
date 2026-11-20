import type { SVGProps } from 'react';

/**
 * The Mission Control rocket mark (Studio Redesign): a cream rocket with a
 * lime nose and fins, a round porthole and an amber flame. Drawn for a dark
 * tile; colors are --mark-* tokens from themes.css.
 */
export default function AppLogoIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            aria-hidden="true"
            focusable="false"
            {...props}
        >
            <path
                d="M12 2.5c3.3 2.8 4.8 6.6 4.2 11L12 17l-4.2-3.5c-.6-4.4.9-8.2 4.2-11z"
                className="fill-[var(--mark-body)] stroke-[var(--mark-ink)]"
                strokeWidth="1.2"
                strokeLinejoin="round"
            />
            <path
                d="M12 2.5c1.4 1.2 2.4 2.6 3.1 4.2H8.9c.7-1.6 1.7-3 3.1-4.2z"
                className="fill-[var(--brand-accent)]"
            />
            <circle
                cx="12"
                cy="10"
                r="1.9"
                className="fill-[var(--mark-ink)]"
            />
            <path
                d="M7.8 13.5 5 16.3l1.4 2.7 2.6-1.6M16.2 13.5l2.8 2.8-1.4 2.7-2.6-1.6"
                className="fill-[var(--brand-accent)] stroke-[var(--mark-ink)]"
                strokeWidth="1"
                strokeLinejoin="round"
            />
            <path
                d="M10.6 18.2 12 21.5l1.4-3.3"
                className="fill-[var(--mark-flame)]"
            />
        </svg>
    );
}
