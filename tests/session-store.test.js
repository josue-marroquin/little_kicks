import test from 'node:test';
import assert from 'node:assert/strict';
import {
  addMovement,
  createSession,
  elapsedSeconds,
  finishSession,
  formatDuration,
  prependHistory,
  undoMovement,
} from '../src/session-store.js';

test('registra movimientos consecutivos sin perder eventos', () => {
  let session = createSession(new Date('2026-06-19T10:00:00Z'), 'session-1');

  for (let second = 1; second <= 20; second += 1) {
    session = addMovement(session, new Date(`2026-06-19T10:00:${String(second).padStart(2, '0')}Z`));
  }

  assert.equal(session.movements.length, 20);
  assert.equal(new Set(session.movements).size, 20);
});

test('deshacer elimina solamente el último movimiento', () => {
  const initial = createSession(new Date('2026-06-19T10:00:00Z'), 'session-2');
  const first = addMovement(initial, new Date('2026-06-19T10:00:01Z'));
  const second = addMovement(first, new Date('2026-06-19T10:00:02Z'));

  assert.deepEqual(undoMovement(second).movements, first.movements);
  assert.deepEqual(undoMovement(initial).movements, []);
});

test('finaliza la sesión, limita notas y calcula duración', () => {
  const session = createSession(new Date('2026-06-19T10:00:00Z'), 'session-3');
  const finished = finishSession(session, `  ${'a'.repeat(510)}  `, new Date('2026-06-19T10:02:05Z'));

  assert.equal(finished.notes.length, 500);
  assert.equal(elapsedSeconds(finished), 125);
  assert.equal(formatDuration(125), '02:05');
  assert.equal(formatDuration(3661), '01:01:01');
});

test('guarda el historial sin duplicar una sesión', () => {
  const values = new Map();
  const storage = { setItem: (key, value) => values.set(key, value) };
  const oldSession = { id: 'old' };
  const updatedSession = { id: 'old', notes: 'actualizada' };

  const history = prependHistory(storage, [oldSession], updatedSession);

  assert.deepEqual(history, [updatedSession]);
  assert.equal(JSON.parse([...values.values()][0]).length, 1);
});
