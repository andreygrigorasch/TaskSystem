export type Status = 'new' | 'in_progress' | 'done';
export interface Person { id: number; name: string; email: string }
export interface User extends Person { role: 'admin' | 'employee' }
export interface Ticket {
  id: number;
  title: string;
  description: string;
  status: Status;
  assignee_id: number | null;
  assignee: Person | null;
  version: number;
  created_at: string;
  updated_at: string;
}
export interface ItemResponse<T> { data: T }
export interface TicketPage {
  data: Ticket[];
  meta: { current_page: number; last_page: number; total: number; from: number | null; to: number | null };
}
export const statusLabels: Record<Status, string> = { new: 'Новая', in_progress: 'В работе', done: 'Завершена' };
export const nextStatus: Record<Status, Status | null> = { new: 'in_progress', in_progress: 'done', done: null };
export const dateTime = (value: string) => new Intl.DateTimeFormat('ru-RU', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
