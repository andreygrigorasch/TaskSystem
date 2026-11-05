import { Link, useSearchParams } from 'react-router-dom';
import { useAuth } from '../auth';
import { ErrorNotice, Loading, StatusBadge } from '../components/Feedback';
import { useApiQuery } from '../useApiQuery';
import { dateTime, type Status, type TicketPage } from '../types';

const filters: { value: string; label: string }[] = [
  { value: '', label: 'Все заявки' }, { value: 'new', label: 'Новые' },
  { value: 'in_progress', label: 'В работе' }, { value: 'done', label: 'Завершённые' },
];

export function RequestList() {
  const { user } = useAuth();
  const [params, setParams] = useSearchParams();
  const status = ['new', 'in_progress', 'done'].includes(params.get('status') ?? '') ? params.get('status') as Status : '';
  const page = Math.max(1, Number.parseInt(params.get('page') ?? '1', 10) || 1);
  const query = useApiQuery<TicketPage>(`/api/requests?page=${page}${status ? `&status=${status}` : ''}`);
  function navigate(nextPage: number, nextStatus = status) {
    setParams({ ...(nextStatus ? { status: nextStatus } : {}), ...(nextPage > 1 ? { page: String(nextPage) } : {}) });
  }
  return <>
    <div className="page-heading"><div><h1>{user?.role === 'admin' ? 'Все заявки' : 'Мои заявки'}</h1><p className="muted">{user?.role === 'admin' ? 'Распределяйте работу и отслеживайте выполнение.' : 'Заявки, назначенные вам. Берите в работу и отмечайте результат.'}</p></div>{user?.role === 'admin' && <Link className="button primary" to="/requests/new"><span aria-hidden="true">＋</span> Создать заявку</Link>}</div>
    <section className="list-panel" aria-label="Список заявок">
      <div className="list-toolbar"><div className="filters" role="group" aria-label="Фильтр по статусу">{filters.map((filter) => <button key={filter.value} aria-pressed={filter.value === status} className={filter.value === status ? 'filter active' : 'filter'} onClick={() => navigate(1, filter.value as Status | '')}>{filter.label}</button>)}</div><button className="text-button" disabled={query.loading} onClick={query.reload}>Обновить</button></div>
      {query.loading && <Loading />}
      <ErrorNotice error={query.error} onRetry={query.reload} />
      {query.data && <>
        {query.data.data.length === 0 ? <div className="empty-state" role="status"><span className="empty-symbol" aria-hidden="true">✓</span><h2>{page > 1 ? 'На этой странице нет заявок' : status ? 'Заявок с таким статусом нет' : 'Здесь пока нет заявок'}</h2><p>{page > 1 ? 'Вернитесь к первой странице списка.' : status ? 'Выберите другой статус или вернитесь ко всем заявкам.' : user?.role === 'admin' ? 'Создайте первую заявку и назначьте ответственного.' : 'Когда администратор назначит вам заявку, она появится здесь.'}</p>{(status || page > 1) && <button className="secondary" onClick={() => navigate(1, '')}>Показать все заявки</button>}</div> : <div className="table-scroll"><table><caption className="sr-only">Доступные заявки, отсортированные от новых к старым</caption><thead><tr><th scope="col">Заявка</th><th scope="col">Статус</th><th scope="col">Исполнитель</th><th scope="col">Создана</th></tr></thead><tbody>{query.data.data.map((ticket) => <tr key={ticket.id}><td className="ticket-cell"><span className="ticket-id">№ {ticket.id}</span><Link to={`/requests/${ticket.id}`} state={{ listUrl: `/requests${params.size ? `?${params}` : ''}` }}>{ticket.title}</Link></td><td><StatusBadge status={ticket.status} /></td><td>{ticket.assignee?.name ?? <span className="muted">Не назначен</span>}</td><td className="date-cell">{dateTime(ticket.created_at)}</td></tr>)}</tbody></table></div>}
        <div className="pagination"><p role="status">{query.data.meta.from !== null ? `${query.data.meta.from}–${query.data.meta.to} из ${query.data.meta.total}` : `Всего заявок: ${query.data.meta.total}`}</p><nav aria-label="Страницы списка"><button className="secondary" onClick={() => navigate(Math.min(page - 1, query.data!.meta.last_page))} disabled={page <= 1}>Назад</button><span>{page > query.data.meta.last_page ? 'Нет такой страницы' : `Страница ${query.data.meta.current_page} из ${query.data.meta.last_page}`} </span><button className="secondary" onClick={() => navigate(page + 1)} disabled={page >= query.data.meta.last_page}>Далее</button></nav></div>
      </>}
    </section>
    <p className="list-note"><span aria-hidden="true">↳</span> Откройте заявку, чтобы посмотреть описание и доступные действия.</p>
  </>;
}
