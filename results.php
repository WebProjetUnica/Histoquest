<?php
require_once 'php/config.php';
$page_title = 'Résultats';
require_once 'php/header.php';
?>

<div class="results-page">

  <div class="results-header">
    <p class="results-header__label">Fin de partie</p>
    <h1 class="results-header__title">Classement final</h1>
    <p class="results-header__sub">7 tours · scores individuels</p>
  </div>

  <div class="podium">
    <div class="podium__place podium__place--silver">
      <div class="podium__medal">🥈</div>
      <p class="podium__pseudo">Napoléfan</p>
      <p class="podium__score">21 pts</p>
    </div>
    <div class="podium__place podium__place--gold">
      <div class="podium__medal">🥇</div>
      <p class="podium__pseudo">HistoKing</p>
      <p class="podium__score">34 pts</p>
    </div>
    <div class="podium__place podium__place--bronze">
      <div class="podium__medal">🥉</div>
      <p class="podium__pseudo">CléopatraFan</p>
      <p class="podium__score">18 pts</p>
    </div>
  </div>

  <div class="results-content">

    <section class="results-rounds">
      <div class="results-rounds__header">
        <h2 class="results-section-title">Détail des tours</h2>
        <span class="results-rounds__total">7 tours au total</span>
      </div>
      <table class="rounds-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Événement historique</th>
            <th>Bonne réponse</th>
            <th>Tes points</th>
          </tr>
        </thead>
        <tbody>
          <tr class="rounds-table__row rounds-table__row--correct">
            <td class="rounds-table__num">1</td>
            <td class="rounds-table__event">Jules César franchit le Rubicon</td>
            <td class="rounds-table__answer"><span class="answer-tag">🇮🇹 Italie</span></td>
            <td class="rounds-table__points rounds-table__points--pos">+3</td>
          </tr>
          <tr class="rounds-table__row rounds-table__row--correct">
            <td class="rounds-table__num">2</td>
            <td class="rounds-table__event">Signature de la Magna Carta</td>
            <td class="rounds-table__answer"><span class="answer-tag">🇬🇧 Royaume-Uni</span></td>
            <td class="rounds-table__points rounds-table__points--pos">+1</td>
          </tr>
          <tr class="rounds-table__row rounds-table__row--wrong">
            <td class="rounds-table__num">3</td>
            <td class="rounds-table__event">Bataille de Waterloo</td>
            <td class="rounds-table__answer"><span class="answer-tag">🇧🇪 Belgique</span></td>
            <td class="rounds-table__points rounds-table__points--zero">0</td>
          </tr>
          <tr class="rounds-table__row rounds-table__row--correct">
            <td class="rounds-table__num">4</td>
            <td class="rounds-table__event">Chute du mur de Berlin</td>
            <td class="rounds-table__answer"><span class="answer-tag">🇩🇪 Allemagne</span></td>
            <td class="rounds-table__points rounds-table__points--pos">+3</td>
          </tr>
          <tr class="rounds-table__row rounds-table__row--correct">
            <td class="rounds-table__num">5</td>
            <td class="rounds-table__event">Restauration Meiji</td>
            <td class="rounds-table__answer"><span class="answer-tag">🇯🇵 Japon</span></td>
            <td class="rounds-table__points rounds-table__points--pos">+3</td>
          </tr>
          <tr class="rounds-table__row rounds-table__row--wrong">
            <td class="rounds-table__num">6</td>
            <td class="rounds-table__event">Déclaration d'indépendance américaine</td>
            <td class="rounds-table__answer"><span class="answer-tag">🇺🇸 États-Unis</span></td>
            <td class="rounds-table__points rounds-table__points--zero">0</td>
          </tr>
          <tr class="rounds-table__row rounds-table__row--correct">
            <td class="rounds-table__num">7</td>
            <td class="rounds-table__event">Alunissage Apollo 11</td>
            <td class="rounds-table__answer"><span class="answer-tag">🇺🇸 États-Unis</span></td>
            <td class="rounds-table__points rounds-table__points--pos">+3</td>
          </tr>
        </tbody>
        <tfoot>
          <tr class="rounds-table__total">
            <td colspan="3">Total</td>
            <td class="rounds-table__points rounds-table__points--pos">13 pts</td>
          </tr>
        </tfoot>
      </table>
    </section>

    <aside class="results-aside">

      <div class="results-stats">
        <h2 class="results-section-title">Tes stats</h2>
        <div class="stats-list">
          <div class="stat-item">
            <span class="stat-item__label">Bonnes réponses</span>
            <span class="stat-item__value stat-item__value--pos">5 / 7</span>
          </div>
          <div class="stat-item">
            <span class="stat-item__label">Avec indice</span>
            <span class="stat-item__value">1 fois</span>
          </div>
          <div class="stat-item">
            <span class="stat-item__label">Contestations gagnées</span>
            <span class="stat-item__value stat-item__value--pos">1</span>
          </div>
          <div class="stat-item">
            <span class="stat-item__label">Ton classement</span>
            <span class="stat-item__value">4e / 5</span>
          </div>
        </div>
      </div>

      <div class="top-contester">
        <h2 class="results-section-title">⚡ Top contestataire</h2>
        <p class="top-contester__sub">Contestation la plus réussie</p>
        <div class="top-contester__card">
          <div class="top-contester__avatar">HK</div>
          <div class="top-contester__info">
            <p class="top-contester__pseudo">HistoKing</p>
            <p class="top-contester__detail">A retourné 2 votes en sa faveur</p>
          </div>
          <div class="top-contester__wins">
            <span class="top-contester__wins-num">2</span>
            <span class="top-contester__wins-label">gains</span>
          </div>
        </div>
      </div>

      <div class="results-ranking">
        <h2 class="results-section-title">Classement complet</h2>
        <ul class="ranking-list">
          <li class="ranking-item ranking-item--gold">
            <span class="ranking-item__pos">1</span>
            <span class="ranking-item__pseudo">HistoKing</span>
            <span class="ranking-item__score">34 pts</span>
          </li>
          <li class="ranking-item ranking-item--silver">
            <span class="ranking-item__pos">2</span>
            <span class="ranking-item__pseudo">Napoléfan</span>
            <span class="ranking-item__score">21 pts</span>
          </li>
          <li class="ranking-item ranking-item--bronze">
            <span class="ranking-item__pos">3</span>
            <span class="ranking-item__pseudo">CléopatraFan</span>
            <span class="ranking-item__score">18 pts</span>
          </li>
          <li class="ranking-item ranking-item--me">
            <span class="ranking-item__pos">4</span>
            <span class="ranking-item__pseudo">Toi</span>
            <span class="ranking-item__score">13 pts</span>
          </li>
          <li class="ranking-item">
            <span class="ranking-item__pos">5</span>
            <span class="ranking-item__pseudo">RomainDubled</span>
            <span class="ranking-item__score">9 pts</span>
          </li>
        </ul>
      </div>

    </aside>

  </div>

  <div class="results-actions">
    <a href="lobby.php" class="btn btn-primary">← Retour au lobby</a>
    <a href="leaderboard.php" class="btn btn-ghost">🏆 Classement général</a>
  </div>

</div>

<?php require_once 'php/footer.php'; ?>