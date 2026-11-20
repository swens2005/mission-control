import type { SVGProps } from 'react';
import { cn } from '@/lib/utils';

/**
 * Mission Control's own navigation icons (story 47), copied from the
 * approved mock-up: 24 px line icons, 1.75 strokes with round ends, one
 * lime dot each. The dot takes `--icon-dot` (brand accent unless a parent
 * sets it, e.g. ink on a lime button). Decorative: links keep their text.
 * Lucide stays for chevrons, close buttons and other small controls.
 */
type IconProps = SVGProps<SVGSVGElement>;

function Icon({ className, children, ...props }: IconProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.75}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
            className={cn('mc-icon size-6 shrink-0', className)}
            {...props}
        >
            {children}
        </svg>
    );
}

function Dot({ cx, cy, r = 1.8 }: { cx: number; cy: number; r?: number }) {
    return (
        <circle className="mc-icon-dot" cx={cx} cy={cy} r={r} stroke="none" />
    );
}

export function FlightDeckIcon(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="M4 18a8 8 0 1 1 16 0" />
            <path d="M12 18l4-6" />
            <Dot cx={12} cy={18} />
            <path
                d="M6.5 13.5l1 .6M12 8v1.2M17.5 13.5l-1 .6"
                strokeWidth={1.5}
            />
        </Icon>
    );
}

export function ClientsIcon(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="M4 20V8l8-4 8 4v12" />
            <path d="M9 20v-5h6v5M3 20h18" />
            <Dot cx={12} cy={10} />
        </Icon>
    );
}

export function ProjectsIcon(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="M12 3c3 2.5 4.5 6 4 10l-4 3-4-3c-.5-4 1-7.5 4-10z" />
            <path
                d="M8.3 13.5 5.5 16l1.2 3 2.3-1.5M15.7 13.5l2.8 2.5-1.2 3-2.3-1.5"
                strokeWidth={1.6}
            />
            <Dot cx={12} cy={9.5} />
        </Icon>
    );
}

export function LibraryIcon(props: IconProps) {
    return (
        <Icon {...props}>
            <rect x={4} y={4} width={16} height={16} rx={2.5} />
            <path d="M8 9h8M8 13h8M8 17h5" />
            <Dot cx={17} cy={17} />
        </Icon>
    );
}

export function ActivityIcon(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="M3 12h4l2.5-6 4 12 2.5-6H21" />
            <Dot cx={21} cy={12} />
        </Icon>
    );
}

export function LaunchIcon(props: IconProps) {
    return (
        <Icon {...props}>
            <path d="M7 21h10M12 21v-4" />
            <path d="M12 3c2.2 2 3 4.8 2.6 8.5L12 14l-2.6-2.5C9 7.8 9.8 5 12 3z" />
            <path
                d="M5 9.5a7 7 0 0 1 2-4M19 9.5a7 7 0 0 0-2-4"
                strokeWidth={1.5}
            />
            <Dot cx={12} cy={8.3} r={1.6} />
        </Icon>
    );
}
