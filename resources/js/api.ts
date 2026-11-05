export class ApiError extends Error {
  constructor(public status: number, message: string, public errors: Record<string, string[]> = {}) {
    super(message);
  }
}

export async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
  const method = options.method ?? 'GET';
  if (!['GET', 'HEAD'].includes(method) && !document.cookie.split('; ').some((item) => item.startsWith('XSRF-TOKEN='))) {
    await api<void>('/api/csrf-cookie');
  }
  const csrf = document.cookie.split('; ').find((item) => item.startsWith('XSRF-TOKEN='))?.slice(11);
  let response: Response;
  try {
    response = await fetch(path, {
      ...options,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...(csrf ? { 'X-XSRF-TOKEN': decodeURIComponent(csrf) } : {}),
        ...options.headers,
      },
    });
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') throw error;
    throw new ApiError(0, 'Не удалось связаться с сервером. Проверьте подключение и повторите попытку.');
  }
  if (response.status === 204) return undefined as T;
  const body = await response.json().catch(() => ({}));
  if (!response.ok) {
    if (response.status === 401) window.dispatchEvent(new Event('auth:expired'));
    const messages: Record<number, string> = {
      401: 'Сессия завершена. Войдите снова.',
      403: 'У вас нет доступа к этой заявке или действию.',
      404: 'Заявка не найдена.',
      409: 'Заявка изменилась в другой вкладке или у другого пользователя. Загрузите актуальные данные перед повторным действием.',
      419: 'Сессия устарела. Обновите страницу и повторите действие.',
      422: 'Проверьте данные формы.',
      429: 'Слишком много попыток. Повторите через минуту.',
    };
    throw new ApiError(response.status, messages[response.status] ?? 'Ошибка сервера. Повторите попытку позже.', body.errors ?? {});
  }
  return body as T;
}
