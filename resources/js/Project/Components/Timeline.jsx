import { useMemo } from 'react';
import Card from '@/Components/Card';

/**
 * Timeline component: displays a chronological list of project activity (created, episodes, last render).
 * Used on project show and in analytics context.
 */
export default function Timeline({ items = [], emptyMessage = 'No activity yet.' }) {
    const sorted = useMemo(() => {
        const list = Array.isArray(items) ? [...items] : [];
        return list.sort((a, b) => (b.date || '').localeCompare(a.date || ''));
    }, [items]);

    if (sorted.length === 0) {
        return (
            <Card>
                <p className="py-4 text-center text-sm text-gray-500">{emptyMessage}</p>
            </Card>
        );
    }

    return (
        <Card>
            <Card.Header>
                <Card.Title>Timeline</Card.Title>
                <p className="mt-1 text-sm text-gray-500">Recent project activity.</p>
            </Card.Header>
            <ul className="divide-y divide-gray-100">
                {sorted.slice(0, 20).map((item, i) => (
                    <li key={i} className="flex flex-col gap-0.5 py-3 first:pt-0 last:pb-0">
                        <span className="text-sm font-medium text-gray-900">{item.label}</span>
                        {item.description && (
                            <span className="text-xs text-gray-500">{item.description}</span>
                        )}
                        {item.date && (
                            <time className="text-xs text-gray-400" dateTime={item.date}>
                                {new Date(item.date).toLocaleString(undefined, {
                                    dateStyle: 'short',
                                    timeStyle: 'short',
                                })}
                            </time>
                        )}
                    </li>
                ))}
            </ul>
        </Card>
    );
}
