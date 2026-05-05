/* Éléments DOM */
const voteButtons     = document.querySelectorAll('.vote-btn');
const voteCount       = document.getElementById('vote-count');
const hintBtn         = document.getElementById('hint-btn');
const contestBtn      = document.getElementById('contest-btn');
const hintZone        = document.getElementById('hint-zone');
const hintText        = document.getElementById('hint-text');
const timerZone       = document.querySelector('.timer-zone');
const timerText       = document.getElementById('timer-text');
const timerFill       = document.getElementById('timer-fill');
const scoresList      = document.getElementById('scores-list');
const overlayHint     = document.getElementById('overlay-hint');
const overlayContest  = document.getElementById('overlay-contest');
const overlayRevote   = document.getElementById('overlay-revote');
const hintRequester   = document.getElementById('hint-requester');
const hintVoteCount   = document.getElementById('hint-vote-count');
const hintYesBtn      = document.getElementById('hint-yes');
const hintNoBtn       = document.getElementById('hint-no');
const hintTimerFill   = document.getElementById('hint-timer-fill');
const contestTitle    = document.getElementById('contest-title');
const contestInput    = document.getElementById('contest-input');
const contestTextEl   = document.getElementById('contest-text');
const contestDivergent= document.getElementById('contest-divergent');
const contestSubmit   = document.getElementById('contest-submit');
const contestArgument = document.getElementById('contest-argument');
const revoteButtons   = document.getElementById('revote-buttons');
const revoteTimerFill = document.getElementById('revote-timer-fill');
const mapTimerText    = document.getElementById('map-timer-text');
const mapHintBtn      = document.getElementById('map-hint-btn');
const mapFocusBtn     = document.getElementById('map-focus-btn');
const mapChoices      = document.getElementById('map-choices');
const mapIntelText    = document.getElementById('map-intel-text');

/* État du jeu */
const gameState = {
  selectedCountry: null,
  hasVoted:        false,
  hasAskedHint:    false,
  hintUsed:        false,
  tourComputed:    false,
  nextTurnCalled:  false,
  currentTour:     typeof TOUR      !== 'undefined' ? TOUR      : 1,
  currentPays:     typeof PAYS_LIST !== 'undefined' ? PAYS_LIST : [],
};

/* Timers */
const mainTimer = createTimer({
  duration: 30,
  textEl:   timerText,
  fillEl:   timerFill,
  zoneEl:   timerZone,
  onEnd:    onMainTimerEnd,
  onTick:   s => { if (mapTimerText) mapTimerText.textContent = s + 's restantes'; },
});

const hintTimer   = createTimer({ duration: 10, fillEl: hintTimerFill,   onEnd: onHintTimerEnd });
const revoteTimer = createTimer({ duration: 20, fillEl: revoteTimerFill,  onEnd: onRevoteTimerEnd });

/* Init */
document.addEventListener('DOMContentLoaded', () => {
  initVoteButtons();
  initHintButton();
  initContestButton();
  initContestSubmit();
  initHintVote();
  initMapHelpers();
  fetchScores();
  checkIfAlreadyVoted();
  setInterval(fetchScores, 3000);
});

/* ── Vérification état initial ── */
async function checkIfAlreadyVoted() {
  try {
    const data = await apiFetch(`ajax/get_scores.php?game_id=${GAME_ID}`);
    if (!data) { mainTimer.start(); return; }
    if (data.status === 'finished') { window.location.href = `results.php?game_id=${GAME_ID}`; return; }
    if (data.majority) {
      gameState.hasVoted     = true;
      gameState.tourComputed = true;
      lockVoteButtons();
      showTourResult(data.majority, data.correct);
      if (contestBtn) contestBtn.classList.remove('hidden');
    } else {
      gameState.hasVoted     = false;
      gameState.tourComputed = false;
      gameState.hasAskedHint = false;
      if (hintBtn)    hintBtn.disabled    = false;
      if (mapHintBtn) mapHintBtn.disabled = false;
      mainTimer.start();
    }
  } catch { mainTimer.start(); }
}

/* ── Vote pays ── */
function initVoteButtons() {
  document.querySelectorAll('.vote-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      if (gameState.hasVoted) return;
      selectCountry(btn.dataset.pays);
      submitVote(btn.dataset.pays);
    });
  });
}

function selectCountry(pays) {
  gameState.selectedCountry = pays;
  document.querySelectorAll('.vote-btn').forEach(b => b.classList.toggle('selected', b.dataset.pays === pays));
  if (mapFocusBtn) { mapFocusBtn.textContent = `Pays sélectionné : ${pays}`; mapFocusBtn.disabled = false; }
  if (mapIntelText) mapIntelText.textContent = `${pays} est verrouillé. Vote envoyé à ton équipe.`;
}

async function submitVote(pays) {
  gameState.hasVoted = true;
  lockVoteButtons();
  const data = await apiPost('ajax/submit_vote.php', { pays, game_id: GAME_ID, team_id: TEAM_ID, tour: gameState.currentTour });
  if (data?.ok) {
    wsSend({ type: 'vote_update', voted: data.voted, total: data.total, game_id: GAME_ID, team_id: TEAM_ID });
    if (data.all_voted && !gameState.tourComputed) {
      gameState.tourComputed = true;
      onAllVoted();
    }
  } else {
    gameState.hasVoted = false;
    unlockVoteButtons();
  }
}

function lockVoteButtons()   { document.querySelectorAll('.vote-btn').forEach(b => b.disabled = true);  mainTimer.stop(); }
function unlockVoteButtons() { document.querySelectorAll('.vote-btn').forEach(b => b.disabled = false); }

/* ── Calcul résultat tour ── */
async function onAllVoted() {
  if (gameState.nextTurnCalled) return;
  gameState.nextTurnCalled = true;
  mainTimer.stop();
  const data = await apiPost('ajax/compute_tour.php', { game_id: GAME_ID, team_id: TEAM_ID, tour: gameState.currentTour });
  if (data?.ok && data.majority) {
    gameState.majority = data.majority;
    showTourResult(data.majority, data.correct);
    if (contestBtn) contestBtn.classList.remove('hidden');
    wsSend({ type: 'vote_update', voted: data.total ?? 5, total: data.total ?? 5, majority: data.majority, correct: data.correct, game_id: GAME_ID, team_id: TEAM_ID });
    await fetchScores();

    // Attendre 3 secondes pour laisser voir le résultat puis passer au tour suivant
    setTimeout(async () => {
      const next = await apiPost('ajax/next_turn.php', { game_id: GAME_ID, team_id: TEAM_ID });
      if (next?.finished) {
        window.location.href = `results.php?game_id=${GAME_ID}`;
      } else if (next?.ok) {
        window.location.reload();
      }
    }, 3000);
  } else {
    await fetchScores();
  }
}

function showTourResult(majority, correct) {
  document.querySelectorAll('.tour-result').forEach(el => el.remove());
  const descEl = document.getElementById('event-desc');
  if (!descEl || !majority) return;
  const ok = majority === correct;
  const p  = document.createElement('p');
  p.className = 'tour-result';
  p.style.cssText = `margin-top:var(--space-sm);font-weight:700;font-size:0.95rem;color:${ok ? 'var(--teal)' : 'var(--coral)'};`;
  p.textContent = ok ? `✅ Bonne réponse : ${correct}` : `❌ La bonne réponse était : ${correct}`;
  descEl.insertAdjacentElement('afterend', p);
}

/* ── Indice ── */
function initHintButton() {
  [hintBtn, mapHintBtn].filter(Boolean).forEach(trigger => {
    trigger.addEventListener('click', async () => {
      if (gameState.hasAskedHint) return;
      gameState.hasAskedHint = true;
      [hintBtn, mapHintBtn].filter(Boolean).forEach(b => b.disabled = true);
      const data = await apiPost('ajax/request_hint.php', { game_id: GAME_ID, team_id: TEAM_ID, tour: gameState.currentTour, user_id: USER_ID });
      if (data?.ok) {
        wsSend({ type: 'hint_vote_opened', requester: PSEUDO, timer: 10, game_id: GAME_ID, team_id: TEAM_ID });
      } else {
        gameState.hasAskedHint = false;
        [hintBtn, mapHintBtn].filter(Boolean).forEach(b => b.disabled = false);
      }
    });
  });
}

function initHintVote() {
  hintYesBtn?.addEventListener('click', () => sendHintVote('oui'));
  hintNoBtn?.addEventListener('click',  () => sendHintVote('non'));
}

async function sendHintVote(choix) {
  closeOverlay(overlayHint);
  hintTimer.stop();
  const data = await apiPost('ajax/vote_hint.php', { game_id: GAME_ID, team_id: TEAM_ID, tour: gameState.currentTour, vote: choix });
  if (!data?.ok) return;
  if (hintVoteCount) hintVoteCount.textContent = `OUI : ${data.oui} — NON : ${data.non}`;
  if (data.result === 'accepted') {
    const scores = await apiFetch(`ajax/get_scores.php?game_id=${GAME_ID}`);
    const indice = scores?.indice ?? '';
    wsSend({ type: 'hint_available', text: indice, game_id: GAME_ID, team_id: TEAM_ID });
    if (indice && hintZone && hintText) { hintText.textContent = indice; hintZone.classList.remove('hidden'); }
  }
}

function showHintOverlay(pseudo) {
  if (hintRequester) hintRequester.textContent = pseudo + ' demande un indice';
  if (hintVoteCount) hintVoteCount.textContent = 'OUI : 0 — NON : 0';
  openOverlay(overlayHint);
  hintTimer.start();
}

/* ── Contestation ── */
function initContestButton() {
  contestBtn?.addEventListener('click', async () => {
    const data = await apiPost('ajax/contest_start.php', { game_id: GAME_ID, team_id: TEAM_ID, tour: gameState.currentTour });
    if (data?.ok) {
      openContestOverlay(true);
      wsSend({ type: 'contest_opened', pseudo: PSEUDO, timer: data.timer, game_id: GAME_ID, team_id: TEAM_ID });
    } else {
      console.warn('Contest refusé :', data?.error);
    }
  });
}

function openContestOverlay(isContestant) {
  if (isContestant) {
    contestInput?.classList.remove('hidden');
    contestSubmit?.classList.remove('hidden');
    contestTextEl?.classList.add('hidden');
    if (contestTitle) contestTitle.textContent = 'Rédige ton argument (30s)';
  } else {
    contestInput?.classList.add('hidden');
    contestSubmit?.classList.add('hidden');
    contestTextEl?.classList.remove('hidden');
  }
  openOverlay(overlayContest);
}

function initContestSubmit() {
  contestSubmit?.addEventListener('click', async () => {
    const texte = contestInput?.value.trim().slice(0, 150);
    if (!texte) return;
    const data = await apiPost('ajax/submit_contest.php', { game_id: GAME_ID, team_id: TEAM_ID, tour: gameState.currentTour, text: texte, pays_defendu: gameState.selectedCountry });
    if (data?.ok) {
      closeOverlay(overlayContest);
      wsSend({ type: 'contest_message', text: data.text, pays_defendu: data.pays_defendu, divergent_votes: data.divergent_votes, majority: data.majority, revote: data.revote, timer: data.timer, game_id: GAME_ID, team_id: TEAM_ID });
      if (data.revote) wsSend({ type: 'revote_started', timer: data.timer, game_id: GAME_ID, team_id: TEAM_ID });
    }
  });
}

/* ── Revote ── */
function startRevote(argument, pays) {
  if (contestArgument) contestArgument.textContent = `"${argument}"`;
  if (revoteButtons) {
    revoteButtons.innerHTML = '';
    pays.forEach(p => {
      const btn = document.createElement('button');
      btn.className = 'btn vote-btn';
      btn.dataset.pays = p;
      btn.textContent = p;
      btn.addEventListener('click', async () => {
        revoteButtons.querySelectorAll('.vote-btn').forEach(b => { b.classList.remove('selected'); b.disabled = true; });
        btn.classList.add('selected');
        revoteTimer.stop();
        const data = await apiPost('ajax/vote_contest.php', { game_id: GAME_ID, team_id: TEAM_ID, tour: gameState.currentTour, pays: p });
        if (data?.ok && data.revote_done) {
          wsSend({ type: 'revote_result', answer: data.new_majority, correct: data.new_majority === data.correct, game_id: GAME_ID, team_id: TEAM_ID });
          fetchScores();
        }
      });
      revoteButtons.appendChild(btn);
    });
  }
  openOverlay(overlayRevote);
  revoteTimer.start();
}

/* ── Scores ── */
async function fetchScores() {
  const data = await apiFetch(`ajax/get_scores.php?game_id=${GAME_ID}`);
  if (!scoresList || !data?.scores) return;
  scoresList.innerHTML = data.scores.map((j, i) => `
    <div class="score-item ${j.pseudo === PSEUDO ? 'score-item--me' : ''}">
      <span class="score-item__rank">${i + 1}</span>
      <span class="score-item__pseudo">${escapeHtml(j.pseudo)}</span>
      <span class="score-item__points">${j.score}</span>
    </div>
  `).join('');
  if (data.status === 'finished') window.location.href = `results.php?game_id=${GAME_ID}`;
}

function showScoreBadge(points) {
  const myItem = scoresList?.querySelector('.score-item--me');
  if (!myItem) return;
  let badge = myItem.querySelector('.score-badge');
  if (!badge) { badge = document.createElement('span'); badge.className = 'score-badge'; myItem.appendChild(badge); }
  badge.textContent = '+' + points;
  badge.classList.add('visible');
  setTimeout(() => badge.classList.remove('visible'), 2000);
}

/* ── Carte ── */
function initMapHelpers() {
  positionCountryPins();
  mapFocusBtn?.addEventListener('click', () => {
    if (!gameState.selectedCountry || gameState.hasVoted) return;
    submitVote(gameState.selectedCountry);
  });
}

function positionCountryPins() {
  if (!mapChoices) return;
  const coords = {
    france:[53,34], espagne:[49,42], spain:[49,42], turquie:[62,43], turkey:[62,43],
    grèce:[57,44], grece:[57,44], greece:[57,44], italie:[54,41], italy:[54,41],
    allemagne:[53,31], germany:[53,31], angleterre:[49,29], "royaume-uni":[49,29], uk:[49,29],
    chine:[74,40], china:[74,40], japon:[86,40], japan:[86,40], inde:[68,49], india:[68,49],
    egypte:[56,50], egypt:[56,50], maroc:[48,48], mali:[47,56],
    usa:[22,39], "états-unis":[22,39], "etats-unis":[22,39], mexique:[18,50],
    brésil:[33,67], bresil:[33,67], brazil:[33,67], argentine:[31,78], argentina:[31,78],
    australie:[82,72], australia:[82,72], russie:[68,25], russia:[68,25],
    prusse:[55,31], autriche:[55,36], hongrie:[57,37],
  };
  const fallback = [[45,38],[55,42],[62,34],[50,52]];
  mapChoices.querySelectorAll('.map-choice').forEach((btn, i) => {
    const key = (btn.dataset.pays || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/\s+/g,'').trim();
    const pos = coords[key] || fallback[i % fallback.length];
    btn.style.setProperty('--pin-left', pos[0] + '%');
    btn.style.setProperty('--pin-top',  pos[1] + '%');
  });
}

/* ── Callbacks timers ── */
function onMainTimerEnd() {
  if (!gameState.hasVoted) { gameState.hasVoted = true; lockVoteButtons(); }
  if (!gameState.tourComputed) { gameState.tourComputed = true; onAllVoted(); }
}
function onHintTimerEnd()   { closeOverlay(overlayHint); }
function onRevoteTimerEnd() { closeOverlay(overlayRevote); }

/* ── Overlays ── */
function openOverlay(el)  { el?.classList.add('overlay--open'); }
function closeOverlay(el) { el?.classList.remove('overlay--open'); }

/* ── Handlers WebSocket (appelés depuis websocket.js) ── */
function onVoteUpdate(data) {
  if (voteCount) voteCount.textContent = `${data.voted}/${data.total} joueurs ont voté`;
  if (data.majority && data.correct) {
    showTourResult(data.majority, data.correct);
    if (contestBtn) contestBtn.classList.remove('hidden');
    lockVoteButtons();
  }
}

function onHintAvailable(data) {
  showHintOverlay(data.requester ?? data.pseudo ?? 'Un joueur');
}

function onContestOpened(data) {
  if (data.pseudo === PSEUDO) return;
  if (contestTextEl) contestTextEl.textContent = data.pseudo + ' rédige son argument…';
  openContestOverlay(false);
}

function onContestMessage(data) {
  closeOverlay(overlayContest);
  if (contestArgument) contestArgument.textContent = `"${data.text ?? data.texte ?? ''}"`;
  if (contestDivergent && data.divergent_votes) {
    contestDivergent.innerHTML = Object.entries(data.divergent_votes).map(([pseudo, pays]) =>
      `<span class="vote-tag ${pays === data.majority ? 'vote-tag--majority' : 'vote-tag--minority'}">${escapeHtml(pseudo)} → ${escapeHtml(pays)}</span>`
    ).join(' ');
  }
}

function onRevoteStarted(data) { gameState.hasVoted = false; startRevote(data.argument ?? '', data.pays ?? gameState.currentPays); }

function onRevoteResult(data) {
  closeOverlay(overlayRevote);
  revoteTimer.stop();
  const pts = data.scores?.[USER_ID] ?? data.points_gagnes ?? 0;
  if (pts > 0) showScoreBadge(pts);
  fetchScores();
}

function onTeamUpdate(data) {
  const list = document.getElementById('team-list');
  const members = data.members ?? data.membres;
  if (!list || !members) return;
  list.innerHTML = members.map(m => `<li style="color:var(--text-on-dark);font-size:0.9rem;padding:var(--space-xs) 0;">${escapeHtml(m.pseudo)}</li>`).join('');
}

function onNewTurn(data) {
  gameState.selectedCountry = null;
  gameState.hasVoted        = false;
  gameState.hasAskedHint    = false;
  gameState.hintUsed        = false;
  gameState.tourComputed    = false;
  gameState.currentTour     = data.tour  ?? gameState.currentTour;
  gameState.currentPays     = data.pays  ?? [];

  const yearEl  = document.getElementById('event-year');
  const titleEl = document.getElementById('event-title');
  const descEl  = document.getElementById('event-desc');
  if (yearEl)  yearEl.textContent  = data.annee       ?? data.year  ?? '';
  if (titleEl) titleEl.textContent = data.titre       ?? data.title ?? '';
  if (descEl)  descEl.textContent  = data.description ?? data.desc  ?? '';

  if (hintZone) hintZone.classList.add('hidden');
  document.querySelectorAll('.tour-result').forEach(el => el.remove());
  document.querySelectorAll('.vote-btn').forEach(b => { b.classList.remove('selected'); b.disabled = false; });
  if (mapFocusBtn) { mapFocusBtn.textContent = 'Sélectionne un pays'; mapFocusBtn.disabled = true; }
  if (mapIntelText) mapIntelText.textContent = "Choisis le pays qui correspond le mieux à l'événement.";
  if (contestBtn) contestBtn.classList.add('hidden');
  if (hintBtn) hintBtn.disabled = false;
  [hintBtn, mapHintBtn].filter(Boolean).forEach(b => b.disabled = false);
  mainTimer.start();
}

/* ── Utilitaires ── */
async function apiFetch(url) {
  try { const r = await fetch(url); return r.ok ? await r.json() : null; }
  catch { return null; }
}

async function apiPost(url, body) {
  try {
    const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
    return r.ok ? await r.json() : null;
  } catch { return null; }
}

function escapeHtml(str) {
  const d = document.createElement('div');
  d.textContent = str ?? '';
  return d.innerHTML;
}