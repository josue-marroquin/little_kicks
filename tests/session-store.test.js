import test from 'node:test';
import assert from 'node:assert/strict';
import {
  addMovement,
  createSession,
  elapsedSeconds,
  finishSession,
  formatDuration,
  loadHistory,
  saveSession,
  undoMovement,
} from '../src/session-store.js';

test('mantiene una sola sesión activa en memoria', () => {
  let session = createSession(new Date('2026-06-19T10:00:00Z'), 'session-1');
  session = addMovement(session, new Date('2026-06-19T10:00:01Z'));
  session = addMovement(session, new Date('2026-06-19T10:00:02Z'));
  assert.equal(session.movements.length, 2);
  assert.deepEqual(undoMovement(session).movements, ['2026-06-19T10:00:01.000Z']);
});

test('genera un identificador aunque randomUUID no esté disponible', () => {
  const session = createSession(new Date('2026-06-19T10:00:00Z'));
  assert.match(session.id, /^[a-zA-Z0-9-]{1,64}$/);
});

test('finaliza la sesión completa para enviarla una sola vez', () => {
  const session = createSession(new Date('2026-06-19T10:00:00Z'), 'session-2');
  const finished = finishSession(session, '  nota  ', new Date('2026-06-19T10:02:05Z'));
  assert.equal(finished.notes, 'nota');
  assert.equal(finished.endedAt, '2026-06-19T10:02:05.000Z');
  assert.equal(elapsedSeconds(finished), 125);
  assert.equal(formatDuration(125), '02:05');
});

test('usa un único endpoint para guardar y leer historial', async () => {
  const originalFetch = global.fetch;
  const calls = [];
  global.fetch = async (url, options = {}) => {
    calls.push({ url, options });
    return { ok: true, json: async () => ({ ok: true, data: [] }) };
  };
  try {
    await loadHistory('/sessions.php');
    await saveSession('/sessions.php', { id: 'session-3' });
    assert.deepEqual(calls.map((call) => call.url), ['/sessions.php', '/sessions.php']);
    assert.equal(calls[0].options.method, undefined);
    assert.equal(calls[1].options.method, 'POST');
  } finally {
    global.fetch = originalFetch;
  }
});
