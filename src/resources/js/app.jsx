import { useState } from 'react';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

async function submitAuth(path, payload) {
    const response = await fetch(path, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(payload),
    });

    if (response.ok) {
        window.location.assign('/');
        return;
    }

    const body = await response.json();
    throw new Error(Object.values(body.errors ?? {}).flat()[0] ?? body.message ?? 'Authentication failed.');
}

function AuthForm({ mode }) {
    const [error, setError] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const isRegistering = mode === 'register';

    async function handleSubmit(event) {
        event.preventDefault();
        setError('');
        setSubmitting(true);

        const values = Object.fromEntries(new FormData(event.currentTarget));

        try {
            await submitAuth(isRegistering ? '/register' : '/login', values);
        } catch (submissionError) {
            setError(submissionError.message);
            setSubmitting(false);
        }
    }

    return (
        <main className="grid min-h-screen place-items-center bg-slate-950 px-6 text-slate-100">
            <form className="w-full max-w-md space-y-5 rounded-2xl border border-slate-800 bg-slate-900 p-8" onSubmit={handleSubmit}>
                <div>
                    <p className="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-400">FormForge AI</p>
                    <h1 className="mt-2 text-3xl font-semibold">{isRegistering ? 'Create account' : 'Welcome back'}</h1>
                </div>
                {isRegistering && <input className="w-full rounded-lg bg-slate-800 p-3" name="name" placeholder="Name" required />}
                <input className="w-full rounded-lg bg-slate-800 p-3" name="email" placeholder="Email" type="email" required />
                <input className="w-full rounded-lg bg-slate-800 p-3" name="password" placeholder="Password" type="password" required />
                {isRegistering && <input className="w-full rounded-lg bg-slate-800 p-3" name="password_confirmation" placeholder="Confirm password" type="password" required />}
                {error && <p className="text-sm text-red-400" role="alert">{error}</p>}
                <button className="w-full rounded-lg bg-cyan-400 p-3 font-semibold text-slate-950 disabled:opacity-50" disabled={submitting} type="submit">
                    {submitting ? 'Please wait...' : isRegistering ? 'Register' : 'Log in'}
                </button>
                <a className="block text-center text-sm text-cyan-300" href={isRegistering ? '/login' : '/register'}>
                    {isRegistering ? 'Already registered? Log in' : 'Need an account? Register'}
                </a>
            </form>
        </main>
    );
}

function Home() {
    return (
        <main className="min-h-screen bg-slate-950 px-6 py-16 text-slate-100">
            <div className="mx-auto max-w-5xl">
                <p className="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-400">FormForge AI</p>
                <h1 className="mt-4 text-4xl font-semibold tracking-tight">React application foundation</h1>
                <div className="mt-8 flex gap-4">
                    <a className="rounded-lg bg-cyan-400 px-5 py-3 font-semibold text-slate-950" href="/login">Log in</a>
                    <a className="rounded-lg border border-slate-700 px-5 py-3 font-semibold" href="/register">Register</a>
                </div>
            </div>
        </main>
    );
}

export default function App() {
    if (window.location.pathname === '/login') return <AuthForm mode="login" />;
    if (window.location.pathname === '/register') return <AuthForm mode="register" />;
    return <Home />;
}
