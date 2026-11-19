<?php
  $categories = TriviaService::getAllCategories();
  if (!is_array($categories) || isset($categories['detail'])) { $categories = []; }
  $clubs = TriviaService::getAllClubs();
  if (!is_array($clubs) || isset($clubs['detail'])) { $clubs = []; }

  // Nombre de clubs par catégorie
  $nb_par_categorie = [];
  foreach ($clubs as $club) {
      $cid = $club['category_id'] ?? 0;
      $nb_par_categorie[$cid] = ($nb_par_categorie[$cid] ?? 0) + 1;
  }
  $est_admin = $auth && $auth['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Trivia — Catégories</title>
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

    <!-- NOM SECTION -->
    <section class="nom-section">
      <div class="nom-section-content">
        <a href="?action=accueil" style="text-decoration: none;">
          <div class="logo-text">E-Trivia</div>
        </a>
        <div class="woohp-sub">CATÉGORIES DE CLUBS</div>
        <div class="woohp-desc">Sport, culture, musique, informatique...</div>
        <div class="scroll-hint">▼ SCROLL POUR CONTINUER ▼</div>
      </div>
    </section>

    <div class="section">
      <h2 class="section-title"> CATÉGORIES</h2>

      <?php if (!empty($message)): ?>
        <div class="alert alert-<?= e($message_type) ?>"><?= e($message) ?></div>
      <?php endif; ?>

      <!-- ========== AFFICHAGE DES CATÉGORIES ========== -->
      <?php if (!empty($categories)): ?>
      <div class="result-card" style="margin-bottom: 40px;">
        <div class="result-card-header"><h3 style="color:white; margin:0;"> LISTE DES CATÉGORIES</h3></div>
        <div class="result-card-body">
          <div class="agents-grid">
            <?php foreach ($categories as $cat): ?>
            <div class="agent-card">
              <div style="padding: 20px; text-align: center;">
                <div style="font-size: 3rem;"></div>
                <div class="agent-name" style="color: var(--primary);"><?= e(mb_strtoupper($cat['name'])) ?></div>
                <div class="agent-role">Catégorie n°<?= (int)$cat['id'] ?></div>
                <div class="agent-gadget" style="margin-top: 10px;"> <?= (int)($nb_par_categorie[$cat['id']] ?? 0) ?> club(s)</div>
                <div style="margin-top: 12px;">
                  <a href="?action=liste&amp;categorie=<?= (int)$cat['id'] ?>" class="execute-btn" style="padding: 5px 15px; font-size: 0.8rem; text-decoration: none; background: linear-gradient(135deg, #ff69b4, #ff1493);">Voir les clubs</a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php else: ?>
      <div class="result-card" style="margin-bottom: 40px;">
        <div class="result-card-header"><h3 style="color:white; margin:0;"> LISTE DES CATÉGORIES</h3></div>
        <div class="result-card-body"><p>Aucune catégorie enregistrée pour le moment.</p></div>
      </div>
      <?php endif; ?>

      <div style="display: flex; gap: 15px; justify-content: center; margin: 30px 0 30px 0; flex-wrap: wrap;">
        <a href="?action=categories" class="execute-btn" style="background: linear-gradient(135deg, #ff69b4, #ff1493); text-decoration: none;"> Rafraîchir</a>
      </div>

      <?php if ($est_admin): ?>
      <div class="mission-panel">
        <h3> AJOUTER UNE CATÉGORIE</h3>
        <form method="post" action="/">
          <input type="hidden" name="action" value="ajouter_categorie">
          <div class="form-grid">
            <div class="form-group"><label>Nom *</label><input type="text" name="name" placeholder="Ex: Théâtre" required minlength="2" maxlength="50"></div>
            <button type="submit" class="execute-btn"> AJOUTER</button>
          </div>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <footer>
      <p>E-Trivia — Projet étudiant · Sorbonne Paris Nord · <span> MENU & CLUBS</span> · Menus fournis par <a href="https://croustillant.menu" style="color:var(--primary);">CROUStillant</a></p>
    </footer>
  </body>
</html>
