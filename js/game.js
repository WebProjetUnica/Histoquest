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
  currentTour:     typeof TOUR !== 'undefined' ? TOUR : 1,
  currentPays:     typeof PAYS_LIST !== 'undefined' ? PAYS_LIST : [],
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
  fetchScores();
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
      body: JSON.stringify({
        pays,
        game_id: GAME_ID,
        team_id: TEAM_ID,
        tour:    gameState.currentTour,
      })
    });
    const data = await response.json();
    if (data.ok) {
      wsSend({
        type:    'vote_update',
        voted:   data.voted,
        total:   data.total,
        game_id: GAME_ID,
        team_id: TEAM_ID,
      });
      if (data.all_voted) {
        onAllVoted();
      }
    } else {
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
        body: JSON.stringify({
          game_id: GAME_ID,
          team_id: TEAM_ID,
          tour:    gameState.currentTour,
          user_id: USER_ID,
        })
      });
      const data = await response.json();
      if (data.ok) {
        wsSend({
          type:      'hint_vote_opened',
          requester: PSEUDO,
          timer:     10,
          game_id:   GAME_ID,
          team_id:   TEAM_ID,
        });
      } else {
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

async function sendHintVote(choix) {
  closeOverlay(overlayHint);
  hintTimer.stop();
  try {
    const response = await fetch('ajax/vote_hint.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        game_id: GAME_ID,
        team_id: TEAM_ID,
        tour:    gameState.currentTour,
        vote:    choix,
      })
    });
    const data = await response.json();
    if (data.ok && data.result === 'accepted') {
      wsSend({
        type:    'hint_available',
        game_id: GAME_ID,
        team_id: TEAM_ID,
      });
    }
    if (hintVoteCount) {
      hintVoteCount.textContent = `OUI : ${data.oui} — NON : ${data.non}`;
    }
  } catch (err) {
    console.error('Erreur vote hint :', err);
  }
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
  contestBtn.addEventListener('click', async () => {
    try {
      const response = await fetch('ajax/contest_start.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          game_id: GAME_ID,
          team_id: TEAM_ID,
          tour:    gameState.currentTour,
        })
      });
      const data = await response.json();
      if (data.ok) {
        openContestOverlay(true);
        wsSend({
          type:    'contest_opened',
          pseudo:  PSEUDO,
          timer:   data.timer,
          game_id: GAME_ID,
          team_id: TEAM_ID,
        });
      } else {
        console.warn('Contest refusé :', data.error);
      }
    } catch (err) {
      console.error('Erreur contest_start :', err);
    }
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
  contestSubmit.addEventListener('click', async () => {
    const texte = contestInput.value.trim().slice(0, 150);
    if (!texte) return;
    try {
      const response = await fetch('ajax/submit_contest.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          game_id:      GAME_ID,
          team_id:      TEAM_ID,
          tour:         gameState.currentTour,
          text:         texte,
          pays_defendu: gameState.selectedCountry,
        })
      });
      const data = await response.json();
      if (data.ok) {
        closeOverlay(overlayContest);
        wsSend({
          type:            'contest_message',
          text:            data.text,
          pays_defendu:    data.pays_defendu,
          divergent_votes: data.divergent_votes,
          majority:        data.majority,
          revote:          data.revote,
          timer:           data.timer,
          game_id:         GAME_ID,
          team_id:         TEAM_ID,
        });
        if (data.revote) {
          wsSend({
            type:    'revote_started',
            timer:   data.timer,
            game_id: GAME_ID,
            team_id: TEAM_ID,
          });
        }
      }
    } catch (err) {
      console.error('Erreur submit_contest :', err);
    }
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
      btn.addEventListener('click', async () => {
        revoteButtons.querySelectorAll('.vote-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        revoteButtons.querySelectorAll('.vote-btn').forEach(b => b.disabled = true);
        revoteTimer.stop();
        try {
          const response = await fetch('ajax/vote_contest.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              game_id: GAME_ID,
              team_id: TEAM_ID,
              tour:    gameState.currentTour,
              pays:    p,
            })
          });
          const data = await response.json();
          if (data.ok && data.revote_done) {
            wsSend({
              type:         'revote_result',
              answer:       data.new_majority,
              correct:      data.new_majority === data.correct,
              game_id:      GAME_ID,
              team_id:      TEAM_ID,
            });
            fetchScores();
          }
        } catch (err) {
          console.error('Erreur vote_contest :', err);
        }
      });
      revoteButtons.appendChild(btn);
    });
  }
  openOverlay(overlayRevote);
  revoteTimer.start();
}

async function fetchScores() {
  try {
    const response = await fetch(`ajax/get_scores.php?game_id=${GAME_ID}`);
    const data = await response.json();
    if (!scoresList || !data.scores) return;
    scoresList.innerHTML = data.scores.map((joueur, i) => `
      <div class="score-item ${joueur.pseudo === PSEUDO ? 'score-item--me' : ''}">
        <span class="score-item__rank">${i + 1}</span>
        <span class="score-item__pseudo">${escapeHtml(joueur.pseudo)}</span>
        <span class="score-item__points">${joueur.score}</span>
      </div>
    `).join('');

    if (data.status === 'finished') {
      window.location.href = `results.php?game_id=${GAME_ID}`;
    }
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

function onAllVoted() {
  mainTimer.stop();
  if (contestBtn) contestBtn.classList.remove('hidden');
  fetchScores();
}

function onMainTimerEnd() {
  if (!gameState.hasVoted) {
    gameState.hasVoted = true;
    lockVoteButtons();
  }
  if (contestBtn) contestBtn.classList.remove('hidden');
  fetchScores();
}
function onHintTimerEnd()   { closeOverlay(overlayHint); }
function onRevoteTimerEnd() { closeOverlay(overlayRevote); }

function openOverlay(el)  { if (el) el.classList.add('overlay--open'); }
function closeOverlay(el) { if (el) el.classList.remove('overlay--open'); }

function onVoteUpdate(data) {
  if (voteCount) voteCount.textContent = `${data.voted}/${data.total} joueurs ont voté`;
}

function onHintAvailable(data) {
  const pseudo = data.requester ?? data.pseudo ?? 'Un joueur';
  showHintOverlay(pseudo);
  if (hintVoteCount) hintVoteCount.textContent = `OUI : 0 — NON : 0`;
}

function onContestOpened(data) {
  if (data.pseudo === PSEUDO) return;
  if (contestTextEl) contestTextEl.textContent = data.pseudo + ' rédige son argument…';
  openContestOverlay(false);
}

function onContestMessage(data) {
  closeOverlay(overlayContest);
  if (contestArgument) contestArgument.textContent = '"' + (data.text ?? data.texte ?? '') + '"';
  if (contestDivergent && data.divergent_votes) {
    contestDivergent.innerHTML = Object.entries(data.divergent_votes).map(([pseudo, pays]) => `
      <span class="vote-tag ${pays === data.majority ? 'vote-tag--majority' : 'vote-tag--minority'}">
        ${escapeHtml(pseudo)} → ${escapeHtml(pays)}
      </span>
    `).join(' ');
  }
}

function onRevoteStarted(data) {
  gameState.hasVoted = false;
  startRevote(data.argument ?? '', data.pays ?? gameState.currentPays);
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
  gameState.currentTour     = data.tour ?? gameState.currentTour;
  gameState.currentPays     = data.pays ?? [];

  const yearEl  = document.getElementById('event-year');
  const titleEl = document.getElementById('event-title');
  const descEl  = document.getElementById('event-desc');
  if (yearEl)  yearEl.textContent  = data.annee  ?? data.year  ?? '';
  if (titleEl) titleEl.textContent = data.titre  ?? data.title ?? '';
  if (descEl)  descEl.textContent  = data.description ?? data.desc ?? '';

  if (hintZone) hintZone.classList.add('hidden');

  const voteBtns = document.querySelectorAll('#vote-buttons .vote-btn');
  voteBtns.forEach(btn => { btn.classList.remove('selected'); btn.disabled = false; });

  if (contestBtn) contestBtn.classList.add('hidden');
  if (hintBtn) hintBtn.disabled = false;
  mainTimer.start();
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}