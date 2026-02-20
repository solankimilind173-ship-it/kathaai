/**
 * Admin module layout. Wraps AuthenticatedLayout for admin-specific shell.
 * Supports optional breadcrumbs (array of { label, href? }) above the header.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Breadcrumbs from '@/Components/Breadcrumbs';

export default function AdminLayout({ header, children, breadcrumbs }) {
    const headerContent = (
        <>
            {breadcrumbs?.length > 0 && <Breadcrumbs items={breadcrumbs} />}
            {header}
        </>
    );
    return <AuthenticatedLayout header={headerContent}>{children}</AuthenticatedLayout>;
}
