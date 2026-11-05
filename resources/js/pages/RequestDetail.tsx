import { useEffect, useState, type FormEvent } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { api, ApiError } from '../api';
import { useAuth } from '../auth';
import { AssigneeSelect } from '../components/AssigneeSelect';
import { ErrorNotice, Loading, StatusBadge } from '../components/Feedback';
import { dateTime, nextStatus, statusLabels, type ItemResponse, type Status, type Ticket } from '../types';
import { useApiQuery } from '../useApiQuery';

export function RequestDetail() {
  const { id } = useParams();
  const { user } = useAuth();
  const location = useLocation();
  const query = useApiQuery<ItemResponse<Ticket>>(`/api/requests/${id}`);
  const ticket = query.data?.data;
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);
  const [success, setSuccess] = useState(location.state?.created ? 'Заявка создана.' : '');
  const [assignee, setAssignee] = useState('');
  useEffect(() => { setAssignee(ticket?.assignee_id ? String(ticket.assignee_id) : ''); }, [ticket?.assignee_id, ticket?.version]);
  const conflict = error?.status === 409;
  const listUrl = location.state?.listUrl ?? '/requests';
  function refresh() { setError(null); setSuccess(''); query.reload(); }
  async function update(action: 'status' | 'assignee', fields: object) {
    if (!ticket || busy || conflict) return;
    setBusy(true); setError(null); setSuccess('');
    try {
      const result = await api<ItemResponse<Ticket>>(`/api/requests/${ticket.id}/${action}`, { method: 'PATCH', body: JSON.stringify({ ...fields, version: ticket.version }) });
      query.setData(result);
      setSuccess(action === 'status' ? 'Статус обновлён.' : 'Исполнитель обновлён.');
    } catch (failure) { setError(failure as ApiError); }
    finally { setBusy(false); }
  }
  function assign(event: FormEvent) { event.preventDefault(); void update('assignee', { assignee_id: assignee ? Number(assignee) : null }); }
  if (query.loading) return <><Link className="back-link" to={listUrl}>← К списку заявок</Link><Loading label="Загружаем заявку…" /></>;
  if (query.error) return <><Link className="back-link" to={listUrl}>← К списку заявок</Link><h1>Не удалось открыть заявку</h1><ErrorNotice error={query.error} onRetry={refresh} /></>;
  if (!ticket) return null;
  const next = nextStatus[ticket.status];
  const completed = ticket.status === 'done';
  return <>
    <Link className="back-link" to={listUrl}>← К списку заявок</Link>
    <div className="detail-heading"><div className="detail-id">Заявка № {ticket.id}</div><h1>{ticket.title}</h1></div>
    <ErrorNotice error={error} />
    {conflict && <button className="secondary conflict-reload" onClick={refresh}>Загрузить актуальную заявку</button>}
    {success && <div className="notice notice-success" role="status">{success}</div>}
    <div className="detail-grid">
      <section className="surface description-panel" aria-labelledby="description-heading"><h2 id="description-heading">Описание</h2><p className="description-text">{ticket.description}</p><div className="request-dates"><div><span>Создана</span><time dateTime={ticket.created_at}>{dateTime(ticket.created_at)}</time></div><div><span>Последнее изменение</span><time dateTime={ticket.updated_at}>{dateTime(ticket.updated_at)}</time></div></div></section>
      <aside className="surface action-panel" aria-label="Состояние и действия"><h2>Состояние заявки</h2><StatusBadge status={ticket.status} />
        <ol className="status-flow">{(['new', 'in_progress', 'done'] as Status[]).map((status) => <li key={status} className={status === ticket.status ? 'current' : ''} aria-current={status === ticket.status ? 'step' : undefined}>{statusLabels[status]}</li>)}</ol>
        {completed ? <p className="completed-note">Заявка завершена. Все изменения закрыты.</p> : <div className="status-action"><button className="primary full-width" disabled={busy || conflict} onClick={() => void update('status', { status: next })}>{busy ? 'Сохраняем…' : next === 'in_progress' ? 'Взять в работу' : 'Завершить заявку'}</button><p className="field-hint">{next === 'in_progress' ? 'Статус изменится на «В работе».' : 'После завершения заявку нельзя будет изменить.'}</p></div>}
        <div className="assignment"><h2>Ответственный</h2><p className="assignee-name">{ticket.assignee?.name ?? 'Исполнитель не назначен'}</p>{ticket.assignee && <p className="muted assignee-email">{ticket.assignee.email}</p>}
          {user?.role === 'admin' && !completed && <form onSubmit={assign} aria-busy={busy}><AssigneeSelect value={assignee} onChange={setAssignee} disabled={busy || conflict} /><button className="secondary full-width" disabled={busy || conflict || assignee === String(ticket.assignee_id ?? '')}>Сохранить исполнителя</button></form>}
        </div>
      </aside>
    </div>
  </>;
}
