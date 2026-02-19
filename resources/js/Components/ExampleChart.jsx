/**
 * Example chart implementation – production-ready pattern for Recharts.
 *
 * Use this as reference when adding new admin/analytics charts:
 * 1. Import from 'recharts': AreaChart/BarChart, Area/Bar, XAxis, YAxis, Tooltip, CartesianGrid, ResponsiveContainer.
 * 2. Data shape: array of objects with consistent keys (e.g. period, total or label, value).
 * 3. Use ResponsiveContainer with percentage width/height so layout is responsive.
 * 4. Provide empty/loading states so the UI never breaks.
 * 5. Use theme colors (e.g. amber) and accessible contrast.
 *
 * Props:
 *   - data: Array<{ period: string, total: number }>
 *   - title: string (optional)
 *   - emptyMessage: string (optional)
 *   - height: number (default 280)
 */
import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

const DEFAULT_DATA = [
    { period: '2026-01', total: 120 },
    { period: '2026-02', total: 180 },
    { period: '2026-03', total: 150 },
];

export default function ExampleChart({
    data = DEFAULT_DATA,
    title = 'Example chart',
    emptyMessage = 'No data in this range.',
    height = 280,
}) {
    const hasData = Array.isArray(data) && data.length > 0;

    return (
        <div className="rounded-xl border border-amber-200/30 bg-white p-4 shadow-sm">
            {title && (
                <h3 className="mb-4 font-display text-lg font-semibold text-stone-800">
                    {title}
                </h3>
            )}
            <div style={{ height: `${height}px` }}>
                {hasData ? (
                    <ResponsiveContainer width="100%" height="100%">
                        <AreaChart
                            data={data}
                            margin={{ top: 8, right: 8, left: 0, bottom: 0 }}
                        >
                            <CartesianGrid
                                strokeDasharray="3 3"
                                className="stroke-amber-200/50"
                            />
                            <XAxis
                                dataKey="period"
                                tick={{ fontSize: 11 }}
                                stroke="#78716c"
                            />
                            <YAxis
                                tick={{ fontSize: 11 }}
                                stroke="#78716c"
                                tickFormatter={(v) => (Number(v) >= 1000 ? `${(v / 1000).toFixed(1)}k` : v)}
                            />
                            <Tooltip
                                formatter={(value) => [Number(value).toLocaleString(), 'Total']}
                                contentStyle={{
                                    borderRadius: '8px',
                                    border: '1px solid #fde68a',
                                }}
                            />
                            <Area
                                type="monotone"
                                dataKey="total"
                                stroke="#d97706"
                                fill="#fcd34d"
                                fillOpacity={0.4}
                                strokeWidth={2}
                            />
                        </AreaChart>
                    </ResponsiveContainer>
                ) : (
                    <p className="flex h-full items-center justify-center text-sm text-stone-500">
                        {emptyMessage}
                    </p>
                )}
            </div>
        </div>
    );
}
