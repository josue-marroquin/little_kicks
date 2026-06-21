function createId() {
  if (typeof globalThis.crypto?.randomUUID === 'function') {
    return globalThis.crypto.randomUUID();
  }

  return 'session-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2);
}

export function createSession(now = new Date(), id = createId()) {
  return { id, startedAt: now.toISOString(), endedAt: null, movements: [], notes: '' };
}

export function addMovement(session, now = new Date()) {
  return { ...session, movements: [...session.movements, now.toISOString()] };
}

export function undoMovement(session) {
  return { ...session, movements: session.movements.slice(0, -1) };
}

export function finishSession(session, notes = '', now = new Date()) {
  return { ...session, endedAt: now.toISOString(), notes: notes.trim().slice(0, 500) };
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
  return hours > 0 ? String(hours).padStart(2, '0') + ':' + parts.join(':') : parts.join(':');
}

async function request(apiUrl, options = {}) {
  const response = await fetch(apiUrl, {
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload?.ok) {
    throw new Error(payload?.error || 'No se pudo completar la operación.');
  }
  return payload.data;
}

export function loadHistory(apiUrl) {
  return request(apiUrl);
}

export function saveSession(apiUrl, session) {
  return request(apiUrl, { method: 'POST', body: JSON.stringify(session) });
}
