const voteButtons    = document.querySelectorAll('.vote-btn');
const voteCount      = document.getElementById('vote-count');
const hintBtn        = document.getElementById('hint-btn');
const contestBtn     = document.getElementById('contest-btn');
const hintZone       = document.getElementById('hint-zone');
const hintText       = document.getElementById('hint-text');
const timerZone      = document.querySelector('.timer-zone');
const timerText      = document.getElementById('timer-text');
const timerFill      = document.getElementById('timer-fill');
const scoresList     = document.getElementById('scores-list');
const overlayHint    = document.getElementById('overlay-hint');
const overlayContest = document.getElementById('overlay-contest');
const overlayRevote  = document.getElementById('overlay-revote');
const hintRequester  = document.getElementById('hint-requester');
const hintVoteCount  = document.getElementById('hint-vote-count');
const hintYesBtn     = document.getElementById('hint-yes');
const hintNoBtn      = document.getElementById('hint-no');
const hintTimerFill  = document.getElementById('hint-timer-fill');
const contestTitle   = document.getElementById('contest-title');
const contestInput   = document.getElementById('contest-input');
const contestTextEl  = document.getElementById('contest-text');
const contestDivergent = document.getElementById('contest-divergent');
const contestSubmit  = document.getElementById('contest-submit');
const contestArgument = document.getElementById('contest-argument');
const revoteButtons  = document.getElementById('revote-buttons');
const revoteTimerFill = document.getElementById('revote-timer-fill');

const gameState = {
  selectedCountry: null,
  hasVoted:        false,
  hasAskedHint:    false,
  hintUsed:        false,
  currentPays:     [],
};

const mainTimer = createTimer({
  duration: 30,
  textEl:   timerText,
  fillEl:   timerFill,
  zoneEl:   timerZone,
  onEnd:    onMainTimerEnd,
});

const hintTimer = createTimer({
  duration: 10,
  textEl:   null,
  fillEl:   hintTimerFill,
  zoneEl:   null,
  onEnd:    onHintTimerEnd,
});

const revoteTimer = createTimer({
  duration: 20,
  textEl:   null,
  fillEl:   revoteTimerFill,
  zoneEl:   null,
  onEnd:    onRevoteTimerEnd,
});

document.addEventListener('DOMContentLoaded', () => {
  initVoteButtons();
  initHintButton();
  initContestButton();
  initContestSubmit();
  initHintVote();
  mainTimer.start();
});

function initVoteButtons() {
  voteButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      if (gameState.hasVoted) return;
      const pays = btn.dataset.pays;
      voteButtons.forEach(b => b.classList.remove('selected'));
      btn.classList.add('selected');
      gameState.selectedCountry = pays;
      submitVote(pays);
    });
  });
}

async function submitVote(pays) {
  gameState.hasVoted = true;
  lockVoteButtons();
  try {
    const response = await fetch('ajax/submit_vote.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ pays, game_id: GAME_ID, user_id: USER_ID })
    });
    const data = await response.json();
    if (!data.ok) {
      gameState.hasVoted = false;
      unlockVoteButtons();
    }
  } catch (err) {
    gameState.hasVoted = false;
    unlockVoteButtons();
  }
}

function lockVoteButtons() {
  voteButtons.forEach(btn => btn.disabled = true);
  mainTimer.stop();
}

function unlockVoteButtons() {
  voteButtons.forEach(btn => btn.disabled = false);
}

function initHintButton() {
  if (!hintBtn) return;
  hintBtn.addEventListener('click', async () => {
    if (gameState.hasAskedHint) return;
    gameState.hasAskedHint = true;
    hintBtn.disabled = true;
    try {
      const response = await fetch('ajax/request_hint.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ game_id: GAME_ID, user_id: USER_ID })
      });
      const data = await response.json();
      if (!data.ok) {
        gameState.hasAskedHint = false;
        hintBtn.disabled = false;
      }
    } catch (err) {
      gameState.hasAskedHint = false;
      hintBtn.disabled = false;
    }
  });
}

function initHintVote() {
  if (!hintYesBtn || !hintNoBtn) return;
  hintYesBtn.addEventListener('click', () => sendHintVote('oui'));
  hintNoBtn.addEventListener('click',  () => sendHintVote('non'));
}

function sendHintVote(choix) {
  wsSend({ type: 'hint_vote', choix, game_id: GAME_ID, user_id: USER_ID });
  closeOverlay(overlayHint);
  hintTimer.stop();
}

function showHintOverlay(pseudo) {
  if (hintRequester) hintRequester.textContent = pseudo + ' demande un indice';
  if (hintVoteCount) hintVoteCount.textContent = 'OUI : 0 — NON : 0';
  openOverlay(overlayHint);
  hintTimer.start();
}

function showHint(texte) {
  gameState.hintUsed = true;
  closeOverlay(overlayHint);
  if (hintZone && hintText) {
    hintText.textContent = texte;
    hintZone.classList.remove('hidden');
  }
}

function initContestButton() {
  if (!contestBtn) return;
  contestBtn.addEventListener('click', () => {
    openContestOverlay(true);
    wsSend({ type: 'contest_open', game_id: GAME_ID, user_id: USER_ID, pseudo: PSEUDO });
  });
}

function openContestOverlay(isContestant) {
  if (isContestant) {
    contestInput.classList.remove('hidden');
    contestSubmit.classList.remove('hidden');
    contestTextEl.classList.add('hidden');
    if (contestTitle) contestTitle.textContent = 'Rédige ton argument (30s)';
  } else {
    contestInput.classList.add('hidden');
    contestSubmit.classList.add('hidden');
    contestTextEl.classList.remove('hidden');
  }
  openOverlay(overlayContest);
}

function initContestSubmit() {
  if (!contestSubmit) return;
  contestSubmit.addEventListener('click', () => {
    const texte = contestInput.value.trim().slice(0, 150);
    if (!texte) return;
    wsSend({ type: 'contest_message', texte, game_id: GAME_ID, user_id: USER_ID, pseudo: PSEUDO });
    closeOverlay(overlayContest);
  });
}

function startRevote(argument, pays) {
  if (contestArgument) contestArgument.textContent = '"' + argument + '"';
  if (revoteButtons) {
    revoteButtons.innerHTML = '';
    pays.forEach(p => {
      const btn = document.createElement('button');
      btn.className    = 'btn vote-btn';
      btn.dataset.pays = p;
      btn.textContent  = p;
      btn.addEventListener('click', () => {
        revoteButtons.querySelectorAll('.vote-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        wsSend({ type: 'revote', pays: p, game_id: GAME_ID, user_id: USER_ID });
        revoteButtons.querySelectorAll('.vote-btn').forEach(b => b.disabled = true);
        revoteTimer.stop();
      });
      revoteButtons.appendChild(btn);
    });
  }
  openOverlay(overlayRevote);
  revoteTimer.start();
}

async function fetchScores() {
  try {
    const response = await fetch('ajax/get_scores.php?game_id=' + GAME_ID);
    const data = await response.json();
    if (!scoresList) return;
    scoresList.innerHTML = data.scores.map((joueur, i) => `
      <div class="score-item ${joueur.pseudo === PSEUDO ? 'score-item--me' : ''}">
        <span class="score-item__rank">${i + 1}</span>
        <span class="score-item__pseudo">${escapeHtml(joueur.pseudo)}</span>
        <span class="score-item__points">${joueur.score}</span>
      </div>
    `).join('');
  } catch (err) {
    console.error('Erreur scores :', err);
  }
}

function showScoreBadge(points) {
  const myItem = scoresList?.querySelector('.score-item--me');
  if (!myItem) return;
  let badge = myItem.querySelector('.score-badge');
  if (!badge) {
    badge = document.createElement('span');
    badge.className = 'score-badge';
    myItem.appendChild(badge);
  }
  badge.textContent = '+' + points;
  badge.classList.add('visible');
  setTimeout(() => badge.classList.remove('visible'), 2000);
}

function onMainTimerEnd() {
  if (!gameState.hasVoted) {
    gameState.hasVoted = true;
    lockVoteButtons();
  }
}
function onHintTimerEnd()   { closeOverlay(overlayHint); }
function onRevoteTimerEnd() { closeOverlay(overlayRevote); }

function openOverlay(el)  { if (el) el.classList.add('overlay--open'); }
function closeOverlay(el) { if (el) el.classList.remove('overlay--open'); }

function onVoteUpdate(data) {
  if (voteCount) voteCount.textContent = `${data.voted}/${data.total} joueurs ont voté`;
}

function onHintAvailable(data)  { showHintOverlay(data.pseudo); }

function onContestOpened(data) {
  if (data.pseudo === PSEUDO) return;
  if (contestTextEl) contestTextEl.textContent = data.pseudo + ' rédige son argument…';
  openContestOverlay(false);
}

function onContestMessage(data) {
  closeOverlay(overlayContest);
  if (contestArgument) contestArgument.textContent = '"' + data.texte + '"';
  if (contestDivergent && data.votes) {
    contestDivergent.innerHTML = data.votes.map(v => `
      <span class="vote-tag ${v.majority ? 'vote-tag--majority' : 'vote-tag--minority'}">
        ${escapeHtml(v.pseudo)} → ${escapeHtml(v.pays)}
      </span>
    `).join(' ');
  }
}

function onRevoteStarted(data) {
  gameState.hasVoted = false;
  startRevote(data.argument, data.pays);
}

function onRevoteResult(data) {
  closeOverlay(overlayRevote);
  revoteTimer.stop();
  if (data.points_gagnes > 0) showScoreBadge(data.points_gagnes);
  fetchScores();
}

function onTeamUpdate(data) {
  const teamList = document.getElementById('team-list');
  if (!teamList || !data.membres) return;
  teamList.innerHTML = data.membres.map(m => `
    <li style="color:var(--text-on-dark);font-size:0.9rem;padding:var(--space-xs) 0;">
      ${escapeHtml(m.pseudo)}
    </li>
  `).join('');
}

function onNewTurn(data) {
  gameState.selectedCountry = null;
  gameState.hasVoted        = false;
  gameState.hasAskedHint    = false;
  gameState.hintUsed        = false;
  gameState.currentPays     = data.pays;

  const yearEl  = document.getElementById('event-year');
  const titleEl = document.getElementById('event-title');
  const descEl  = document.getElementById('event-desc');
  if (yearEl)  yearEl.textContent  = data.year;
  if (titleEl) titleEl.textContent = data.title;
  if (descEl)  descEl.textContent  = data.desc;

  if (hintZone) hintZone.classList.add('hidden');
  voteButtons.forEach(btn => { btn.classList.remove('selected'); btn.disabled = false; });
  if (contestBtn) contestBtn.classList.add('hidden');
  if (hintBtn) hintBtn.disabled = false;
  mainTimer.start();
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}