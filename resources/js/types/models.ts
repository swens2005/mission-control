export type ProjectPhase =
    | 'scope'
    | 'palette'
    | 'proofmark'
    | 'launch'
    | 'launched';

export type Option<T = string> = { value: T; label: string };

export type OrganizationOption = { id: number; name: string };

export type Organization = {
    id: number;
    name: string;
    websiteUrl: string | null;
    archived: boolean;
};

export type OrganizationRow = Organization & {
    projectsCount: number;
    contactsCount: number;
};

export type Contact = { id: number; name: string; email: string };

export type Project = {
    id: number;
    name: string;
    description: string | null;
    phase: ProjectPhase;
    phaseLabel: string;
    targetLaunchOn: string | null;
    archived: boolean;
    organization: OrganizationOption;
};

/** Laravel's length-aware paginator, as serialized to JSON. */
export type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};
