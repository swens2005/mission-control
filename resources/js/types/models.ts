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

/** A project as the client sees it in Launchpad. */
export type ClientProject = {
    id: number;
    name: string;
    description: string | null;
    phase: ProjectPhase;
    phaseLabel: string;
    /** 1-based position in the four phases; 5 means launched. */
    step: number;
    targetLaunchOn: string | null;
};

/** Something a module needs the client to do (WaitingOnClient). */
export type WaitingItem = {
    title: string;
    module: string;
    projectId: number;
    projectName: string;
    url: string;
    dueOn: string | null;
};

/** One line of the shared activity log. */
export type ActivityItem = {
    id: number;
    actor: string;
    description: string;
    createdAt: string;
    project: { id: number; name: string } | null;
};

/** One manual pre-flight item on a launch checklist (Launch Control). */
export type ChecklistItem = {
    id: number;
    label: string;
    hint: string | null;
    owner: 'studio' | 'client';
    ownerLabel: string;
    checked: boolean;
    checkedAt: string | null;
    checkedBy: string | null;
    /** What the UI offers; the server checks again. */
    canToggle: boolean;
};

export type Launch = {
    id: number;
    url: string;
    checklist: ChecklistItem[];
    checks: LaunchChecks;
    board: GoBoardState;
};

export type CheckStatus = 'pass' | 'warn' | 'fail' | 'skipped';

/** One automated check's outcome in the latest run. */
export type CheckResultItem = {
    key: string;
    label: string;
    status: CheckStatus;
    statusLabel: string;
    message: string;
    details: string[];
    waiver: { reason: string; by: string; at: string | null } | null;
};

export type LaunchChecks = {
    latestRun: {
        id: number;
        ranAt: string;
        ranBy: string;
        url: string;
        summary: string;
        durationMs: number;
    } | null;
    previousRunAt: string | null;
    results: CheckResultItem[];
};

/** The go/no-go board (Launch Control, story 12). */
export type GoBoardState = {
    rows: { key: string; label: string; ok: boolean; detail: string }[];
    clear: boolean;
    go: boolean;
    status: 'GO' | 'NO-GO' | 'CLEAR' | 'LAUNCHED';
    headline: string;
    launched: boolean;
    canSign: boolean;
    signAs: string;
    expectedName: string;
    canMarkLaunched: boolean;
};
