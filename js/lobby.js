let lobbyWs = null;
let lobbyReconnectDelay = 1000;

const inviteInput    = document.getElementById('invite-input');
const inviteBtn      = document.getElementById('invite-btn');
const startBtn       = document.getElementById('start-btn');
const gamesList      = document.getElementById('games-list');
const teamMembers    = document.getElementById('team-members');
const overlayInvite  = document.getElementById('overlay-invitation');
const inviteFrom     = document.getElementById('invite-from');
const inviteTeam     = document.getElementById('invite-team');
const inviteAccept   = document.getElementById('invite-accept');
const inviteRefuse   = document.getElementById('invite-refuse');

document.addEventListener('DOMContentLoaded', () => {
  fetchGames();
  setInterval(fetchGames, 5000);
  lobbyWsConnect();
  if (inviteBtn)   inviteBtn.addEventListener('click', sendInvite);
  if (inviteAccept) inviteAccept.addEventListener('click', acceptInvite);
  if (inviteRefuse) inviteRefuse.addEventListener('click', refuseInvite);
  if (startBtn)    startBtn.addEventListener('click', startGame);
});

async function fetchGames() {
  try {
    const response = await fetch('ajax/get_games.php');
    const data = await response.json();
    renderGames(data.games);
  } catch (err) {
    console.error('Erreur fetchGames :', err);
  }
}

function renderGames(games) {
  if (!gamesList) return;
  if (!games || games.length === 0) {
    gamesList.innerHTML = '<p class="text-muted">Aucune partie en attente.</p>';
    return;
  }
  gamesList.innerHTML = games.map(g => `
    <div class="game-item">
      <span class="game-item__info">${escapeHtml(g.host)} — ${g.players}/5 joueurs</span>
      <button class="btn btn-primary" onclick="joinGame('${g.id}')">Rejoindre</button>
    </div>
  `).join('');
}

async function joinGame(gameId) {
  try {
    const response = await fetch('ajax/join_game.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ game_id: gameId, user_id: USER_ID })
    });
    const data = await response.json();
    if (data.ok) {
      document.getElementById('lobby-team')?.classList.remove('hidden');
      if (startBtn) startBtn.disabled = true;
    }
  } catch (err) {
    console.error('Erreur joinGame :', err);
  }
}

async function sendInvite() {
  const pseudo = inviteInput?.value.trim();
  if (!pseudo) return;
  try {
    const response = await fetch('ajax/invite_player.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ pseudo, game_id: GAME_ID, user_id: USER_ID })
    });
    const data = await response.json();
    if (!data.ok) alert(data.error);
    else if (inviteInput) inviteInput.value = '';
  } catch (err) {
    console.error('Erreur sendInvite :', err);
  }
}

async function startGame() {
  try {
    const response = await fetch('ajax/start_game.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ game_id: GAME_ID, user_id: USER_ID })
    });
    const data = await response.json();
    if (data.ok) window.location.href = 'game.php';
  } catch (err) {
    console.error('Erreur startGame :', err);
  }
}

let pendingInvite = null;

function onInvitationReceived(data) {
  pendingInvite = data;
  if (inviteFrom) inviteFrom.textContent = data.from_pseudo;
  if (inviteTeam) inviteTeam.textContent = data.team_name ?? 'une équipe';
  if (overlayInvite) {
    overlayInvite.classList.add('overlay--open');
    setTimeout(() => overlayInvite.classList.remove('overlay--open'), 30000);
  }
}

async function acceptInvite() {
  if (!pendingInvite) return;
  if (overlayInvite) overlayInvite.classList.remove('overlay--open');
  try {
    const response = await fetch('ajax/join_game.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ game_id: pendingInvite.game_id, user_id: USER_ID })
    });
    const data = await response.json();
    if (data.ok) document.getElementById('lobby-team')?.classList.remove('hidden');
  } catch (err) {
    console.error('Erreur acceptInvite :', err);
  }
  pendingInvite = null;
}

function refuseInvite() {
  pendingInvite = null;
  if (overlayInvite) overlayInvite.classList.remove('overlay--open');
}

function onTeamUpdate(data) {
  if (!teamMembers || !data.membres) return;
  teamMembers.innerHTML = data.membres.map(m => `
    <div class="team-member">
      <span>${escapeHtml(m.pseudo)}</span>
    </div>
  `).join('');
  if (startBtn) startBtn.disabled = data.membres.length < 2;
}

function lobbyWsConnect() {
  lobbyWs = new WebSocket(WS_URL);

  lobbyWs.addEventListener('open', () => {
    lobbyReconnectDelay = 1000;
    lobbyWsSend({ type: 'lobby_join', user_id: USER_ID, pseudo: PSEUDO });
  });

  lobbyWs.addEventListener('message', (event) => {
    try {
      const data = JSON.parse(event.data);
      switch (data.type) {
        case 'invitation':   onInvitationReceived(data); break;
        case 'team_update':  onTeamUpdate(data);         break;
        case 'game_started': window.location.href = 'game.php'; break;
      }
    } catch (err) {
      console.error('WS lobby message invalide :', err);
    }
  });

  lobbyWs.addEventListener('close', () => {
    setTimeout(() => {
      lobbyReconnectDelay = Math.min(lobbyReconnectDelay * 2, 30000);
      lobbyWsConnect();
    }, lobbyReconnectDelay);
  });

  lobbyWs.addEventListener('error', () => lobbyWs.close());
}

function lobbyWsSend(payload) {
  if (lobbyWs && lobbyWs.readyState === WebSocket.OPEN) {
    lobbyWs.send(JSON.stringify(payload));
  }
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}