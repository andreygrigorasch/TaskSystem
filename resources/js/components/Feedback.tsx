import { ApiError } from '../api';
import { statusLabels, type Status } from '../types';

export function ErrorNotice({ error, onRetry }: { error: ApiError | null; onRetry?: () => void }) {
  if (!error) return null;
  return <div className="notice notice-error" role="alert">
    <p>{error.message}</p>
    {error.status === 422 && Object.entries(error.errors).map(([key, messages]) => <p key={key}>{messages.join(' ')}</p>)}
    {onRetry && <button className="secondary" onClick={onRetry}>Повторить загрузку</button>}
  </div>;
}

export function FieldError({ name, error }: { name: string; error: ApiError | null }) {
  return error?.errors[name] ? <span id={`${name}-error`} className="field-error">{error.errors[name].join(' ')}</span> : null;
}

export function Loading({ label = 'Загружаем заявки…' }: { label?: string }) {
  return <div className="loading" role="status"><span className="spinner" aria-hidden="true" />{label}</div>;
}

export function StatusBadge({ status }: { status: Status }) {
  return <span className={`badge badge-${status}`}><span aria-hidden="true" />{statusLabels[status]}</span>;
}
