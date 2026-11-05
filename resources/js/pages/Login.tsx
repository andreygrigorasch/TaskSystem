import { useState, type FormEvent } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { api, ApiError } from '../api';
import { useAuth } from '../auth';
import { ErrorNotice, FieldError } from '../components/Feedback';
import type { ItemResponse, User } from '../types';

export function Login() {
  const { user, setUser } = useAuth();
  const location = useLocation();
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);
  const returnTo = location.state?.from ?? '/requests';
  if (user) return <Navigate to={returnTo} replace />;
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy) return;
    setBusy(true); setError(null);
    const data = new FormData(event.currentTarget);
    try {
      const result = await api<ItemResponse<User>>('/api/login', { method: 'POST', body: JSON.stringify({ email: data.get('email'), password: data.get('password') }) });
      setUser(result.data);
    } catch (failure) { setError(failure as ApiError); }
    finally { setBusy(false); }
  }
  return <main className="login-layout">
    <section className="login-intro"><div className="brand"><span className="brand-mark" aria-hidden="true">з</span>Заявки</div><div><p className="intro-kicker">Одно рабочее пространство</p><h1>От заявки<br />к результату.</h1><p>Назначайте ответственных, следите за работой<br className="desktop-only" /> и завершайте задачи команды.</p><ol className="intro-flow"><li>Новая</li><li>В работе</li><li>Завершена</li></ol></div><span className="intro-footnote">Сервис заявок сотрудников</span></section>
    <section className="login-panel" aria-labelledby="login-title"><div className="login-form"><h2 id="login-title">Вход в систему</h2><p className="muted">Используйте учётную запись сотрудника.</p>
      <form onSubmit={submit} aria-busy={busy}>
        <ErrorNotice error={error} />
        <label htmlFor="email">Электронная почта</label><input disabled={busy} autoFocus id="email" name="email" type="email" autoComplete="username" required maxLength={255} aria-invalid={Boolean(error?.errors.email)} aria-describedby={error?.errors.email ? 'email-error' : undefined} /><FieldError name="email" error={error} />
        <label htmlFor="password">Пароль</label><input disabled={busy} id="password" name="password" type="password" autoComplete="current-password" required maxLength={255} />
        <button className="primary full-width" disabled={busy}>{busy ? 'Входим…' : 'Войти'}</button>
      </form>
      <div className="login-help"><strong>Нет учётной записи?</strong><p>Обратитесь к администратору вашей команды.</p></div>
    </div></section>
  </main>;
}
