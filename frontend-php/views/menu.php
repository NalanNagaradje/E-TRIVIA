<?php
// $donnees (menu), $restaurants, $code, $date et $recherche viennent d'index.php
$menu_ok = is_array($donnees) && isset($donnees['meals']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>E-Trivia — Menu du Resto U</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>

<!-- NAV -->
<nav class="nav-bar">
  <div class="nav-logo">E-Trivia</div>
  <div class="nav-links">
    <a class="nav-link" href="?action=liste">Clubs</a>
    <a class="nav-link" href="?action=evenements">Événements</a>
    <a class="nav-link" href="?action=menu">Menu du RU</a>
    <a class="nav-link" href="?action=categories">Catégories</a>
    <?php if ($auth): ?>
      <span class="topsecret"> <?= e($auth['role'] === 'admin' ? 'ADMIN' : $auth['club_name']) ?></span>
      <form method="post" action="/" style="display:inline;">
        <input type="hidden" name="action" value="deconnexion">
        <button type="submit" class="nav-link">Déconnexion</button>
      </form>
    <?php else: ?>
      <a class="nav-link" href="?action=liste#connexion"> Connexion</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-content">
    <a href="?action=accueil" style="text-decoration: none;">
      <div class="logo-text">E-Trivia</div>
    </a>
    <div class="woohp-sub">MENU DU RESTAURANT UNIVERSITAIRE</div>
    <div class="woohp-desc">Consultez le menu avant de faire la queue</div>
    <div class="scroll-hint">▼ SCROLL POUR CONTINUER ▼</div>
  </div>
</section>

<div class="section">
  <h2 class="section-title"> MENU DU RESTO U</h2>

  <!-- ========== AFFICHAGE DU MENU ========== -->
  <?php if ($menu_ok): ?>
    <p style="text-align:center; color:rgba(255,255,255,0.6); margin-bottom: 30px;">Menu du <?= e(date('d/m/Y', strtotime($donnees['date']))) ?></p>
    <?php if (!empty($donnees['warning'])): ?>
      <div class="alert alert-warning"> <?= e($donnees['warning']) ?></div>
    <?php endif; ?>
    <div class="agents-grid">
      <?php foreach ($donnees['meals'] as $repas): ?>
      <div class="agent-card">
        <div class="agent-color-bar"></div>
        <div class="agent-info">
          <div class="agent-name" style="color:var(--text);"><?= e(mb_strtoupper($repas['name'])) ?></div>
          <?php foreach ($repas['categories'] as $categorie): ?>
            <div class="agent-role" style="margin-top: 14px;"><?= e($categorie['name']) ?></div>
            <ul style="padding-left: 20px; margin: 0;">
              <?php foreach ($categorie['dishes'] as $plat): ?><li><?= e($plat) ?></li><?php endforeach; ?>
            </ul>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="alert alert-info">ℹ <?= e(is_array($donnees) && isset($donnees['detail']) ? detail_en_texte($donnees['detail']) : "Le menu est indisponible pour le moment") ?></div>
  <?php endif; ?>

  <!-- ========== FORMULAIRES ========== -->
  <div class="mission-panel">
    <h3> CHANGER DE JOUR</h3>
    <form method="get" action="/">
      <input type="hidden" name="action" value="menu">
      <div class="form-grid">
        <div class="form-group"><label>Date</label><input type="date" name="date" value="<?= e($date) ?>"></div>
        <button type="submit" class="execute-btn"> AFFICHER</button>
      </div>
    </form>
  </div>

  <div class="mission-panel">
    <h3> CHOISIR UN RESTAURANT</h3>
    <form method="get" action="/">
      <input type="hidden" name="action" value="menu">
      <div class="form-grid">
        <div class="form-group"><label>Nom ou ville du restaurant</label><input type="text" name="q" value="<?= e($recherche) ?>" placeholder="Ex: Villetaneuse" required></div>
        <button type="submit" class="execute-btn"> RECHERCHER</button>
      </div>
    </form>

    <?php if ($restaurants !== null): ?>
      <?php if (!is_array($restaurants) || isset($restaurants['detail'])): ?>
        <div class="alert alert-error" style="margin-top:20px;"> <?= e(is_array($restaurants) ? detail_en_texte($restaurants['detail']) : "L'API est injoignable") ?></div>
      <?php elseif (empty($restaurants)): ?>
        <p style="margin-top:20px;">Aucun restaurant trouvé pour « <?= e($recherche) ?> ».</p>
      <?php else: ?>
        <table class="data-table" style="margin-top:20px;">
          <thead><tr><th>Restaurant</th><th>Adresse</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($restaurants as $resto): if (empty($resto['code'])) continue; ?>
            <tr>
              <td><?= e($resto['name']) ?></td>
              <td><?= e($resto['address']) ?></td>
              <td><a href="?action=menu&amp;restaurant=<?= urlencode((string)$resto['code']) ?>" style="color:var(--gold);">Choisir</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<footer>
  <p>E-Trivia — Projet étudiant · Sorbonne Paris Nord · <span> MENU & CLUBS</span> · Menus fournis par <a href="https://croustillant.menu" style="color:var(--primary);">CROUStillant</a></p>
</footer>

</body>
</html>
