import { useState, type FormEvent } from 'react';
import { Link, Navigate, useNavigate } from 'react-router-dom';
import { api, ApiError } from '../api';
import { useAuth } from '../auth';
import { AssigneeSelect } from '../components/AssigneeSelect';
import { ErrorNotice, FieldError } from '../components/Feedback';
import type { ItemResponse, Ticket } from '../types';

export function NewRequest() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [assignee, setAssignee] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);
  if (user?.role !== 'admin') return <Navigate to="/requests" replace />;
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy) return;
    setBusy(true); setError(null);
    const form = new FormData(event.currentTarget);
    try {
      const result = await api<ItemResponse<Ticket>>('/api/requests', { method: 'POST', body: JSON.stringify({
        title: form.get('title'), description: form.get('description'), assignee_id: assignee ? Number(assignee) : null,
      }) });
      navigate(`/requests/${result.data.id}`, { replace: true, state: { created: true } });
    } catch (failure) { setError(failure as ApiError); }
    finally { setBusy(false); }
  }
  return <>
    <Link className="back-link" to="/requests">← Все заявки</Link>
    <div className="page-heading"><div><h1>Новая заявка</h1><p className="muted">Опишите задачу и выберите, кто будет над ней работать.</p></div></div>
    <div className="create-layout"><form className="surface request-form" onSubmit={submit} aria-busy={busy}>
      <ErrorNotice error={error} />
      <label htmlFor="title">Название <span className="muted">обязательно</span></label><input disabled={busy} autoFocus id="title" name="title" required maxLength={200} aria-invalid={Boolean(error?.errors.title)} aria-describedby={error?.errors.title ? 'title-error' : undefined} /><FieldError name="title" error={error} />
      <label htmlFor="description">Описание <span className="muted">обязательно</span></label><textarea disabled={busy} id="description" name="description" required maxLength={10000} rows={7} aria-invalid={Boolean(error?.errors.description)} aria-describedby={error?.errors.description ? 'description-error' : undefined} /><FieldError name="description" error={error} />
      <AssigneeSelect value={assignee} onChange={setAssignee} disabled={busy} /><FieldError name="assignee_id" error={error} />
      <p className="field-hint">Исполнителя можно назначить позже.</p>
      <div className="form-actions"><button className="primary" disabled={busy}>{busy ? 'Создаём…' : 'Создать заявку'}</button><Link className="button secondary" to="/requests">Отмена</Link></div>
    </form><aside className="create-note"><h2>Понятная задача —<br />быстрый результат</h2><p>В описании укажите, что нужно сделать, где возникает проблема и какой результат ожидается.</p><p>Новая заявка появится в общем списке. После назначения её увидит исполнитель.</p></aside></div>
  </>;
}
