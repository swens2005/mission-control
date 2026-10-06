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

/** Proofmark (stories 15-18). */
export type RoundStatus = 'draft' | 'in_review' | 'superseded' | 'approved';

/** A round in the round switcher. */
export type ReviewRoundSummary = {
    id: number;
    number: number;
    label: string;
    status: RoundStatus;
    statusLabel: string;
    designCount: number;
};

export type DesignComment = {
    id: number;
    /** 1-based, per design, oldest first. */
    number: number;
    /** Hundredths of a percent of the image (0 to 10 000). */
    x: number;
    y: number;
    body: string;
    authorName: string;
    authorRole: 'studio' | 'client';
    createdAt: string | null;
    resolved: boolean;
    resolvedByName: string | null;
};

export type Design = {
    id: number;
    title: string;
    width: number;
    height: number;
    bytes: number;
    /** Human-readable, e.g. "1.5 MB". */
    size: string;
    imageUrl: string;
    comments: DesignComment[];
};

export type ReviewRound = {
    id: number;
    number: number;
    label: string;
    status: RoundStatus;
    statusLabel: string;
    sentAt: string | null;
    approvedAt: string | null;
    approvedByName: string | null;
    /** Only the round in review takes new comments. */
    canComment: boolean;
    designs: Design[];
};

/** Palette Lab (stories 20-24). */
export type ColorRole = 'text' | 'surface' | 'accent' | 'shape';

export type BrandColor = {
    id: number;
    name: string;
    role: ColorRole;
    roleLabel: string;
    hex: string;
    /** CSS notation, e.g. "oklch(27.12% 0.051 255.3)". */
    oklch: string;
    /** The OKLCH value didn't fit sRGB, so its chroma was lowered. */
    gamutAdjusted: boolean;
};

export type BrandKit = {
    id: number;
    /** Approved by the client; "Start a revision" unlocks it. */
    locked: boolean;
    colors: BrandColor[];
    matrix: ContrastMatrix;
    type: KitType;
};

export type RoleOption = { value: ColorRole; label: string; hint: string };

export type ContrastCell = {
    surfaceId: number;
    /** "4.82:1", rounded down. */
    ratio: string;
    grade: 'aaa' | 'aa' | 'aa_large' | 'fails' | 'graphics' | 'decoration';
    gradeLabel: string;
    tone: 'pass' | 'warn' | 'fail';
    /** The nearest AA shade, for text that fails. */
    fix: { hex: string; oklch: string; ratio: string } | null;
};

export type ContrastMatrix = {
    columns: { id: number; name: string; hex: string }[];
    rows: {
        id: number;
        name: string;
        hex: string;
        role: ColorRole;
        roleLabel: string;
        cells: ContrastCell[];
    }[];
    /** Text pairs below AA. */
    failing: number;
};

export type TypeStep = { name: string; px: number; rem: string };

export type KitType = {
    headingFont: string;
    headingLabel: string;
    /** CSS font-family stack. */
    headingStack: string;
    bodyFont: string;
    bodyLabel: string;
    bodyStack: string;
    baseSizePx: number;
    /** "1.25" */
    ratio: string;
    /** "1250", or "custom" */
    ratioPreset: string;
    stepsUp: number;
    stepsDown: number;
    /** Smallest to largest. */
    scale: TypeStep[];
};

export type TypeOptions = {
    fonts: { value: string; label: string }[];
    ratios: { value: string; label: string }[];
};
