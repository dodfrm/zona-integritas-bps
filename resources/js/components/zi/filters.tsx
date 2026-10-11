import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import type { Filters, Option, PeriodOption } from '@/types';

/**
 * Applies filters as query-string parameters so the server does the filtering & pagination.
 */
export function useFilters(baseUrl: string, filters: Filters) {
    const apply = (changes: Filters) => {
        const query = Object.fromEntries(
            Object.entries({ ...filters, ...changes }).filter(
                ([, value]) =>
                    value !== '' &&
                    value !== null &&
                    value !== undefined &&
                    value !== false,
            ),
        ) as Record<string, string | number | boolean>;

        router.get(baseUrl, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const reset = (keep: Filters = {}) =>
        router.get(
            baseUrl,
            Object.fromEntries(
                Object.entries(keep).filter(([, value]) => value != null),
            ) as Record<string, string | number | boolean>,
            { preserveState: true, replace: true },
        );

    return { apply, reset };
}

export function FilterBar({ children }: { children: ReactNode }) {
    return (
        <div
            role="search"
            className="flex flex-col gap-2 rounded-lg border bg-card p-3 sm:flex-row sm:flex-wrap sm:items-end"
        >
            {children}
        </div>
    );
}

export function FilterField({
    label,
    htmlFor,
    children,
    className = 'sm:w-48',
}: {
    label: string;
    htmlFor: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={`flex flex-col gap-1 ${className}`}>
            <label
                htmlFor={htmlFor}
                className="text-xs font-bold text-muted-foreground"
            >
                {label}
            </label>
            {children}
        </div>
    );
}

export function SearchFilter({
    value,
    onSearch,
    placeholder = 'Cari…',
    id = 'filter-search',
}: {
    value: string | undefined;
    onSearch: (value: string) => void;
    placeholder?: string;
    id?: string;
}) {
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const value = new FormData(event.currentTarget).get('search');
        onSearch(typeof value === 'string' ? value : '');
    };

    return (
        <FilterField label="Pencarian" htmlFor={id} className="sm:w-64">
            <form onSubmit={submit} className="flex gap-1">
                <Input
                    id={id}
                    name="search"
                    type="search"
                    key={value ?? ''}
                    defaultValue={value ?? ''}
                    placeholder={placeholder}
                    className="h-9"
                />
                <Button
                    type="submit"
                    variant="outline"
                    size="icon"
                    aria-label="Cari"
                >
                    <Search aria-hidden="true" />
                </Button>
            </form>
        </FilterField>
    );
}

export function SelectFilter({
    id,
    label,
    value,
    options,
    onChange,
    allLabel = 'Semua',
    className,
}: {
    id: string;
    label: string;
    value: string | number | null | undefined;
    options: Option[];
    onChange: (value: string) => void;
    allLabel?: string;
    className?: string;
}) {
    return (
        <FilterField label={label} htmlFor={id} className={className}>
            <NativeSelect
                id={id}
                value={value != null ? String(value) : ''}
                onChange={(event) => onChange(event.target.value)}
            >
                <option value="">{allLabel}</option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </NativeSelect>
        </FilterField>
    );
}

export function PeriodFilter({
    periods,
    selectedId,
    onChange,
}: {
    periods: PeriodOption[];
    selectedId: number | null | undefined;
    onChange: (id: string) => void;
}) {
    return (
        <FilterField
            label="Periode ZI"
            htmlFor="filter-period"
            className="sm:w-72"
        >
            <NativeSelect
                id="filter-period"
                value={selectedId != null ? String(selectedId) : ''}
                onChange={(event) => onChange(event.target.value)}
                disabled={periods.length === 0}
            >
                {periods.length === 0 && (
                    <option value="">Belum ada periode</option>
                )}
                {periods.map((period) => (
                    <option key={period.id} value={period.id}>
                        {period.name} — {period.organization} (
                        {period.status_label})
                    </option>
                ))}
            </NativeSelect>
        </FilterField>
    );
}

export function ResetFilters({ onReset }: { onReset: () => void }) {
    return (
        <Button type="button" variant="ghost" size="sm" onClick={onReset}>
            <X aria-hidden="true" />
            Reset filter
        </Button>
    );
}
