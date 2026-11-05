import { useApiQuery } from '../useApiQuery';
import type { ItemResponse, Person } from '../types';
import { ErrorNotice } from './Feedback';

export function AssigneeSelect({ value, onChange, disabled = false }: { value: string; onChange: (value: string) => void; disabled?: boolean }) {
  const query = useApiQuery<ItemResponse<Person[]>>('/api/employees');
  return <>
    <label htmlFor="assignee_id">Исполнитель</label>
    <select id="assignee_id" name="assignee_id" value={value} onChange={(event) => onChange(event.target.value)} disabled={disabled || query.loading || Boolean(query.error)}>
      <option value="">{query.loading ? 'Загружаем сотрудников…' : 'Не назначен'}</option>
      {query.data?.data.map((person) => <option key={person.id} value={person.id}>{person.name}</option>)}
    </select>
    <ErrorNotice error={query.error} onRetry={query.reload} />
  </>;
}
