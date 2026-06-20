import {
  addMovement,
  clearActive,
  createSession,
  elapsedSeconds,
  finishSession,
  formatDuration,
  loadActive,
  loadHistory,
  prependHistory,
  saveActive,
  undoMovement,
} from './session-store.js';

const elements = {
  counterView: document.querySelector('#counter-view'),
  historyView: document.querySelector('#history-view'),
  emptyState: document.querySelector('#empty-state'),
  activeState: document.querySelector('#active-state'),
  start: document.querySelector('#start-session'),
  kick: document.querySelector('#record-kick'),
  undo: document.querySelector('#undo-kick'),
  finish: document.querySelector('#finish-session'),
  count: document.querySelector('#kick-count'),
  timer: document.querySelector('#timer'),
  note: document.querySelector('#session-note'),
  historyList: document.querySelector('#history-list'),
  historyEmpty: document.querySelector('#history-empty'),
  status: document.querySelector('#status-message'),
  tabs: [...document.querySelectorAll('[data-view]')],
};

let activeSession = loadActive(localStorage);
let history = loadHistory(localStorage);
let timerId = null;

function announce(message) {
  elements.status.textContent = message;
}

function updateTimer() {
  if (!activeSession) return;
  elements.timer.textContent = formatDuration(elapsedSeconds(activeSession));
}

function renderCounter() {
  const isActive = Boolean(activeSession);
  elements.emptyState.hidden = isActive;
  elements.activeState.hidden = !isActive;

  window.clearInterval(timerId);
  timerId = null;

  if (!activeSession) return;

  elements.count.textContent = String(activeSession.movements.length);
  elements.undo.disabled = activeSession.movements.length === 0;
  elements.finish.disabled = activeSession.movements.length === 0;
  elements.note.value = activeSession.notes ?? '';
  updateTimer();
  timerId = window.setInterval(updateTimer, 1000);
}

function formatSessionDate(value) {
  return new Intl.DateTimeFormat('es-GT', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    hour: 'numeric',
    minute: '2-digit',
  }).format(new Date(value));
}

function renderHistory() {
  elements.historyEmpty.hidden = history.length > 0;
  elements.historyList.replaceChildren();

  for (const session of history) {
    const item = document.createElement('article');
    item.className = 'history-card';

    const heading = document.createElement('div');
    heading.className = 'history-card__heading';

    const date = document.createElement('h3');
    date.textContent = formatSessionDate(session.startedAt);

    const count = document.createElement('strong');
    count.textContent = `${session.movements.length} ${session.movements.length === 1 ? 'patada' : 'patadas'}`;

    const duration = document.createElement('p');
    duration.textContent = `Duración: ${formatDuration(elapsedSeconds(session))}`;

    heading.append(date, count);
    item.append(heading, duration);

    if (session.notes) {
      const note = document.createElement('p');
      note.className = 'history-card__note';
      note.textContent = session.notes;
      item.append(note);
    }

    elements.historyList.append(item);
  }
}

function switchView(viewName) {
  const showHistory = viewName === 'history';
  elements.counterView.hidden = showHistory;
  elements.historyView.hidden = !showHistory;

  for (const tab of elements.tabs) {
    const selected = tab.dataset.view === viewName;
    tab.classList.toggle('nav__button--active', selected);
    tab.setAttribute('aria-current', selected ? 'page' : 'false');
  }

  if (showHistory) renderHistory();
}

elements.start.addEventListener('click', () => {
  activeSession = createSessionAndPersist();
  renderCounter();
  elements.kick.focus();
  announce('Sesión iniciada.');
});

elements.kick.addEventListener('click', () => {
  activeSession = addMovementAndPersist(activeSession);
  renderCounter();
  elements.kick.classList.remove('kick-button--pulse');
  requestAnimationFrame(() => elements.kick.classList.add('kick-button--pulse'));
  announce(`Patada ${activeSession.movements.length} registrada.`);
});

elements.undo.addEventListener('click', () => {
  if (!activeSession?.movements.length) return;
  activeSession = undoMovement(activeSession);
  saveActiveAndPersist(localStorage, activeSession);
  renderCounter();
  announce('Se eliminó el último registro.');
});

elements.note.addEventListener('input', () => {
  if (!activeSession) return;
  activeSession = { ...activeSession, notes: elements.note.value.slice(0, 500) };
  saveActiveAndPersist(localStorage, activeSession);
});

elements.finish.addEventListener('click', () => {
  if (!activeSession?.movements.length) return;
  const completed = finishSessionAndPersist(activeSession, elements.note.value);
  history = loadHistory(localStorage);
  activeSession = null;
  renderCounter();
  announce('Sesión guardada en tu historial.');
});

for (const tab of elements.tabs) {
  tab.addEventListener('click', () => switchView(tab.dataset.view));
}

renderCounter();
renderHistory();
