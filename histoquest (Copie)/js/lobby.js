let lobbyWs = null;
let lobbyReconnectDelay = 1000;
let pollInterval = null;
let pendingInvite = null;

const inviteInput   = document.getElementById('invite-input');
const inviteBtn     = document.getElementById('invite-btn');
const teamMembers   = document.getElementById('team-members');
const overlayInvite = document.getElementById('overlay-invitation');
const inviteFrom    = document.getElementById('invite-from');
const inviteTeam    = document.getElementById('invite-team');
const inviteAccept  = document.getElementById('invite-accept');
const inviteRefuse  = document.getElementById('invite-refuse');

document.addEventListener('DOMContentLoaded', () => {
  inviteBtn?.addEventListener('click', sendInvite);
  inviteAccept?.addEventListener('click', acceptInvite);
  inviteRefuse?.addEventListener('click', refuseInvite);
  startPolling();
  lobbyWsConnect();
});

/* ── Invitation ── */
async function sendInvite() {
  const pseudo   = inviteInput?.value.trim();
  const feedback = document.getElementById('invite-feedback');
  if (!pseudo) return;

  try {
    const r    = await fetch('ajax/invite_player.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ target_pseudo: pseudo, game_id: GAME_ID, team_id: TEAM_ID }) });
    const data = await r.json();
    if (feedback) feedback.textContent = data.ok ? `Invitation envoyée à ${pseudo}` : (data.error ?? 'Erreur.');
    if (data.ok && inviteInput) inviteInput.value = '';
    if (data.ok) setTimeout(() => { if (feedback) feedback.textContent = ''; }, 3000);
  } catch {
    if (feedback) feedback.textContent = "Impossible d'envoyer l'invitation.";
  }
}

function onInvitationReceived(data) {
  pendingInvite = data;
  if (inviteFrom) inviteFrom.textContent = data.from_pseudo ?? '';
  if (inviteTeam) inviteTeam.textContent = data.team_name ?? 'une équipe';
  if (overlayInvite) {
    overlayInvite.classList.add('overlay--open');
    setTimeout(() => overlayInvite.classList.remove('overlay--open'), 30000);
  }
}

async function acceptInvite() {
  if (!pendingInvite) return;
  overlayInvite?.classList.remove('overlay--open');
  try {
    const r    = await fetch('ajax/join_game.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ game_id: pendingInvite.game_id, team_id: pendingInvite.team_id }) });
    const data = await r.json();
    if (data.ok) window.location.href = 'lobby.php';
    else alert(data.error ?? 'Impossible de rejoindre.');
  } catch { console.error('Erreur acceptInvite'); }
  pendingInvite = null;
}

function refuseInvite() {
  pendingInvite = null;
  overlayInvite?.classList.remove('overlay--open');
}

/* ── Équipe ── */
function renderTeamMembers(members) {
  if (!teamMembers || !members) return;
  teamMembers.innerHTML = members.map(m => `
    <li class="team-member ${m.id === USER_ID ? 'team-member--me' : ''}">
      ${escapeHtml(m.pseudo)} ${m.id === USER_ID ? '(toi)' : ''}
    </li>
  `).join('');
}

function onTeamUpdate(data) { renderTeamMembers(data.members ?? data.membres); }

/* ── Polling statut partie ── */
function startPolling() {
  if (!GAME_ID) return;
  pollInterval = setInterval(async () => {
    try {
      const r = await fetch(`ajax/get_game_status.php?game_id=${encodeURIComponent(GAME_ID)}`);
      if (!r.ok) return;
      const data = await r.json();
      if (data.status === 'playing') { clearInterval(pollInterval); window.location.href = 'game.php'; }
    } catch {}
  }, 1000);
}

/* ── WebSocket lobby ── */
function lobbyWsConnect() {
  if (!window.WebSocket || !WS_URL) return;
  try { lobbyWs = new WebSocket(WS_URL); } catch { return; }

  lobbyWs.addEventListener('open', () => {
    lobbyReconnectDelay = 1000;
    lobbyWsSend({ type: 'lobby_join', user_id: USER_ID, pseudo: PSEUDO, game_id: GAME_ID ?? '', team_id: TEAM_ID ?? '' });
  });

  lobbyWs.addEventListener('message', (event) => {
    try {
      const data = JSON.parse(event.data);
      switch (data.type) {
        case 'invitation':   onInvitationReceived(data); break;
        case 'team_update':  onTeamUpdate(data);         break;
        case 'game_started': clearInterval(pollInterval); window.location.href = 'game.php'; break;
      }
    } catch {}
  });

  lobbyWs.addEventListener('close', () => {
    setTimeout(() => { lobbyReconnectDelay = Math.min(lobbyReconnectDelay * 2, 30000); lobbyWsConnect(); }, lobbyReconnectDelay);
  });

  lobbyWs.addEventListener('error', () => { try { lobbyWs.close(); } catch {} });
}

function lobbyWsSend(payload) {
  if (lobbyWs && lobbyWs.readyState === WebSocket.OPEN) lobbyWs.send(JSON.stringify(payload));
}

function escapeHtml(str) {
  const d = document.createElement('div');
  d.textContent = str ?? '';
  return d.innerHTML;
}