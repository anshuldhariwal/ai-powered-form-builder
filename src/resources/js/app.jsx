import { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const fieldTypes = ['text', 'textarea', 'number', 'email', 'phone', 'date', 'select', 'radio', 'checkbox', 'file', 'heading', 'rating'];

async function api(path, options = {}) {
    const response = await fetch(path, {
        credentials: 'same-origin',
        ...options,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, ...options.headers },
    });
    if (response.status === 401) { window.location.assign('/login'); throw new Error('Please sign in.'); }
    const body = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(Object.values(body.errors ?? {}).flat().join(' ') || body.message || 'Request failed.');
    return body;
}

function blankValidation() {
    return { min_length: null, max_length: null, min: null, max: null, email: false, url: false, numeric: false, regex: null, allowed_file_types: [], max_file_size_kb: null };
}

function blankSchema(title = 'Untitled form') {
    return {
        schema_version: '1.0',
        form: { title, description: null, submit_label: 'Submit', success_message: 'Thank you for your response.' },
        steps: [{ id: 'step_main', title: 'Form', description: null, sections: [{ id: 'section_main', title: 'Questions', description: null, fields: [] }] }],
        conditions: [], settings: { show_progress: true, allow_multiple_submissions: true },
    };
}

function AuthForm({ mode }) {
    const [error, setError] = useState('');
    const isRegistering = mode === 'register';
    async function submit(event) {
        event.preventDefault(); setError('');
        try { await api(isRegistering ? '/register' : '/login', { method: 'POST', body: JSON.stringify(Object.fromEntries(new FormData(event.currentTarget))) }); window.location.assign('/'); }
        catch (issue) { setError(issue.message); }
    }
    return <main className="grid min-h-screen place-items-center bg-slate-950 p-6 text-white"><form onSubmit={submit} className="w-full max-w-md space-y-4 rounded-3xl border border-slate-800 bg-slate-900 p-8 shadow-2xl"><Brand/><h1 className="text-3xl font-bold">{isRegistering ? 'Create your workspace' : 'Welcome back'}</h1>{isRegistering && <Input name="name" placeholder="Name"/>}<Input name="email" type="email" placeholder="Email"/><Input name="password" type="password" placeholder="Password"/>{isRegistering && <Input name="password_confirmation" type="password" placeholder="Confirm password"/>}{error && <p className="text-sm text-rose-400">{error}</p>}<button className="w-full rounded-xl bg-cyan-400 p-3 font-bold text-slate-950">{isRegistering ? 'Register' : 'Log in'}</button><a className="block text-center text-sm text-cyan-300" href={isRegistering ? '/login' : '/register'}>{isRegistering ? 'Already registered? Log in' : 'Create an account'}</a></form></main>;
}

function Brand() { return <p className="text-sm font-black uppercase tracking-[.22em] text-cyan-400">FormForge AI</p>; }
function Input(props) { return <input {...props} required className="w-full rounded-xl border border-slate-700 bg-slate-800 p-3 outline-none focus:border-cyan-400"/>; }

function Dashboard() {
    const [data, setData] = useState(null); const [error, setError] = useState('');
    useEffect(() => { api('/api/forms').then(setData).catch((e) => setError(e.message)); }, []);
    async function createForm() { try { const form = await api('/api/forms', { method: 'POST', body: JSON.stringify({ schema: blankSchema() }) }); window.location.assign(`/forms/${form.public_id}`); } catch (e) { setError(e.message); } }
    async function logout() { await api('/logout', { method: 'POST' }); window.location.assign('/login'); }
    return <main className="min-h-screen bg-slate-950 p-6 text-slate-100"><div className="mx-auto max-w-6xl"><header className="flex items-center justify-between py-6"><div><Brand/><h1 className="mt-2 text-3xl font-bold">{data?.tenant?.name ?? 'Your forms'}</h1></div><div className="flex gap-3"><button onClick={createForm} className="rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950">New form</button><button onClick={logout} className="rounded-xl border border-slate-700 px-4">Log out</button></div></header>{error && <p className="text-rose-400">{error}</p>}<div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">{data?.forms?.map((form) => <a key={form.public_id} href={`/forms/${form.public_id}`} className="rounded-2xl border border-slate-800 bg-slate-900 p-5 hover:border-cyan-500"><div className="flex justify-between"><h2 className="font-bold">{form.title}</h2><span className="rounded-full bg-slate-800 px-2 py-1 text-xs uppercase">{form.status}</span></div><p className="mt-6 text-sm text-slate-400">Version {form.current_version?.version_number ?? 1}</p></a>)}</div>{data?.forms?.length === 0 && <p className="mt-16 text-center text-slate-400">Create your first form to begin.</p>}</div></main>;
}

function Builder({ publicId }) {
    const [form, setForm] = useState(null); const [schema, setSchema] = useState(null); const [error, setError] = useState(''); const [saved, setSaved] = useState(''); const [raw, setRaw] = useState(false); const [submissions, setSubmissions] = useState(null);
    useEffect(() => { api(`/api/forms/${publicId}`).then((value) => { setForm(value); setSchema(value.current_version.schema_json); }).catch((e) => setError(e.message)); }, [publicId]);
    const fields = schema?.steps[0].sections[0].fields ?? [];
    function updateFields(next) { setSchema({ ...schema, steps: [{ ...schema.steps[0], sections: [{ ...schema.steps[0].sections[0], fields: next }] }] }); setSaved(''); }
    function add(type) { const token = crypto.randomUUID().replaceAll('-', '').slice(0, 10); const options = ['select', 'radio'].includes(type) ? [{ label: 'Option 1', value: 'option_1' }] : []; updateFields([...fields, { id: `field_${token}`, type, key: `${type}_${token}`, label: type[0].toUpperCase() + type.slice(1), placeholder: null, help_text: null, default: null, required: false, options, validation: blankValidation() }]); }
    function patchField(index, values) { updateFields(fields.map((field, position) => position === index ? { ...field, ...values } : field)); }
    function move(index, delta) { const next = [...fields]; const target = index + delta; if (target < 0 || target >= next.length) return; [next[index], next[target]] = [next[target], next[index]]; updateFields(next); }
    async function save() { setError(''); try { const result = await api(`/api/forms/${publicId}`, { method: 'PUT', body: JSON.stringify({ schema }) }); setSaved(`Saved version ${result.version.version_number}`); } catch (e) { setError(e.message); } }
    async function publish() { setError(''); try { const value = await api(`/api/forms/${publicId}/publish`, { method: 'POST' }); setForm({ ...form, ...value }); setSaved('Published'); } catch (e) { setError(e.message); } }
    async function loadSubmissions() { try { const value = await api(`/api/forms/${publicId}/submissions`); setSubmissions(value.data); } catch (e) { setError(e.message); } }
    if (!schema) return <main className="min-h-screen bg-slate-950 p-8 text-white">Loading…</main>;
    return <main className="min-h-screen bg-slate-950 text-slate-100"><header className="sticky top-0 z-10 flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 bg-slate-950/95 px-6 py-4"><a href="/" className="font-bold text-cyan-400">← Forms</a><input value={schema.form.title} onChange={(e) => setSchema({ ...schema, form: { ...schema.form, title: e.target.value } })} className="min-w-64 bg-transparent text-center text-xl font-bold outline-none"/><div className="flex gap-2"><button onClick={loadSubmissions} className="rounded-lg border border-slate-700 px-3 py-2">Responses</button><button onClick={() => setRaw(!raw)} className="rounded-lg border border-slate-700 px-3 py-2">{raw ? 'Visual' : 'JSON'}</button><button onClick={save} className="rounded-lg bg-slate-700 px-4 py-2 font-bold">Save</button><button onClick={publish} className="rounded-lg bg-cyan-400 px-4 py-2 font-bold text-slate-950">Publish</button></div></header><div className="mx-auto grid max-w-7xl gap-6 p-6 lg:grid-cols-[240px_1fr]"><aside className="rounded-2xl border border-slate-800 bg-slate-900 p-4"><h2 className="mb-3 font-bold">Add a field</h2><div className="grid grid-cols-2 gap-2">{fieldTypes.map((type) => <button key={type} onClick={() => add(type)} className="rounded-lg bg-slate-800 p-2 text-left text-sm capitalize hover:bg-slate-700">+ {type}</button>)}</div>{form?.published_at && <a className="mt-5 block break-all text-sm text-cyan-300" target="_blank" href={`/f/${form.tenant.slug}/${form.slug}`}>Open public form ↗</a>}</aside><section>{error && <p className="mb-3 rounded-lg bg-rose-950 p-3 text-rose-300">{error}</p>}{saved && <p className="mb-3 text-emerald-400">{saved}</p>}{submissions ? <div className="space-y-3 rounded-2xl border border-slate-800 bg-slate-900 p-5"><div className="flex justify-between"><h2 className="text-xl font-bold">Responses ({submissions.length})</h2><button onClick={() => setSubmissions(null)} className="text-cyan-300">Back to builder</button></div>{submissions.map((submission) => <pre key={submission.public_id} className="overflow-auto rounded-xl bg-slate-800 p-4 text-sm">{JSON.stringify(submission.data_json, null, 2)}</pre>)}{submissions.length === 0 && <p className="text-slate-400">No responses yet.</p>}</div> : raw ? <textarea className="min-h-[70vh] w-full rounded-2xl bg-slate-900 p-5 font-mono text-sm" value={JSON.stringify(schema, null, 2)} onChange={(e) => { try { setSchema(JSON.parse(e.target.value)); setError(''); } catch { setError('JSON is not valid yet.'); } }}/>: <div className="space-y-3 rounded-2xl border border-slate-800 bg-slate-900 p-5">{fields.map((field, index) => <div key={field.id} className="rounded-xl border border-slate-700 bg-slate-800 p-4"><div className="flex gap-2"><input value={field.label} onChange={(e) => patchField(index, { label: e.target.value })} className="flex-1 bg-transparent font-bold outline-none"/><span className="text-xs uppercase text-slate-400">{field.type}</span></div><div className="mt-3 flex flex-wrap items-center gap-3 text-sm"><label><input type="checkbox" checked={field.required} disabled={field.type === 'heading'} onChange={(e) => patchField(index, { required: e.target.checked })}/> Required</label><button onClick={() => move(index, -1)}>↑ Up</button><button onClick={() => move(index, 1)}>↓ Down</button><button onClick={() => updateFields(fields.filter((_, position) => position !== index))} className="text-rose-400">Delete</button></div></div>)}{fields.length === 0 && <p className="py-16 text-center text-slate-400">Choose a field type to start building.</p>}</div>}</section></div></main>;
}

function PublicForm({ tenantSlug, formSlug }) {
    const [schema, setSchema] = useState(null); const [message, setMessage] = useState(''); const [error, setError] = useState('');
    useEffect(() => { api(`/api/public/forms/${tenantSlug}/${formSlug}`).then((value) => setSchema(value.schema)).catch((e) => setError(e.message)); }, [tenantSlug, formSlug]);
    const fields = useMemo(() => schema?.steps.flatMap((step) => step.sections.flatMap((section) => section.fields)) ?? [], [schema]);
    async function submit(event) { event.preventDefault(); setError(''); const answers = Object.fromEntries(new FormData(event.currentTarget)); try { const result = await api(`/api/public/forms/${tenantSlug}/${formSlug}`, { method: 'POST', body: JSON.stringify({ answers }) }); setMessage(result.message); } catch (e) { setError(e.message); } }
    if (error && !schema) return <main className="grid min-h-screen place-items-center bg-slate-950 text-rose-300">{error}</main>;
    if (!schema) return <main className="grid min-h-screen place-items-center bg-slate-950 text-white">Loading…</main>;
    if (message) return <main className="grid min-h-screen place-items-center bg-slate-950 p-6 text-white"><div className="rounded-3xl bg-slate-900 p-10 text-center"><h1 className="text-3xl font-bold">Submitted</h1><p className="mt-3 text-slate-300">{message}</p></div></main>;
    return <main className="min-h-screen bg-slate-950 p-6 text-slate-100"><form onSubmit={submit} className="mx-auto max-w-2xl space-y-5 rounded-3xl border border-slate-800 bg-slate-900 p-8"><Brand/><h1 className="text-4xl font-bold">{schema.form.title}</h1>{schema.form.description && <p className="text-slate-400">{schema.form.description}</p>}{fields.map((field) => <PublicField key={field.id} field={field}/>)}{error && <p className="text-rose-400">{error}</p>}<button className="w-full rounded-xl bg-cyan-400 p-3 font-bold text-slate-950">{schema.form.submit_label}</button></form></main>;
}

function PublicField({ field }) {
    if (field.type === 'heading') return <h2 className="pt-3 text-2xl font-bold">{field.label}</h2>;
    const common = { name: field.key, required: field.required, className: 'mt-2 w-full rounded-xl border border-slate-700 bg-slate-800 p-3' };
    return <label className="block"><span className="font-semibold">{field.label}{field.required && ' *'}</span>{field.help_text && <span className="ml-2 text-sm text-slate-400">{field.help_text}</span>}{field.type === 'textarea' ? <textarea {...common}/>: ['select', 'radio'].includes(field.type) ? <select {...common}><option value="">Choose…</option>{field.options.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select>: field.type === 'checkbox' ? <input {...common} className="ml-3" type="checkbox" value="1"/> : <input {...common} type={field.type === 'rating' ? 'number' : field.type} min={field.validation.min ?? undefined} max={field.validation.max ?? undefined}/>}</label>;
}

export default function App() {
    const path = window.location.pathname;
    if (path === '/login') return <AuthForm mode="login"/>;
    if (path === '/register') return <AuthForm mode="register"/>;
    if (path.startsWith('/forms/')) return <Builder publicId={path.split('/')[2]}/>;
    if (path.startsWith('/f/')) { const [, , tenantSlug, formSlug] = path.split('/'); return <PublicForm tenantSlug={tenantSlug} formSlug={formSlug}/>; }
    return <Dashboard/>;
}

createRoot(document.getElementById('app')).render(<App/>);
