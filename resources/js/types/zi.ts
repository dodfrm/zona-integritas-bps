export type Option = {
    value: string;
    label: string;
};

/** A backed enum value with its Indonesian label (App\Support\Present::enum). */
export type EnumValue = {
    value: string;
    label: string;
};

export type UserBrief = {
    id: number;
    name: string;
};

export type OrganizationBrief = {
    id: number;
    name: string;
    code: string | null;
    type: string;
    type_label: string;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/** Laravel LengthAwarePaginator serialized by Inertia. */
export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

export type PeriodOption = {
    id: number;
    name: string;
    year: number;
    status: string;
    status_label: string;
    organization: string;
};

export type AreaOption = Option & {
    indicators: (Option & { is_sub: boolean })[];
};

export type Assignment = {
    id: number;
    type: EnumValue;
    status: EnumValue;
    assignee: UserBrief | null;
    assigner: UserBrief | null;
    due_date: string | null;
    notes: string | null;
    created_at: string | null;
    completed_at: string | null;
};

export type TaskAssignment = {
    id: number;
    type: EnumValue;
    status: EnumValue;
    target: string;
    url: string | null;
    assigner: UserBrief | null;
    due_date: string | null;
    is_overdue: boolean;
    notes: string | null;
    created_at: string | null;
};

export type Filters = Record<
    string,
    string | number | boolean | null | undefined
>;
