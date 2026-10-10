import type { ReactNode } from 'react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

export type Column<T> = {
    key: string;
    header: ReactNode;
    cell: (row: T) => ReactNode;
    className?: string;
};

export function DataTable<T extends { id: number | string }>({
    columns,
    rows,
    caption,
    empty,
    rowClassName,
}: {
    columns: Column<T>[];
    rows: T[];
    caption: string;
    empty?: ReactNode;
    rowClassName?: (row: T) => string | undefined;
}) {
    if (rows.length === 0 && empty) {
        return <>{empty}</>;
    }

    return (
        <div className="overflow-hidden rounded-lg border bg-card">
            <Table>
                <caption className="sr-only">{caption}</caption>
                <TableHeader>
                    <TableRow className="hover:bg-transparent">
                        {columns.map((column) => (
                            <TableHead
                                key={column.key}
                                className={column.className}
                            >
                                {column.header}
                            </TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {rows.map((row) => (
                        <TableRow key={row.id} className={rowClassName?.(row)}>
                            {columns.map((column) => (
                                <TableCell
                                    key={column.key}
                                    className={cn(column.className)}
                                >
                                    {column.cell(row)}
                                </TableCell>
                            ))}
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
