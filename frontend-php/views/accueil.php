<?php
  $clubs = TriviaService::getAllClubs();
  $events = TriviaService::getAllEvents();
  $nb_clubs = (is_array($clubs) && !isset($clubs['detail'])) ? count($clubs) : 0;
  $nb_events = (is_array($events) && !isset($events['detail'])) ? count($events) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Trivia — Accueil</title>
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
          <span class="user-badge"> <?= e($auth['role'] === 'admin' ? 'ADMIN' : $auth['club_name']) ?></span>
          <form method="post" action="/" style="display:inline;">
            <input type="hidden" name="action" value="deconnexion">
            <button type="submit" class="nav-link">Déconnexion</button>
          </form>
        <?php else: ?>
          <a class="nav-link" href="?action=liste#connexion"> Connexion</a>
        <?php endif; ?>
      </div>
    </nav>

    <!-- EN-TÊTE -->
    <section class="page-header">
      <div class="page-header-content">
        <a href="?action=accueil" style="text-decoration: none;">
          <div class="header-logo">E-Trivia</div>
        </a>
        <div class="header-title">MENU DU RU & CLUBS DE L'UNIVERSITÉ</div>
        <div class="header-subtitle">Sorbonne Paris Nord · Villetaneuse</div>
        <div class="header-actions">
          <a href="?action=menu" class="btn btn-large">
            VOIR LE MENU
          </a>
          <a href="?action=liste" class="btn btn-large">
            VOIR LES CLUBS
          </a>
        </div>
      </div>
    </section>

    <div class="section" style="padding-bottom: 0;">
      <?php if (!empty($message)): ?>
        <div class="alert alert-<?= e($message_type) ?>"><?= e($message) ?></div>
      <?php endif; ?>
    </div>

    <!-- PETITE SECTION D'INFORMATION -->
    <div class="info-section">
      <div class="info-text">
        <h2> FINI LA QUEUE <span>POUR RIEN</span></h2>
        <p>La file d'attente du restaurant universitaire peut durer 20 à 30 minutes. Consultez le menu du jour depuis votre téléphone et décidez avant de partir.</p>
        <div class="badge-row">
          <span class="badge">⏱ Jusqu'à 10 h gagnées par mois</span>
          <span class="badge"> Menu mis à jour chaque jour</span>
        </div>
      </div>
      <div class="info-text">
        <h2> DES CLUBS <span>À DÉCOUVRIR</span></h2>
        <p>Chaque association présente sa bannière, sa description, ses événements et ses moyens de contact. Trouvez celle qui vous ressemble et engagez-vous dans la vie du campus.</p>
        <div class="badge-row">
          <span class="badge"> <?= (int)$nb_clubs ?> clubs et associations</span>
          <span class="badge"> <?= (int)$nb_events ?> événements à venir</span>
        </div>
      </div>
    </div>

    <footer>
      <p>E-Trivia — Projet étudiant · Sorbonne Paris Nord · <span> MENU & CLUBS</span> · Menus fournis par <a href="https://croustillant.menu">CROUStillant</a></p>
    </footer>
  </body>
</html>