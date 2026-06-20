export const STORAGE_KEYS = Object.freeze({
  active: 'kicks.active-session.v1',
  history: 'kicks.session-history.v1',
});

const API_ROOT = (typeof window !== 'undefined' && window.KICKS_API_ROOT) || '/api';

function getCookie(name) {
  if (typeof document === 'undefined') return null;
  const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  return match ? decodeURIComponent(match[2]) : null;
}

async function ensureCsrf() {
  if (getCookie('XSRF-TOKEN')) return;
  try {
    await fetch(`${API_ROOT}/csrf`, { credentials: 'same-origin' });
  } catch (e) {
    // ignore
  }
}

async function postJson(path, body) {
  try {
    await ensureCsrf();
    const csrf = getCookie('XSRF-TOKEN');
    const headers = { 'Content-Type': 'application/json' };
    if (csrf) headers['X-XSRF-TOKEN'] = csrf;

    const res = await fetch(path, {
      method: 'POST',
      headers,
      credentials: 'same-origin',
      body: JSON.stringify(body),
    });
    return res.json().catch(() => null);
  } catch (err) {
    return null;
  }
}

export function createSession(now = new Date(), id = crypto.randomUUID()) {
  return {
    id,
    startedAt: now.toISOString(),
    endedAt: null,
    movements: [],
    notes: '',
  };
}

export function createSessionAndPersist(now = new Date(), id = crypto.randomUUID()) {
  const session = createSession(now, id);
  // persist locally
  saveActive(localStorage, session);
  // persist to server in background
  postJson(`${API_ROOT}/sessions`, { id: session.id, startedAt: session.startedAt });
  return session;
}

export function addMovement(session, now = new Date()) {
  return {
    ...session,
    movements: [...session.movements, now.toISOString()],
  };
}

export function addMovementAndPersist(session, now = new Date()) {
  const next = addMovement(session, now);
  // update local copy
  saveActive(localStorage, next);
  // send to server in background
  postJson(`${API_ROOT}/sessions/${encodeURIComponent(next.id)}/movements`, { occurredAt: new Date().toISOString() });
  return next;
}

export function undoMovement(session) {
  return {
    ...session,
    movements: session.movements.slice(0, -1),
  };
}

export function finishSession(session, notes = '', now = new Date()) {
  return {
    ...session,
    endedAt: now.toISOString(),
    notes: notes.trim().slice(0, 500),
  };
}

export function finishSessionAndPersist(session, notes = '', now = new Date()) {
  const finished = finishSession(session, notes, now);
  // persist locally to history
  const history = loadHistory(localStorage);
  prependHistory(localStorage, history, finished);
  clearActive(localStorage);
  // notify server
  postJson(`${API_ROOT}/sessions/${encodeURIComponent(finished.id)}`, { action: 'finish', endedAt: finished.endedAt, notes: finished.notes });
  return finished;
}

export function elapsedSeconds(session, now = new Date()) {
  const end = session.endedAt ? new Date(session.endedAt) : now;
  return Math.max(0, Math.floor((end.getTime() - new Date(session.startedAt).getTime()) / 1000));
}

export function formatDuration(totalSeconds) {
  const hours = Math.floor(totalSeconds / 3600);
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  const seconds = totalSeconds % 60;
  const parts = [minutes, seconds].map((value) => String(value).padStart(2, '0'));
  return hours > 0 ? `${String(hours).padStart(2, '0')}:${parts.join(':')}` : parts.join(':');
}

function safelyParse(value, fallback) {
  if (!value) return fallback;

  try {
    return JSON.parse(value);
  } catch {
    return fallback;
  }
}

export function loadActive(storage) {
  const value = safelyParse(storage.getItem(STORAGE_KEYS.active), null);
  return value && Array.isArray(value.movements) ? value : null;
}

export function saveActive(storage, session) {
  storage.setItem(STORAGE_KEYS.active, JSON.stringify(session));
}

export function saveActiveAndPersist(storage, session) {
  saveActive(storage, session);
  // best-effort server sync: if session is new, create it; otherwise add movements
  postJson(`${API_ROOT}/sessions`, { id: session.id, startedAt: session.startedAt });
}

export function clearActive(storage) {
  storage.removeItem(STORAGE_KEYS.active);
}

export function loadHistory(storage) {
  const value = safelyParse(storage.getItem(STORAGE_KEYS.history), []);
  return Array.isArray(value) ? value : [];
}

export function prependHistory(storage, history, session) {
  const next = [session, ...history.filter((item) => item.id !== session.id)];
  storage.setItem(STORAGE_KEYS.history, JSON.stringify(next));
  return next;
}
