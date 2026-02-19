/**
 * Donut chart for credits: Used vs Remaining, with legend.
 * used: number, allowance: number (optional). If no allowance, full circle = used.
 */
export default function CreditsPieChart({ used = 0, allowance = 0, size = 200, className = '' }) {
    const usedNum = Number(used) || 0;
    const allowanceNum = Number(allowance) || 0;
    const remaining = Math.max(0, allowanceNum - usedNum);
    const total = allowanceNum > 0 ? allowanceNum : (usedNum || 1);
    const usedPct = total > 0 ? usedNum / total : 0;
    const remainingPct = total > 0 ? remaining / total : 0;

    const strokeWidth = Math.max(10, size / 6);
    const r = (size - strokeWidth) / 2;
    const cx = size / 2;
    const cy = size / 2;
    const circumference = 2 * Math.PI * r;
    const usedStroke = usedPct * circumference;
    const remainingStroke = remainingPct * circumference;

    const legend = [
        { label: 'Used', value: usedNum, color: 'bg-amber-500', stroke: 'text-amber-600' },
        { label: 'Remaining', value: remaining, color: 'bg-stone-300', stroke: 'text-stone-500' },
    ];

    return (
        <div className={`flex flex-col items-center gap-4 ${className}`}>
            <svg width={size} height={size} className="shrink-0" viewBox={`0 0 ${size} ${size}`}>
                <g transform={`rotate(-90 ${cx} ${cy})`}>
                    {/* Segment 1: Remaining (gray) - drawn first from 0 */}
                    {(remainingStroke > 0 || (usedStroke === 0 && remainingStroke === 0)) && (
                        <circle
                            cx={cx}
                            cy={cy}
                            r={r}
                            fill="none"
                            stroke="currentColor"
                            strokeWidth={strokeWidth}
                            strokeDasharray={
                                remainingStroke > 0
                                    ? `${remainingStroke} ${circumference - remainingStroke}`
                                    : `${circumference} 0`
                            }
                            strokeLinecap="round"
                            className="text-stone-300"
                        />
                    )}
                    {/* Segment 2: Used (amber) - drawn after remaining */}
                    {usedStroke > 0 && (
                        <circle
                            cx={cx}
                            cy={cy}
                            r={r}
                            fill="none"
                            stroke="currentColor"
                            strokeWidth={strokeWidth}
                            strokeDasharray={`${usedStroke} ${circumference - usedStroke}`}
                            strokeDashoffset={-remainingStroke}
                            strokeLinecap="round"
                            className="text-amber-500 transition-all duration-500"
                        />
                    )}
                </g>
            </svg>

            <div className="text-center">
                <p className="font-display text-2xl font-bold text-stone-900">
                    {usedNum}
                    {allowanceNum > 0 && (
                        <span className="font-sans text-lg font-normal text-stone-500"> / {allowanceNum}</span>
                    )}
                </p>
                <p className="text-xs font-medium uppercase tracking-wider text-stone-500">credits used</p>
            </div>

            {/* Legend: which colour = which data */}
            <div className="flex flex-wrap justify-center gap-6 border-t border-stone-200 pt-4">
                {legend.map((item) => (
                    <div key={item.label} className="flex items-center gap-2">
                        <span className={`h-3 w-3 shrink-0 rounded-full ${item.color}`} aria-hidden />
                        <span className={`text-sm font-medium ${item.stroke}`}>
                            {item.label}: {item.value}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}
