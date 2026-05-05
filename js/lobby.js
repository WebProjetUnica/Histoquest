let lobbyWs = null;
let lobbyReconnectDelay = 1000;
let pollInterval = null;

const inviteInput   = document.getElementById('invite-input');
const inviteBtn     = document.getElementById('invite-btn');
const teamMembers   = document.getElementById('team-members');
const overlayInvite = document.getElementById('overlay-invitation');
const inviteFrom    = document.getElementById('invite-from');
const inviteTeam    = document.getElementById('invite-team');
const inviteAccept  = document.getElementById('invite-accept');
const inviteRefuse  = document.getElementById('invite-refuse');

document.addEventListener('DOMContentLoaded', () => {
  interceptJoinForms();
  lobbyWsConnect();
  startPolling();

  if (inviteBtn)    inviteBtn.addEventListener('click', sendInvite);
  if (inviteAccept) inviteAccept.addEventListener('click', acceptInvite);
  if (inviteRefuse) inviteRefuse.addEventListener('click', refuseInvite);
});


// ── Intercepter les formulaires "Rejoindre" ────────────────────────────────
// lobby.php utilise des formulaires POST — on les intercepte pour utiliser
// ajax/join_game.php en fetch et éviter un rechargement de page
function interceptJoinForms() {
  document.querySelectorAll('form').forEach(form => {
    const actionInput = form.querySelector('input[name="action"]');
    if (actionInput?.value === 'join') {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const game_id = form.querySelector('input[name="game_id"]')?.value;
        const team_id = form.querySelector('input[name="team_id"]')?.value;
        if (game_id && team_id) await joinGame(game_id, team_id);
      });
    }
  });
}

// ── Intercepter le formulaire "Démarrer" ──────────────────────────────────
// Même logique — on évite le rechargement et on redirige via JS
function interceptStartForm() {
  document.querySelectorAll('form').forEach(form => {
    const actionInput = form.querySelector('input[name="action"]');
    if (actionInput?.value === 'start') {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await startGame();
      });
    }
  });
}

async function joinGame(game_id, team_id) {
  try {
    const response = await fetch('ajax/join_game.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ game_id, team_id })
    });
    const data = await response.json();
    if (data.ok) {
      // Mettre à jour la liste des membres localement
      renderTeamMembers(data.members);
    } else {
      alert(data.error ?? 'Impossible de rejoindre cette équipe.');
    }
  } catch (err) {
    console.error('Erreur joinGame :', err);
  }
}

async function startGame() {
  try {
    const response = await fetch('ajax/start_game.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ game_id: GAME_ID })
    });
    const data = await response.json();
    if (data.ok) window.location.href = 'game.php';
    else alert(data.error ?? 'Impossible de démarrer la partie.');
  } catch (err) {
    console.error('Erreur startGame :', err);
  }
}

async function sendInvite() {
  const pseudo = inviteInput?.value.trim();
  if (!pseudo) return;

  const feedback = document.getElementById('invite-feedback');
  try {
    const response = await fetch('ajax/invite_player.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        target_pseudo: pseudo,
        game_id:       GAME_ID,
        team_id:       TEAM_ID,
      })
    });
    const data = await response.json();
    if (!data.ok) {
      if (feedback) feedback.textContent = data.error ?? 'Erreur.';
    } else {
      if (feedback) feedback.textContent = `Invitation envoyée à ${pseudo}`;
      if (inviteInput) inviteInput.value = '';
      setTimeout(() => { if (feedback) feedback.textContent = ''; }, 3000);
    }
  } catch (err) {
    console.error('Erreur sendInvite :', err);
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
      body: JSON.stringify({
        game_id: pendingInvite.game_id,
        team_id: pendingInvite.team_id,
      })
    });
    const data = await response.json();
    if (data.ok) renderTeamMembers(data.members);
  } catch (err) {
    console.error('Erreur acceptInvite :', err);
  }
  pendingInvite = null;
}

function refuseInvite() {
  pendingInvite = null;
  if (overlayInvite) overlayInvite.classList.remove('overlay--open');
}

// ── Mise à jour de la liste des membres ───────────────────────────────────
function renderTeamMembers(members) {
  if (!teamMembers || !members) return;
  teamMembers.innerHTML = members.map(m => `
    <li class="team-member ${m.id === USER_ID ? 'team-member--me' : ''}">
      ${escapeHtml(m.pseudo)} ${m.id === USER_ID ? '(toi)' : ''}
    </li>
  `).join('');
}

function onTeamUpdate(data) {
  const members = data.members ?? data.membres;
  renderTeamMembers(members);
}

// ── Polling — vérifie si la partie a démarré toutes les 3s ───────────────
// Fallback si le broadcast WebSocket game_started ne fonctionne pas
function startPolling() {
  if (!GAME_ID) return;
  pollInterval = setInterval(async () => {
    try {
      const response = await fetch(`ajax/get_game_status.php?game_id=${GAME_ID}`);
      if (!response.ok) return;
      const data = await response.json();
      if (data.status === 'playing') {
        clearInterval(pollInterval);
        window.location.href = 'game.php';
      }
    } catch (err) {}
  }, 3000);
}


// ── WebSocket lobby ───────────────────────────────────────────────────────
function lobbyWsConnect() {
  lobbyWs = new WebSocket(WS_URL);

  lobbyWs.addEventListener('open', () => {
    lobbyReconnectDelay = 1000;
    lobbyWsSend({
      type:    'lobby_join',
      user_id: USER_ID,
      pseudo:  PSEUDO,
      game_id: GAME_ID ?? '',
      team_id: TEAM_ID ?? '',
    });
  });

  lobbyWs.addEventListener('message', (event) => {
    try {
      const data = JSON.parse(event.data);
      switch (data.type) {
        case 'invitation':   onInvitationReceived(data);          break;
        case 'team_update':  onTeamUpdate(data);                  break;
        case 'game_started':
          clearInterval(pollInterval);
          window.location.href = 'game.php';
          break;
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