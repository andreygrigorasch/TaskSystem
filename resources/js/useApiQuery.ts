import { useCallback, useEffect, useState } from 'react';
import { api, ApiError } from './api';

interface QueryState<T> { path: string; data: T | null; loading: boolean; error: ApiError | null }

export function useApiQuery<T>(path: string) {
  const [state, setState] = useState<QueryState<T>>({ path, data: null, loading: true, error: null });
  const [revision, setRevision] = useState(0);
  const reload = useCallback(() => setRevision((value) => value + 1), []);
  const setData = useCallback((data: T) => setState({ path, data, loading: false, error: null }), [path]);
  useEffect(() => {
    const controller = new AbortController();
    let active = true;
    setState({ path, data: null, loading: true, error: null });
    api<T>(path, { signal: controller.signal })
      .then((data) => { if (active) setState({ path, data, loading: false, error: null }); })
      .catch((error) => { if (active) setState({ path, data: null, loading: false, error }); });
    return () => { active = false; controller.abort(); };
  }, [path, revision]);
  // Never render data from a previous URL while the new request starts.
  return { ...(state.path === path ? state : { data: null, loading: true, error: null }), reload, setData };
}
