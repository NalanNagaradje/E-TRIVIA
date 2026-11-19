<?php
  $passes = isset($_GET['passes']);
  $events = TriviaService::getAllEvents($passes);
  if (!is_array($events) || isset($events['detail'])) { $events = []; }
  $est_club = $auth && $auth['role'] === 'club';
  $est_admin = $auth && $auth['role'] === 'admin';
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Trivia — Événements</title>
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
      <div class="woohp-sub">ÉVÉNEMENTS DES CLUBS</div>
      <div class="woohp-desc">Soirées, tournois, concerts : tout ce qui se passe sur le campus</div>
      <div class="scroll-hint">▼ SCROLL POUR CONTINUER ▼</div>
    </div>
  </section>

  <div class="section">
    <h2 class="section-title"> ÉVÉNEMENTS</h2>

    <?php if (!empty($message)): ?>
      <div class="alert alert-<?= e($message_type) ?>"><?= e($message) ?></div>
    <?php endif; ?>

    <!-- ========== AFFICHAGE DES ÉVÉNEMENTS ========== -->
    <?php if (!empty($events)): ?>
    <div class="result-card" style="margin-bottom: 40px;">
      <div class="result-card-header">
        <h3 style="color:white; margin:0;"> <?= $passes ? 'TOUS LES ÉVÉNEMENTS' : 'ÉVÉNEMENTS À VENIR' ?></h3>
      </div>
      <div class="result-card-body" style="overflow-x:auto;">
        <table class="data-table">
          <thead>
            <tr><th>ID</th><th>Événement</th><th>Date</th><th>Lieu</th><th>Organisé par</th></tr>
          </thead>
          <tbody>
            <?php foreach ($events as $ev): ?>
            <tr>
              <td>#<?= (int)$ev['id'] ?></td>
              <td>
                <strong><?= e($ev['title']) ?></strong>
                <?php if (!empty($ev['description'])): ?><br><span style="color:rgba(255,255,255,0.6); font-size:0.85rem;"><?= e($ev['description']) ?></span><?php endif; ?>
              </td>
              <td><?= e(format_date($ev['date'])) ?></td>
              <td><?= e($ev['location'] ?? '-') ?></td>
              <td>
                <?php foreach ($ev['clubs'] as $k => $c): ?>
                  <?= $k > 0 ? ', ' : '' ?><a href="?action=detail&amp;id=<?= (int)$c['id'] ?>" style="color:var(--primary);"><?= e($c['name']) ?></a>
                <?php endforeach; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php else: ?>
    <div class="result-card" style="margin-bottom: 40px;">
      <div class="result-card-header"><h3 style="color:white; margin:0;"> LISTE DES ÉVÉNEMENTS</h3></div>
      <div class="result-card-body"><p>Aucun événement pour le moment.</p></div>
    </div>
    <?php endif; ?>

    <div style="display: flex; gap: 15px; justify-content: center; margin: 30px 0 30px 0; flex-wrap: wrap;">
      <a href="?action=evenements" class="execute-btn" style="background: linear-gradient(135deg, #ff69b4, #ff1493); text-decoration: none;"> À venir</a>
      <a href="?action=evenements&amp;passes=1" class="execute-btn" style="background: linear-gradient(135deg, #9c27b0, #7b1fa2); text-decoration: none;"> Voir aussi les passés</a>
    </div>

    <!-- ========== FORMULAIRES ========== -->
    <?php if (!$auth): ?>
    <div class="alert alert-info"> Les clubs peuvent ajouter leurs événements après <a href="?action=liste#connexion" style="color:white; text-decoration:underline;">connexion</a>.</div>
    <?php endif; ?>

    <?php if ($est_club): ?>
    <div class="mission-panel">
      <h3> AJOUTER UN ÉVÉNEMENT</h3>
      <form method="post" action="/">
        <input type="hidden" name="action" value="ajouter_evenement">
        <div class="form-grid">
          <div class="form-group"><label>Titre *</label><input type="text" name="title" required maxlength="200"></div>
          <div class="form-group"><label>Date et heure *</label><input type="datetime-local" name="date" required></div>
          <div class="form-group"><label>Lieu</label><input type="text" name="location" maxlength="200"></div>
          <div class="form-group"><label>Description</label><input type="text" name="description"></div>
          <button type="submit" class="execute-btn"> AJOUTER</button>
        </div>
      </form>
    </div>

    <div class="mission-panel">
      <h3> CO-ORGANISER UN ÉVÉNEMENT</h3>
      <form method="post" action="/">
        <input type="hidden" name="action" value="participer">
        <div class="form-grid">
          <div class="form-group"><label>ID de l'événement *</label><input type="number" name="event_id" placeholder="ID événement" required></div>
          <button type="submit" class="execute-btn"> REJOINDRE</button>
        </div>
      </form>
    </div>

    <div class="mission-panel">
      <h3> RETIRER MON CLUB D'UN ÉVÉNEMENT</h3>
      <form method="post" action="/" onsubmit="return confirm('Confirmer le retrait ? Sans autre organisateur, l\'événement sera supprimé.')">
        <input type="hidden" name="action" value="retirer_participation">
        <div class="form-grid">
          <div class="form-group"><label>ID de l'événement *</label><input type="number" name="event_id" placeholder="ID événement" required></div>
          <button type="submit" class="execute-btn"> RETIRER</button>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <?php if ($auth): ?>
    <div class="mission-panel">
      <h3> SUPPRIMER UN ÉVÉNEMENT</h3>
      <form method="post" action="/" onsubmit="return confirm('Confirmer la suppression ?')">
        <input type="hidden" name="action" value="supprimer_evenement">
        <div class="form-grid">
          <div class="form-group"><label>ID de l'événement *</label><input type="number" name="id" placeholder="ID événement" required></div>
          <button type="submit" class="execute-btn"> SUPPRIMER</button>
        </div>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <!-- STATISTIQUES -->
  <div class="result-card" style="margin: 0 20px 30px 20px;">
    <div class="result-card-header"><h3 style="color:white; margin:0;"> STATISTIQUES ÉVÉNEMENTS</h3></div>
    <div class="result-card-body">
      <p>Total : <?= count($events) ?> événements affichés</p>
    </div>
  </div>

  <footer>
    <p>E-Trivia — Projet étudiant · Sorbonne Paris Nord · <span> MENU & CLUBS</span> · Menus fournis par <a href="https://croustillant.menu" style="color:var(--primary);">CROUStillant</a></p>
  </footer>

  </body>
</html>
