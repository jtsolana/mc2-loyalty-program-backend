import { cn } from '@/lib/utils';

interface Column<T> {
    key: string;
    header: string;
    className?: string;
    render?: (row: T) => React.ReactNode;
}

interface DataTableProps<T> {
    columns: Column<T>[];
    data: T[];
    emptyMessage?: string;
    keyField?: keyof T;
}

export function DataTable<T extends Record<string, unknown>>({
    columns,
    data,
    emptyMessage = 'No records found.',
    keyField = 'id' as keyof T,
}: DataTableProps<T>) {
    return (
        <div className="overflow-hidden rounded-xl border border-border">
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-border bg-muted/50">
                            {columns.map((col) => (
                                <th
                                    key={col.key}
                                    className={cn(
                                        'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground',
                                        col.className,
                                    )}
                                >
                                    {col.header}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                        {data.length === 0 ? (
                            <tr>
                                <td colSpan={columns.length} className="px-4 py-8 text-center text-muted-foreground">
                                    {emptyMessage}
                                </td>
                            </tr>
                        ) : (
                            data.map((row, idx) => (
                                <tr key={(row[keyField] as string) ?? idx} className="bg-card transition-colors hover:bg-muted/30">
                                    {columns.map((col) => (
                                        <td key={col.key} className={cn('px-4 py-3 text-foreground', col.className)}>
                                            {col.render ? col.render(row) : (row[col.key] as React.ReactNode)}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
