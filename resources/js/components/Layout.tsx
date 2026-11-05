import { useState } from 'react';
import { Link, Outlet } from 'react-router-dom';
import { api, ApiError } from '../api';
import { useAuth } from '../auth';
import { ErrorNotice } from './Feedback';

export function Layout() {
  const { user, setUser } = useAuth();
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);
  async function logout() {
    setBusy(true); setError(null);
    try { await api('/api/logout', { method: 'POST' }); setUser(null); }
    catch (failure) { setError(failure as ApiError); }
    finally { setBusy(false); }
  }
  return <>
    <a className="skip-link" href="#main">К содержимому</a>
    <header className="app-header"><div className="header-inner">
      <Link className="brand" to="/requests"><span className="brand-mark" aria-hidden="true">з</span>Заявки<span className="brand-note">Рабочее пространство</span></Link>
      <div className="account"><div><strong>{user?.name}</strong><span>{user?.role === 'admin' ? 'Администратор' : 'Сотрудник'}</span></div><button className="secondary" onClick={logout} disabled={busy}>{busy ? 'Выходим…' : 'Выйти'}</button></div>
    </div></header>
    <main id="main" className="container" tabIndex={-1}><ErrorNotice error={error} /><Outlet /></main>
    <footer className="app-footer">Заявки сотрудников<span>Все изменения сохраняются в общем рабочем пространстве</span></footer>
  </>;
}
