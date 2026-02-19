/**
 * Admin module layout. Wraps AuthenticatedLayout for admin-specific shell.
 * Use for all Admin pages so future admin-only chrome (e.g. breadcrumbs) lives here.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function AdminLayout({ header, children }) {
    return <AuthenticatedLayout header={header}>{children}</AuthenticatedLayout>;
}
