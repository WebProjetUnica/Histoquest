<?php
require_once 'php/config.php';
$page_title = 'Classement général';
require_once 'php/header.php';
?>

<div class="lb-page">

  <aside class="lb-sidebar">

    <div class="lb-my-standing">
      <p class="lb-my-standing__label">Ta position</p>
      <p class="lb-my-standing__rank">#12</p>
      <p class="lb-my-standing__sub">Top 25% des joueurs</p>
      <a href="results.php" class="btn btn-primary btn-full">Voir mes stats</a>
    </div>

    <div class="lb-filters">
      <p class="lb-filters__title">Période</p>
      <div class="lb-period">
        <button class="lb-period__btn lb-period__btn--active">Aujourd'hui</button>
        <button class="lb-period__btn">Cette semaine</button>
        <button class="lb-period__btn">Ce mois</button>
        <button class="lb-period__btn">Tout temps</button>
      </div>
    </div>

  </aside>

  <main class="lb-main">

    <div class="lb-header">
      <div>
        <h1 class="lb-header__title">Classement général</h1>
        <p class="lb-header__sub">Meilleurs joueurs HistoQuest — mis à jour en temps réel</p>
      </div>
    </div>

    <div class="lb-podium">

      <div class="lb-podium__place lb-podium__place--silver">
        <div class="lb-podium__avatar">NF</div>
        <p class="lb-podium__rank">2</p>
        <p class="lb-podium__pseudo">Napoléfan</p>
        <p class="lb-podium__score">4 210 pts</p>
        <p class="lb-podium__acc">96.2% précision</p>
      </div>

      <div class="lb-podium__place lb-podium__place--gold">
        <div class="lb-podium__crown">🏆</div>
        <div class="lb-podium__avatar lb-podium__avatar--gold">HK</div>
        <p class="lb-podium__rank">1</p>
        <p class="lb-podium__pseudo">HistoKing</p>
        <p class="lb-podium__score">5 870 pts</p>
        <p class="lb-podium__acc">98.1% précision</p>
      </div>

      <div class="lb-podium__place lb-podium__place--bronze">
        <div class="lb-podium__avatar">CF</div>
        <p class="lb-podium__rank">3</p>
        <p class="lb-podium__pseudo">CléopatraFan</p>
        <p class="lb-podium__score">3 890 pts</p>
        <p class="lb-podium__acc">94.7% précision</p>
      </div>

    </div>

    <table class="lb-table">
      <thead>
        <tr>
          <th>Rang</th>
          <th>Joueur</th>
          <th>Parties jouées</th>
          <th>Score total</th>
          <th>Précision</th>
          <th>Contestations gagnées</th>
        </tr>
      </thead>
      <tbody>
        <tr class="lb-table__row">
          <td class="lb-table__rank">#4</td>
          <td class="lb-table__player">
            <div class="lb-avatar-sm">RD</div>
            <span>RomainDubled</span>
          </td>
          <td>18</td>
          <td class="lb-table__score">3 410 pts</td>
          <td>91.3%</td>
          <td>3</td>
        </tr>
        <tr class="lb-table__row">
          <td class="lb-table__rank">#5</td>
          <td class="lb-table__player">
            <div class="lb-avatar-sm">VH</div>
            <span>VercingHistory</span>
          </td>
          <td>15</td>
          <td class="lb-table__score">3 100 pts</td>
          <td>89.8%</td>
          <td>1</td>
        </tr>
        <tr class="lb-table__row">
          <td class="lb-table__rank">#6</td>
          <td class="lb-table__player">
            <div class="lb-avatar-sm">AM</div>
            <span>AttilaMax</span>
          </td>
          <td>21</td>
          <td class="lb-table__score">2 980 pts</td>
          <td>87.5%</td>
          <td>5</td>
        </tr>
        <tr class="lb-table__row">
          <td class="lb-table__rank">#7</td>
          <td class="lb-table__player">
            <div class="lb-avatar-sm">JC</div>
            <span>JulesChronos</span>
          </td>
          <td>12</td>
          <td class="lb-table__score">2 750 pts</td>
          <td>92.0%</td>
          <td>2</td>
        </tr>
        <tr class="lb-table__row">
          <td class="lb-table__rank">#8</td>
          <td class="lb-table__player">
            <div class="lb-avatar-sm">PH</div>
            <span>PharaonHisto</span>
          </td>
          <td>9</td>
          <td class="lb-table__score">2 310 pts</td>
          <td>85.1%</td>
          <td>0</td>
        </tr>
        <tr class="lb-table__row lb-table__row--me">
          <td class="lb-table__rank">#12</td>
          <td class="lb-table__player">
            <div class="lb-avatar-sm lb-avatar-sm--me">TO</div>
            <span>Toi</span>
          </td>
          <td>7</td>
          <td class="lb-table__score">1 340 pts</td>
          <td>78.6%</td>
          <td>1</td>
        </tr>
      </tbody>
    </table>

    <div class="lb-pagination">
      <button class="lb-page-btn" disabled>← Précédent</button>
      <button class="lb-page-btn lb-page-btn--active">1</button>
      <button class="lb-page-btn">2</button>
      <button class="lb-page-btn">3</button>
      <span class="lb-page-dots">…</span>
      <button class="lb-page-btn">Suivant →</button>
    </div>

  </main>

</div>

<?php require_once 'php/footer.php'; ?>