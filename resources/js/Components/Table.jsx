/**
 * Reusable table component. Same theme and Tailwind styling as the app.
 *
 * @param {Array<{ key: string, label: string, render?: (row) => ReactNode }>} columns
 * @param {Array<Object>} data
 * @param {string} emptyMessage
 */
export default function Table({ columns = [], data = [], emptyMessage = 'No data.' }) {
    return (
        <div className="overflow-hidden rounded-xl border border-amber-200/40 bg-white shadow-sm">
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-amber-200/50">
                    <thead className="bg-amber-50/80">
                        <tr>
                            {columns.map((col) => (
                                <th
                                    key={col.key}
                                    scope="col"
                                    className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-amber-800"
                                >
                                    {col.label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-amber-200/30 bg-white">
                        {data.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={columns.length}
                                    className="px-4 py-8 text-center text-sm text-stone-500"
                                >
                                    {emptyMessage}
                                </td>
                            </tr>
                        ) : (
                            data.map((row, idx) => (
                                <tr key={row.id ?? idx} className="transition-colors hover:bg-amber-50/50">
                                    {columns.map((col) => (
                                        <td
                                            key={col.key}
                                            className="whitespace-nowrap px-4 py-3 text-sm text-stone-700"
                                        >
                                            {col.render ? col.render(row) : row[col.key]}
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
