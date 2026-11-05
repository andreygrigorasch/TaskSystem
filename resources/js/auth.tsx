import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';
import { api, ApiError } from './api';
import type { ItemResponse, User } from './types';
import { ErrorNotice, Loading } from './components/Feedback';

const AuthContext = createContext<{ user: User | null; setUser: (user: User | null) => void } | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [ready, setReady] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);
  const [attempt, setAttempt] = useState(0);
  useEffect(() => {
    let active = true;
    const controller = new AbortController();
    api<ItemResponse<User>>('/api/user', { signal: controller.signal }).then((result) => { if (active) setUser(result.data); })
      .catch((failure: ApiError) => { if (active && failure.status !== 401) setError(failure); })
      .finally(() => { if (active) setReady(true); });
    return () => { active = false; controller.abort(); };
  }, [attempt]);
  useEffect(() => {
    const expire = () => setUser(null);
    window.addEventListener('auth:expired', expire);
    return () => window.removeEventListener('auth:expired', expire);
  }, []);
  if (!ready) return <main className="initial-state"><Loading label="Проверяем сессию…" /></main>;
  if (error) return <main className="initial-state"><ErrorNotice error={error} /><button onClick={() => { setError(null); setReady(false); setAttempt((value) => value + 1); }}>Повторить</button></main>;
  return <AuthContext.Provider value={{ user, setUser }}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const auth = useContext(AuthContext);
  if (!auth) throw new Error('AuthProvider is required');
  return auth;
}
