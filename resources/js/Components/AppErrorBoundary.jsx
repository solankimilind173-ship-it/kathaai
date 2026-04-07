import { Component } from 'react';

export default class AppErrorBoundary extends Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        console.error('AppErrorBoundary caught an error', error, errorInfo);
    }

    handleReload = () => {
        window.location.reload();
    };

    render() {
        if (!this.state.hasError) {
            return this.props.children;
        }

        return (
            <div className="cinematic-shell flex min-h-screen items-center justify-center px-4 py-10">
                <div className="cinematic-hero-card max-w-2xl rounded-[2rem] p-8 text-white sm:p-10">
                    <p className="text-xs font-semibold uppercase tracking-[0.32em] text-amber-200">
                        Something went wrong
                    </p>
                    <h1 className="mt-4 font-display text-4xl leading-tight sm:text-5xl">
                        The studio hit an unexpected client-side error.
                    </h1>
                    <p className="mt-5 text-sm leading-7 text-slate-300 sm:text-base">
                        We blocked the crash from taking over the whole session. Reload the page to try again.
                        If the problem continues, go back and retry the last action more safely.
                    </p>

                    {this.state.error?.message && (
                        <div className="mt-6 rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm text-slate-200">
                            {this.state.error.message}
                        </div>
                    )}

                    <div className="mt-8 flex flex-wrap gap-3">
                        <button
                            type="button"
                            onClick={this.handleReload}
                            className="inline-flex items-center justify-center rounded-full border border-amber-300/40 bg-gradient-to-r from-amber-400 via-orange-500 to-rose-500 px-5 py-3 text-xs font-semibold uppercase tracking-[0.22em] text-white shadow-lg shadow-orange-950/25"
                        >
                            Reload page
                        </button>
                        <a
                            href="/dashboard"
                            className="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/5 px-5 py-3 text-xs font-semibold uppercase tracking-[0.22em] text-slate-100 backdrop-blur-md"
                        >
                            Go to dashboard
                        </a>
                    </div>
                </div>
            </div>
        );
    }
}
