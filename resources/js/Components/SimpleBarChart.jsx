/**
 * Simple horizontal bar chart for admin analytics.
 * data = [{ [labelKey]: string, [valueKey]: number }]. Bars scale to max value.
 *
 * @param {Array<Object>} data
 * @param {string} labelKey - Key for label (e.g. 'month', 'week')
 * @param {string} valueKey - Key for value (e.g. 'value', 'count', 'used')
 */
export default function SimpleBarChart({ data = [], labelKey = 'label', valueKey = 'value', className = '' }) {
    const values = data.map((d) => Number(d[valueKey]) || 0);
    const max = Math.max(...values, 1);

    return (
        <div className={`space-y-2 ${className}`}>
            {data.map((d, i) => (
                <div key={i} className="flex items-center gap-2">
                    <span className="w-24 shrink-0 truncate text-xs text-stone-600">{d[labelKey]}</span>
                    <div className="min-w-0 flex-1">
                        <div
                            className="h-6 rounded bg-amber-200/80 transition-all duration-500"
                            style={{ width: `${(Number(d[valueKey]) || 0) / max * 100}%`, minWidth: (Number(d[valueKey]) || 0) > 0 ? '4px' : 0 }}
                        />
                    </div>
                    <span className="w-12 shrink-0 text-right text-xs font-medium text-stone-700">{d[valueKey]}</span>
                </div>
            ))}
        </div>
    );
}
