let ws = null;
let wsReconnectDelay = 1000;

function wsConnect() {
  ws = new WebSocket(WS_URL);

  ws.addEventListener('open', () => {
    wsReconnectDelay = 1000;
    wsSend({ type: 'join', game_id: GAME_ID, team_id: TEAM_ID, user_id: USER_ID, pseudo: PSEUDO });
  });

  ws.addEventListener('message', (event) => {
    try { wsDispatch(JSON.parse(event.data)); }
    catch (err) { console.error('WS message invalide :', err); }
  });

  ws.addEventListener('close', () => {
    setTimeout(() => {
      wsReconnectDelay = Math.min(wsReconnectDelay * 2, 30000);
      wsConnect();
    }, wsReconnectDelay);
  });

  ws.addEventListener('error', () => ws.close());
}

function wsSend(payload) {
  if (ws && ws.readyState === WebSocket.OPEN) ws.send(JSON.stringify(payload));
}

function wsDispatch(data) {
  switch (data.type) {
    case 'vote_update':    onVoteUpdate(data);    break;
    case 'hint_vote_opened':
    case 'hint_available': onHintAvailable(data); break;
    case 'contest_opened': onContestOpened(data); break;
    case 'contest_message':onContestMessage(data);break;
    case 'revote_started': onRevoteStarted(data); break;
    case 'revote_result':  onRevoteResult(data);  break;
    case 'team_update':    onTeamUpdate(data);    break;
    case 'new_turn':       onNewTurn(data);       break;
    case 'joined': break;
    default: console.warn('WS événement inconnu :', data.type);
  }
}

document.addEventListener('DOMContentLoaded', wsConnect);

window.addEventListener('beforeunload', () => { if (ws) ws.close(); });