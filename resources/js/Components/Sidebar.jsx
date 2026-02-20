import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

const userNavItems = [
    { href: 'dashboard', label: 'Dashboard', icon: DashboardIcon },
    { href: 'projects.index', label: 'Projects', icon: ProjectIcon },
    { href: 'gallery.index', label: 'Image Gallery', icon: GalleryIcon },
    { href: 'video-gallery.index', label: 'Video Gallery', icon: VideoGalleryIcon },
    { href: 'billing.index', label: 'Billing & usage', icon: BillingIcon },
    { href: 'upgrade', label: 'Upgrade', icon: SubscriptionIcon },
    { href: 'help.index', label: 'Help', icon: HelpIcon },
];

function LogIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
        </svg>
    );
}

function QueueIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
    );
}

const adminNavItems = [
    { href: 'admin.dashboard', label: 'Dashboard', icon: DashboardIcon },
    { href: 'admin.subscriptions.index', label: 'Plans', icon: SubscriptionIcon },
    { href: 'admin.subscription-records.index', label: 'Subscription records', icon: SubscriptionIcon },
    { href: 'admin.users.index', label: 'Users', icon: UsersIcon },
    { href: 'admin.projects.index', label: 'Projects', icon: ProjectIcon },
    { href: 'admin.notifications.index', label: 'Notifications', icon: NotificationIcon },
    { href: 'admin.analytics.index', label: 'Analytics', icon: AnalyticsIcon },
    { href: 'admin.failed-jobs.index', label: 'Failed jobs', icon: QueueIcon },
    { href: 'admin.logs.index', label: 'Logs', icon: LogIcon },
];

function DashboardIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
        </svg>
    );
}

function ProjectIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5v-7.5H8.25v7.5z" />
        </svg>
    );
}

function GalleryIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
        </svg>
    );
}

function VideoGalleryIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 3v18h16.5V3H3.75z" />
        </svg>
    );
}

function SubscriptionIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
        </svg>
    );
}

function BillingIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 18.75h19.5m-18-18v18m18-18v18m-18-2.25h19.5M2.25 6h19.5" />
        </svg>
    );
}

function HelpIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
        </svg>
    );
}

function UsersIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
        </svg>
    );
}

function NotificationIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
    );
}

function AnalyticsIcon({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
        </svg>
    );
}

function ChevronLeft({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
    );
}

function ChevronRight({ className = 'h-5 w-5' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
        </svg>
    );
}

export default function Sidebar({ mobileOpen = false, onNavigate, collapsed: controlledCollapsed, onCollapsedChange }) {
    const { url } = usePage();
    const user = usePage().props.auth?.user;
    const [internalCollapsed, setInternalCollapsed] = useState(false);
    const collapsed = onCollapsedChange ? controlledCollapsed : internalCollapsed;
    const setCollapsed = onCollapsedChange
        ? (v) => {
            const next = typeof v === 'function' ? v(collapsed) : v;
            onCollapsedChange(next);
          }
        : setInternalCollapsed;

    const handleLinkClick = () => onNavigate?.();
    const handleCollapseToggle = () => setCollapsed((c) => !c);

    const isAdmin = user?.role === 'admin' || user?.role === 'super_admin';
    const navItems = isAdmin ? adminNavItems : userNavItems;

    const isActive = (routeName) => {
        if (routeName === 'dashboard') return url === '/dashboard';
        if (routeName === 'admin.dashboard') return url === '/admin' || url === '/admin/';
        if (routeName === 'admin.subscriptions.index') return url.startsWith('/admin/subscriptions') && !url.startsWith('/admin/subscription-records');
        if (routeName === 'admin.subscription-records.index') return url.startsWith('/admin/subscription-records');
        if (routeName === 'admin.users.index') return url.startsWith('/admin/users');
        if (routeName === 'admin.projects.index') return url.startsWith('/admin/projects');
        if (routeName === 'admin.notifications.index') return url.startsWith('/admin/notifications');
        if (routeName === 'admin.analytics.index') return url.startsWith('/admin/analytics');
        if (routeName === 'admin.failed-jobs.index') return url.startsWith('/admin/failed-jobs');
        if (routeName === 'admin.logs.index') return url.startsWith('/admin/logs');
        if (routeName === 'projects.index') return url.startsWith('/projects') && !url.startsWith('/admin');
        if (routeName === 'gallery.index') return url.startsWith('/gallery') && !url.startsWith('/video-gallery');
        if (routeName === 'video-gallery.index') return url.startsWith('/video-gallery');
        if (routeName === 'billing.index') return url.startsWith('/billing');
        if (routeName === 'upgrade') return url.startsWith('/upgrade');
        if (routeName === 'help.index') return url.startsWith('/help');
        return false;
    };

    return (
        <aside
            className={`fixed left-0 top-0 z-40 flex h-screen flex-col border-r border-amber-200/60 bg-white/98 shadow-xl shadow-amber-900/5 backdrop-blur-xl transition-all duration-300 ease-in-out
                lg:translate-x-0 ${mobileOpen ? 'translate-x-0' : '-translate-x-full'}
                ${collapsed ? 'lg:w-[72px] w-64' : 'w-64'}`}
        >
            <div className="flex h-16 shrink-0 items-center border-b border-amber-200/50 px-4">
                <Link href="/" className="flex items-center gap-3 overflow-hidden">
                    <ApplicationLogo className="h-8 w-auto shrink-0" />
                    {!collapsed && (
                        <span className="font-display text-lg font-semibold tracking-wide text-amber-900 truncate">
                            KATHAAI
                        </span>
                    )}
                </Link>
            </div>

            <button
                type="button"
                onClick={handleCollapseToggle}
                className="absolute -right-3 top-20 z-10 flex h-6 w-6 items-center justify-center rounded-full border border-amber-300 bg-white text-amber-700 shadow-md transition-all hover:border-amber-400 hover:bg-amber-50"
                aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
            >
                {collapsed ? <ChevronRight className="h-3.5 w-3.5" /> : <ChevronLeft className="h-3.5 w-3.5" />}
            </button>

            <nav className="flex-1 space-y-0.5 overflow-y-auto px-3 py-4">
                {navItems.map((item) => {
                    const active = isActive(item.href);
                    const href = route(item.href);
                    return (
                        <Link
                            key={item.href}
                            href={href}
                            onClick={handleLinkClick}
                            className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 ${
                                active
                                    ? 'bg-amber-100 text-amber-900 shadow-inner'
                                    : 'text-stone-600 hover:bg-amber-50 hover:text-amber-800'
                            } ${collapsed ? 'justify-center px-2' : ''}`}
                        >
                            <item.icon className={`h-5 w-5 shrink-0 ${active ? 'text-amber-700' : 'text-amber-600'}`} />
                            {!collapsed && <span>{item.label}</span>}
                        </Link>
                    );
                })}
            </nav>

            <div className={`border-t border-amber-200/50 p-3 ${collapsed ? 'flex justify-center' : ''}`}>
                <Menu as="div" className="relative">
                    <MenuButton
                        className={`flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm transition-all hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-300 data-[active]:bg-amber-50 ${
                            collapsed ? 'justify-center' : ''
                        }`}
                    >
                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-200 text-sm font-semibold text-amber-900">
                            {user?.name?.charAt(0)?.toUpperCase() || 'U'}
                        </div>
                        {!collapsed && (
                            <div className="min-w-0 flex-1 truncate">
                                <p className="font-medium text-stone-800">{user?.name}</p>
                                <p className="truncate text-xs text-stone-500">{user?.email}</p>
                            </div>
                        )}
                        {!collapsed && (
                            <svg className="h-4 w-4 shrink-0 text-stone-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                            </svg>
                        )}
                    </MenuButton>
                    <MenuItems
                        anchor="bottom start"
                        className="z-[101] mt-2 min-w-[12rem] origin-top-left rounded-lg border border-amber-200/50 bg-white py-1 shadow-xl focus:outline-none"
                    >
                        {isAdmin && !url.startsWith('/admin') && (
                            <MenuItem>
                                <Link
                                    href={route('admin.dashboard')}
                                    className="block px-4 py-2 text-sm text-stone-700 data-[focus]:bg-amber-50"
                                >
                                    Admin
                                </Link>
                            </MenuItem>
                        )}
                        {!url.startsWith('/admin') && (
                            <>
                                <MenuItem>
                                    <Link
                                        href={route('profile.edit')}
                                        className="block px-4 py-2 text-sm text-stone-700 data-[focus]:bg-amber-50"
                                    >
                                        Profile
                                    </Link>
                                </MenuItem>
                                <MenuItem>
                                    <Link
                                        href={route('settings.index')}
                                        className="block px-4 py-2 text-sm text-stone-700 data-[focus]:bg-amber-50"
                                    >
                                        Settings
                                    </Link>
                                </MenuItem>
                                <div className="my-1 border-t border-amber-200/60" aria-hidden />
                            </>
                        )}
                        <MenuItem>
                            <Link
                                method="post"
                                href={route('logout')}
                                as="button"
                                className="block w-full px-4 py-2 text-left text-sm text-stone-700 data-[focus]:bg-amber-50"
                            >
                                Logout
                            </Link>
                        </MenuItem>
                    </MenuItems>
                </Menu>
            </div>
        </aside>
    );
}
