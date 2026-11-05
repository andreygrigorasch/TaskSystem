import { StrictMode, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Navigate, Outlet, Route, Routes, useLocation } from 'react-router-dom';
import { AuthProvider, useAuth } from './auth';
import { Layout } from './components/Layout';
import { Login } from './pages/Login';
import { RequestList } from './pages/RequestList';
import { NewRequest } from './pages/NewRequest';
import { RequestDetail } from './pages/RequestDetail';
import '../css/app.css';

function ProtectedRoutes() {
  const { user } = useAuth();
  const location = useLocation();
  return user ? <Outlet /> : <Navigate to="/login" replace state={{ from: location.pathname + location.search }} />;
}

function App() {
  const location = useLocation();
  useEffect(() => { document.getElementById('main')?.focus(); }, [location.pathname]);
  return <Routes>
    <Route path="/login" element={<Login />} />
    <Route element={<ProtectedRoutes />}><Route element={<Layout />}>
      <Route path="/requests" element={<RequestList />} />
      <Route path="/requests/new" element={<NewRequest />} />
      <Route path="/requests/:id" element={<RequestDetail />} />
      <Route path="*" element={<Navigate to="/requests" replace />} />
    </Route></Route>
  </Routes>;
}

createRoot(document.getElementById('root')!).render(<StrictMode><BrowserRouter><AuthProvider><App /></AuthProvider></BrowserRouter></StrictMode>);
