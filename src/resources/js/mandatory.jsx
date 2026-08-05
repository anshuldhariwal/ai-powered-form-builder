import { useEffect, useState } from 'react';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const fieldTypes = ['text', 'textarea', 'number', 'email', 'phone', 'date', 'select', 'radio', 'checkbox', 'file', 'heading', 'rating'];

async function api(path, options = {}) {
    const response = await fetch(path, { credentials: 'same-origin', ...options, headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, ...options.headers } });
    if (response.status === 401) { window.location.assign('/login'); throw new Error('Please sign in.'); }
    const body = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(Object.values(body.errors ?? {}).flat().join(' ') || body.message || 'Request failed.');
    return body;
}

async function multipartApi(path, answers) {
    const body = new FormData();
    const plain = {};
    Object.entries(answers).forEach(([key, value]) => value instanceof File ? body.append(`files[${key}]`, value) : plain[key] = value);
    body.append('answers', JSON.stringify(plain));
    const response = await fetch(path, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken }, body });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(Object.values(result.errors ?? {}).flat().join(' ') || result.message || 'Request failed.');
    return result;
}

function blankValidation() {
    return { min_length: null, max_length: null, min: null, max: null, email: false, url: false, numeric: false, regex: null, allowed_file_types: [], max_file_size_kb: null };
}

const control = 'w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm';

function token(prefix) {
    return `${prefix}_${crypto.randomUUID().replaceAll('-', '').slice(0, 10)}`;
}

function makeField(type) {
    const id = token('field');
    return { id, type, key: `${type}_${id.slice(-10)}`, label: type[0].toUpperCase() + type.slice(1), placeholder: null, help_text: null, default: null, required: false, options: ['select', 'radio'].includes(type) ? [{ label: 'Option 1', value: 'option_1' }] : [], validation: blankValidation() };
}

function cloneField(field) {
    const id = token('field');
    return { ...structuredClone(field), id, key: `${field.key}_copy_${id.slice(-5)}`, label: `${field.label} copy` };
}

export function MandatoryBuilder({ publicId }) {
    const [form, setForm] = useState(null);
    const [schema, setSchema] = useState(null);
    const [stepIndex, setStepIndex] = useState(0);
    const [sectionIndex, setSectionIndex] = useState(0);
    const [mode, setMode] = useState('builder');
    const [rawText, setRawText] = useState('');
    const [error, setError] = useState('');
    const [notice, setNotice] = useState('');
    const [responses, setResponses] = useState(null);
    const [search, setSearch] = useState('');
    const [dragIndex, setDragIndex] = useState(null);
    const [aiBusy, setAiBusy] = useState(false);

    useEffect(() => {
        api(`/api/forms/${publicId}`).then((value) => {
            setForm(value);
            setSchema(value.current_version.schema_json);
            setRawText(JSON.stringify(value.current_version.schema_json, null, 2));
        }).catch((issue) => setError(issue.message));
    }, [publicId]);

    const step = schema?.steps[stepIndex];
    const section = step?.sections[sectionIndex];
    const fields = section?.fields ?? [];

    function commit(next) {
        setSchema(next);
        setRawText(JSON.stringify(next, null, 2));
        setNotice('');
    }
    function patchForm(values) { commit({ ...schema, form: { ...schema.form, ...values } }); }
    function patchStep(values) {
        const steps = schema.steps.map((item, index) => index === stepIndex ? { ...item, ...values } : item);
        commit({ ...schema, steps });
    }
    function patchSection(values) {
        patchStep({ sections: step.sections.map((item, index) => index === sectionIndex ? { ...item, ...values } : item) });
    }
    function updateFields(next) { patchSection({ fields: next }); }
    function patchField(index, values) { updateFields(fields.map((field, position) => position === index ? { ...field, ...values } : field)); }
    function patchValidation(index, values) { patchField(index, { validation: { ...fields[index].validation, ...values } }); }
    function move(index, target) {
        if (target < 0 || target >= fields.length || index === target) return;
        const next = [...fields];
        const [item] = next.splice(index, 1);
        next.splice(target, 0, item);
        updateFields(next);
    }
    function addStep() {
        const next = { ...schema, steps: [...schema.steps, { id: token('step'), title: `Step ${schema.steps.length + 1}`, description: null, sections: [{ id: token('section'), title: 'Questions', description: null, fields: [] }] }] };
        commit(next); setStepIndex(next.steps.length - 1); setSectionIndex(0);
    }
    function addSection() {
        patchStep({ sections: [...step.sections, { id: token('section'), title: `Section ${step.sections.length + 1}`, description: null, fields: [] }] });
        setSectionIndex(step.sections.length);
    }
    async function save() {
        setError('');
        try { const result = await api(`/api/forms/${publicId}`, { method: 'PUT', body: JSON.stringify({ schema }) }); setNotice(`Saved version ${result.version.version_number}`); }
        catch (issue) { setError(issue.message); }
    }
    async function publish() {
        setError('');
        try { const value = await api(`/api/forms/${publicId}/publish`, { method: 'POST' }); setForm({ ...form, ...value }); setNotice('Published'); }
        catch (issue) { setError(issue.message); }
    }
    async function editWithAi() {
        const prompt = window.prompt('Describe the change to make');
        if (!prompt) return;
        setAiBusy(true); setError('');
        try {
            let request = await api(`/api/forms/${publicId}/ai/edit`, { method: 'POST', body: JSON.stringify({ prompt }) });
            for (let attempt = 0; attempt < 30 && !['succeeded', 'failed'].includes(request.status); attempt += 1) { await new Promise((resolve) => setTimeout(resolve, 1000)); request = await api(`/api/ai-requests/${request.public_id}`); }
            if (request.status !== 'succeeded') throw new Error(request.error_message || 'AI edit failed.');
            if (window.confirm('Apply the generated edit as a new immutable version?')) { const updated = await api(`/api/ai-requests/${request.public_id}/accept`, { method: 'POST' }); commit(updated.current_version.schema_json); setNotice('AI edit accepted as a new version'); }
        } catch (issue) { setError(issue.message); }
        finally { setAiBusy(false); }
    }
    async function loadResponses(query = '') {
        try { const value = await api(`/api/forms/${publicId}/submissions?search=${encodeURIComponent(query)}`); setResponses(value); setMode('responses'); }
        catch (issue) { setError(issue.message); }
    }
    function applyRaw(value) {
        setRawText(value);
        try { setSchema(JSON.parse(value)); setError(''); }
        catch { setError('JSON is not valid yet; the last valid schema is preserved.'); }
    }
    if (!schema) return <main className="min-h-screen bg-slate-950 p-8 text-white">Loading…</main>;

    return <main className="min-h-screen bg-slate-950 text-slate-100">
        <header className="sticky top-0 z-10 flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 bg-slate-950/95 px-5 py-4">
            <a href="/" className="font-bold text-cyan-400">← Forms</a>
            <input value={schema.form.title} onChange={(event) => patchForm({ title: event.target.value })} className="min-w-64 bg-transparent text-center text-xl font-bold outline-none" />
            <div className="flex flex-wrap gap-2">
                <button onClick={() => loadResponses()} className="rounded-lg border border-slate-700 px-3 py-2">Responses</button>
                <button disabled={aiBusy} onClick={editWithAi} className="rounded-lg border border-cyan-500 px-3 py-2 text-cyan-300">{aiBusy ? 'AI working…' : 'Edit with AI'}</button>
                <button onClick={() => setMode(mode === 'json' ? 'builder' : 'json')} className="rounded-lg border border-slate-700 px-3 py-2">{mode === 'json' ? 'Builder' : 'JSON'}</button>
                <button onClick={save} className="rounded-lg bg-slate-700 px-4 py-2 font-bold">Save draft</button>
                <button onClick={publish} className="rounded-lg bg-cyan-400 px-4 py-2 font-bold text-slate-950">Publish</button>
            </div>
        </header>
        <div className="mx-auto grid max-w-[1500px] gap-5 p-5 lg:grid-cols-[250px_1fr]">
            <aside className="space-y-5 rounded-2xl border border-slate-800 bg-slate-900 p-4">
                <div><h2 className="mb-2 font-bold">Steps</h2>{schema.steps.map((item, index) => <button key={item.id} onClick={() => { setStepIndex(index); setSectionIndex(0); }} className={`mb-1 block w-full rounded-lg p-2 text-left text-sm ${index === stepIndex ? 'bg-cyan-400 text-slate-950' : 'bg-slate-800'}`}>{index + 1}. {item.title}</button>)}<button onClick={addStep} className="mt-2 text-sm text-cyan-300">+ Add step</button></div>
                <div><h2 className="mb-2 font-bold">Sections</h2>{step.sections.map((item, index) => <button key={item.id} onClick={() => setSectionIndex(index)} className={`mb-1 block w-full rounded-lg p-2 text-left text-sm ${index === sectionIndex ? 'bg-cyan-400 text-slate-950' : 'bg-slate-800'}`}>{item.title}</button>)}<button onClick={addSection} className="mt-2 text-sm text-cyan-300">+ Add section</button></div>
                <div><h2 className="mb-2 font-bold">Fields</h2><div className="grid grid-cols-2 gap-2">{fieldTypes.map((type) => <button key={type} onClick={() => updateFields([...fields, makeField(type)])} className="rounded-lg bg-slate-800 p-2 text-left text-xs capitalize hover:bg-slate-700">+ {type}</button>)}</div></div>
                {form?.published_at && <a className="block break-all text-sm text-cyan-300" target="_blank" href={`/f/${form.tenant.slug}/${form.slug}`}>Open public form ↗</a>}
            </aside>
            <section>
                {error && <p className="mb-3 rounded-lg bg-rose-950 p-3 text-rose-300">{error}</p>}{notice && <p className="mb-3 text-emerald-400">{notice}</p>}
                {mode === 'responses' && <Responses publicId={publicId} responses={responses} search={search} setSearch={setSearch} reload={loadResponses} close={() => setMode('builder')} />}
                {mode === 'json' && <textarea className="min-h-[75vh] w-full rounded-2xl bg-slate-900 p-5 font-mono text-sm" value={rawText} onChange={(event) => applyRaw(event.target.value)} />}
                {mode === 'builder' && <div className="space-y-4 rounded-2xl border border-slate-800 bg-slate-900 p-5">
                    <input value={step.title} onChange={(event) => patchStep({ title: event.target.value })} className="w-full bg-transparent text-2xl font-bold outline-none" />
                    <input value={section.title} onChange={(event) => patchSection({ title: event.target.value })} className="w-full bg-transparent text-lg font-semibold text-cyan-200 outline-none" />
                    {fields.map((field, index) => <FieldEditor key={field.id} field={field} index={index} patch={(values) => patchField(index, values)} patchValidation={(values) => patchValidation(index, values)} move={move} duplicate={() => updateFields([...fields.slice(0, index + 1), cloneField(field), ...fields.slice(index + 1)])} remove={() => updateFields(fields.filter((_, position) => position !== index))} onDragStart={() => setDragIndex(index)} onDrop={() => { if (dragIndex !== null) move(dragIndex, index); setDragIndex(null); }} />)}
                    {fields.length === 0 && <p className="py-16 text-center text-slate-400">Choose a field type to start building.</p>}
                </div>}
            </section>
        </div>
    </main>;
}

function FieldEditor({ field, index, patch, patchValidation, move, duplicate, remove, onDragStart, onDrop }) {
    const numeric = (value) => value === '' ? null : Number(value);
    return <article draggable onDragStart={onDragStart} onDragOver={(event) => event.preventDefault()} onDrop={onDrop} className="rounded-xl border border-slate-700 bg-slate-800 p-4">
        <div className="grid gap-3 md:grid-cols-2"><label>Label<input className={control} value={field.label} onChange={(event) => patch({ label: event.target.value })} /></label><label>Key<input className={control} value={field.key} onChange={(event) => patch({ key: event.target.value.toLowerCase().replace(/[^a-z0-9_]/g, '_') })} /></label><label>Placeholder<input className={control} value={field.placeholder ?? ''} onChange={(event) => patch({ placeholder: event.target.value || null })} /></label><label>Help text<input className={control} value={field.help_text ?? ''} onChange={(event) => patch({ help_text: event.target.value || null })} /></label></div>
        {['select', 'radio'].includes(field.type) && <label className="mt-3 block">Options (one “Label | value” per line)<textarea className={control} value={field.options.map((option) => `${option.label} | ${option.value}`).join('\n')} onChange={(event) => patch({ options: event.target.value.split('\n').filter(Boolean).map((line) => { const [label, value] = line.split('|').map((part) => part.trim()); return { label, value: value || label.toLowerCase().replace(/[^a-z0-9]+/g, '_') }; }) })} /></label>}
        <div className="mt-3 grid gap-3 md:grid-cols-4"><label>Min length<input type="number" className={control} value={field.validation.min_length ?? ''} onChange={(event) => patchValidation({ min_length: numeric(event.target.value) })} /></label><label>Max length<input type="number" className={control} value={field.validation.max_length ?? ''} onChange={(event) => patchValidation({ max_length: numeric(event.target.value) })} /></label><label>Minimum<input type="number" className={control} value={field.validation.min ?? ''} onChange={(event) => patchValidation({ min: numeric(event.target.value) })} /></label><label>Maximum<input type="number" className={control} value={field.validation.max ?? ''} onChange={(event) => patchValidation({ max: numeric(event.target.value) })} /></label></div>
        <div className="mt-3 flex flex-wrap gap-4 text-sm"><label><input type="checkbox" checked={field.required} disabled={field.type === 'heading'} onChange={(event) => patch({ required: event.target.checked })} /> Required</label><label><input type="checkbox" checked={field.validation.email} onChange={(event) => patchValidation({ email: event.target.checked })} /> Email</label><label><input type="checkbox" checked={field.validation.url} onChange={(event) => patchValidation({ url: event.target.checked })} /> URL</label><label><input type="checkbox" checked={field.validation.numeric} onChange={(event) => patchValidation({ numeric: event.target.checked })} /> Numeric</label><button onClick={() => move(index, index - 1)}>↑ Up</button><button onClick={() => move(index, index + 1)}>↓ Down</button><button onClick={duplicate}>Duplicate</button><button onClick={remove} className="text-rose-400">Delete</button><span className="ml-auto cursor-grab text-slate-400">Drag ↕ · {field.type}</span></div>
    </article>;
}

function Responses({ publicId, responses, search, setSearch, reload, close }) {
    return <div className="space-y-3 rounded-2xl border border-slate-800 bg-slate-900 p-5"><div className="flex flex-wrap justify-between gap-3"><h2 className="text-xl font-bold">Responses ({responses?.total ?? 0})</h2><div className="flex gap-2"><a href={`/api/forms/${publicId}/submissions/export`} className="rounded-lg bg-emerald-500 px-3 py-2 font-bold text-slate-950">Export CSV</a><button onClick={close} className="text-cyan-300">Back</button></div></div><form onSubmit={(event) => { event.preventDefault(); reload(search); }} className="flex gap-2"><input className={control} placeholder="Search responses" value={search} onChange={(event) => setSearch(event.target.value)} /><button className="rounded-lg bg-slate-700 px-4">Search</button></form>{responses?.data?.map((submission) => <pre key={submission.public_id} className="overflow-auto rounded-xl bg-slate-800 p-4 text-sm">{JSON.stringify(submission.data_json, null, 2)}</pre>)}{responses?.data?.length === 0 && <p className="text-slate-400">No responses found.</p>}</div>;
}

export function MandatoryPublicForm({ tenantSlug, formSlug }) {
    const [schema, setSchema] = useState(null); const [step, setStep] = useState(0); const [answers, setAnswers] = useState({}); const [message, setMessage] = useState(''); const [error, setError] = useState('');
    useEffect(() => { api(`/api/public/forms/${tenantSlug}/${formSlug}`).then((value) => setSchema(value.schema)).catch((issue) => setError(issue.message)); }, [tenantSlug, formSlug]);
    const current = schema?.steps[step];
    const total = schema?.steps.length ?? 0;
    async function submit() { setError(''); try { const path = `/api/public/forms/${tenantSlug}/${formSlug}`; const result = Object.values(answers).some((value) => value instanceof File) ? await multipartApi(path, answers) : await api(path, { method: 'POST', body: JSON.stringify({ answers }) }); setMessage(result.message); } catch (issue) { setError(issue.message); } }
    if (error && !schema) return <main className="grid min-h-screen place-items-center bg-slate-950 text-rose-300">{error}</main>;
    if (!schema) return <main className="grid min-h-screen place-items-center bg-slate-950 text-white">Loading…</main>;
    if (message) return <main className="grid min-h-screen place-items-center bg-slate-950 p-6 text-white"><div className="rounded-3xl bg-slate-900 p-10 text-center"><h1 className="text-3xl font-bold">Submitted</h1><p className="mt-3 text-slate-300">{message}</p></div></main>;
    return <main className="min-h-screen bg-slate-950 p-6 text-slate-100"><form onSubmit={(event) => { event.preventDefault(); if (step < total - 1) setStep(step + 1); else submit(); }} className="mx-auto max-w-2xl space-y-5 rounded-3xl border border-slate-800 bg-slate-900 p-8"><p className="text-sm font-black uppercase tracking-[.22em] text-cyan-400">FormForge AI</p><h1 className="text-4xl font-bold">{schema.form.title}</h1>{schema.settings.show_progress && <div className="h-2 rounded bg-slate-800"><div className="h-2 rounded bg-cyan-400" style={{ width: `${((step + 1) / total) * 100}%` }} /></div>}<h2 className="text-2xl font-bold">{current.title}</h2>{current.sections.map((section) => <section key={section.id} className="space-y-4"><h3 className="text-lg font-semibold text-cyan-200">{section.title}</h3>{section.fields.map((field) => <LiveField key={field.id} field={field} value={answers[field.key]} setValue={(value) => setAnswers({ ...answers, [field.key]: value })} />)}</section>)}{error && <p className="text-rose-400">{error}</p>}<div className="flex justify-between">{step > 0 ? <button type="button" onClick={() => setStep(step - 1)} className="rounded-xl border border-slate-700 px-4 py-3">Back</button> : <span />}<button className="rounded-xl bg-cyan-400 px-6 py-3 font-bold text-slate-950">{step < total - 1 ? 'Next' : schema.form.submit_label}</button></div></form></main>;
}

function LiveField({ field, value, setValue }) {
    if (field.type === 'heading') return <h4 className="pt-3 text-xl font-bold">{field.label}</h4>;
    const props = { required: field.required, minLength: field.validation.min_length ?? undefined, maxLength: field.validation.max_length ?? undefined, min: field.validation.min ?? undefined, max: field.validation.max ?? undefined, placeholder: field.placeholder ?? undefined, className: `${control} mt-2`, value: value ?? '', onChange: (event) => setValue(event.target.value) };
    return <label className="block"><span className="font-semibold">{field.label}{field.required && ' *'}</span>{field.help_text && <span className="ml-2 text-sm text-slate-400">{field.help_text}</span>}{field.type === 'textarea' ? <textarea {...props} /> : field.type === 'select' ? <select {...props}><option value="">Choose…</option>{field.options.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select> : field.type === 'radio' ? <span className="mt-2 block space-y-2">{field.options.map((option) => <label key={option.value} className="block"><input type="radio" name={field.key} required={field.required} checked={value === option.value} onChange={() => setValue(option.value)} /> {option.label}</label>)}</span> : field.type === 'checkbox' ? <input className="ml-3" type="checkbox" checked={Boolean(value)} onChange={(event) => setValue(event.target.checked)} /> : field.type === 'file' ? <input className={`${control} mt-2`} type="file" required={field.required} accept={field.validation.allowed_file_types.map((type) => type.includes('/') ? type : `.${type}`).join(',')} onChange={(event) => setValue(event.target.files[0])} /> : <input {...props} type={field.type === 'rating' || field.type === 'number' ? 'number' : field.type === 'phone' ? 'tel' : field.type} />}</label>;
}
