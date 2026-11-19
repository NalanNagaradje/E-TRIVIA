<?php
  $filtre = (int)($_GET['categorie'] ?? 0);
  $clubs = TriviaService::getAllClubs($filtre ?: null);
  if (!is_array($clubs) || isset($clubs['detail'])) { $clubs = []; }
  $categories = TriviaService::getAllCategories();
  if (!is_array($categories) || isset($categories['detail'])) { $categories = []; }

  $est_admin = $auth && $auth['role'] === 'admin';
  $est_club = $auth && $auth['role'] === 'club';

  // Si on est connecté en tant que club, on pré-remplit le formulaire de modification
  $mon_club = $est_club ? TriviaService::getClubById($auth['club_id']) : null;
  if (!is_array($mon_club) || isset($mon_club['detail'])) { $mon_club = null; }
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Trivia — Clubs et associations</title>
    <link rel="icon" type="image/png" href="../assets/ET.png">
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
        <div class="header-title">CLUBS ET ASSOCIATIONS</div>
        <div class="header-subtitle">Présentations remplies par les clubs eux-mêmes</div>
      </div>
    </section>

    <div class="section">
      <h2 class="section-title"> CLUBS ET ASSOCIATIONS</h2>

      <?php if (!empty($message)): ?>
        <div class="alert alert-<?= e($message_type) ?>"><?= e($message) ?></div>
      <?php endif; ?>

      <!-- Filtre par catégorie -->
      <div class="filter-tags">
        <a class="tag<?= !$filtre ? ' active' : '' ?>" href="?action=liste">Toutes</a>
        <?php foreach ($categories as $cat): ?>
          <a class="tag<?= $filtre === (int)$cat['id'] ? ' active' : '' ?>" href="?action=liste&amp;categorie=<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?></a>
        <?php endforeach; ?>
      </div>

      <!-- ========== DETAIL D'UN CLUB ========== -->
      <?php if ($action === 'detail'): ?>
        <?php if (is_array($donnees) && isset($donnees['id'])): $club = $donnees; $contact = $club['contact'] ?? []; ?>
        
        <?php $banniere = safe_url($club['banner_url'] ?? null); ?>
        <?php if ($banniere): ?>
          <div class="club-banner">
            <img class="club-banner-bg" src="<?= e($banniere) ?>" alt="" aria-hidden="true">
            <img class="club-banner-img" src="<?= e($banniere) ?>" alt="Bannière de <?= e($club['name']) ?>">
          </div>
        <?php endif; ?>
        
        <div class="detail-card">
          <h3> <?= e($club['name']) ?></h3>
          <table class="detail-table">
            <tr><td>ID</td><td><?= (int)$club['id'] ?></td></tr>
            <tr><td>Catégorie</td><td><?= e($club['category']['name'] ?? 'Non classé') ?></td></tr>
            <tr><td>Description</td><td><?= nl2br(e($club['description'] ?? 'Aucune description')) ?></td></tr>
            <tr><td>E-mail</td><td><?= !empty($contact['email']) ? '<a href="mailto:' . e($contact['email']) . '">' . e($contact['email']) . '</a>' : '-' ?></td></tr>
            <tr><td>Instagram</td><td><?= e($contact['instagram'] ?? '-') ?></td></tr>
            <tr><td>Site web</td><td><?php $site = safe_url($contact['website'] ?? null); echo $site ? '<a href="' . e($site) . '">' . e($site) . '</a>' : '-'; ?></td></tr>
            <tr><td>Événements</td><td>
              <?php if (!empty($club['events'])): ?>
                <ul style="margin:0; padding-left:20px;">
                  <?php foreach ($club['events'] as $ev): ?>
                    <li><?= e($ev['title']) ?> — <?= e(format_date($ev['date'])) ?><?= !empty($ev['location']) ? ' (' . e($ev['location']) . ')' : '' ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                Aucun événement
              <?php endif; ?>
            </td></tr>
          </table>
        </div>
        <?php else: ?>
          <div class="alert alert-error"> Club introuvable</div>
        <?php endif; ?>
      <?php endif; ?>

      <!-- ========== AFFICHAGE DES CLUBS ========== -->
      <?php if (empty($clubs)): ?>
        <div class="result-card"><div class="result-card-body"><p>Aucun club pour le moment.</p></div></div>
      <?php else: ?>
        <div class="cards-grid">
          <?php foreach ($clubs as $club):
              $banniere = safe_url($club['banner_url'] ?? null);
              $contact = $club['contact'] ?? [];
          ?>
          <div class="card">
            <div class="card-img-wrap">
              <?php if ($banniere): ?>
                <img src="<?= e($banniere) ?>" alt="Bannière de <?= e($club['name']) ?>" loading="lazy">
              <?php else: ?>
                <div class="card-img-fallback"></div>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <div class="card-title"><?= e(mb_strtoupper($club['name'])) ?></div>
              <div class="card-id">ID: <?= (int)$club['id'] ?></div>
              <div class="card-subtitle">Club · <?= e($club['category']['name'] ?? 'Non classé') ?></div>
              <p class="card-text"><?= e(mb_strimwidth($club['description'] ?? '', 0, 110, '…')) ?></p>
              <div class="card-meta"> <?= e($contact['email'] ?? 'Pas de contact') ?></div>
              <div style="margin-top: 12px;">
                <a href="?action=detail&amp;id=<?= (int)$club['id'] ?>" class="btn btn-small"> Voir la présentation</a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="btn-row">
        <a href="?action=liste" class="btn"> Rafraîchir</a>
      </div>

      <!-- ========== FORMULAIRES ========== -->

      <?php if (!$auth): ?>
        <div class="form-panel" id="connexion">
          <h3> CONNEXION (ESPACE CLUB)</h3>
          <p class="text-muted" style="margin-bottom:16px;">Identifiant et mot de passe fournis par l'administrateur de l'université.</p>
          <form method="post" action="/">
            <input type="hidden" name="action" value="connexion">
            <div class="form-grid">
              <div class="form-group"><label>Identifiant *</label><input type="text" name="username" autocomplete="username" required></div>
              <div class="form-group"><label>Mot de passe *</label><input type="password" name="password" autocomplete="current-password" required></div>
              <button type="submit" class="btn"> SE CONNECTER</button>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <?php if ($est_admin): ?>
        <div class="form-panel">
          <h3> CRÉER UN CLUB</h3>
          <form method="post" action="/">
            <input type="hidden" name="action" value="ajouter_club">
            <div class="form-grid">
              <div class="form-group"><label>Nom du club *</label><input type="text" name="name" required maxlength="100"></div>
              <div class="form-group"><label>Identifiant de connexion *</label><input type="text" name="username" required pattern="[A-Za-z0-9._-]{3,50}" placeholder="Ex: club-echecs"></div>
              <div class="form-group"><label>Mot de passe initial *</label><input type="text" name="password" required minlength="8" autocomplete="off"></div>
              <div class="form-group"><label>Catégorie</label>
                <select name="category_id">
                  <option value="">— Aucune —</option>
                  <?php foreach ($categories as $cat): ?><option value="<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="form-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
              <div class="form-group"><label>Image de bannière (https://…)</label><input type="url" name="banner_url"></div>
              <div class="form-group"><label>E-mail</label><input type="email" name="email"></div>
              <div class="form-group"><label>Instagram</label><input type="text" name="instagram" placeholder="@moncub"></div>
              <div class="form-group"><label>Site web</label><input type="url" name="website"></div>
              <button type="submit" class="btn"> CRÉER</button>
            </div>
          </form>
        </div>

        <div class="form-panel">
          <h3> SUPPRIMER UN CLUB</h3>
          <form method="post" action="/" onsubmit="return confirm('Supprimer ce club ? Ses événements seront aussi supprimés.')">
            <input type="hidden" name="action" value="supprimer_club">
            <div class="form-grid">
              <div class="form-group"><label>ID du club *</label><input type="number" name="id" placeholder="ID" required></div>
              <button type="submit" class="btn"> SUPPRIMER</button>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <?php if ($est_club && $mon_club): $mon_contact = $mon_club['contact'] ?? []; ?>
        <div class="form-panel">
          <h3> MODIFIER LA PRÉSENTATION DE MON CLUB</h3>
          <form method="post" action="/">
            <input type="hidden" name="action" value="modifier_club">
            <div class="form-grid">
              <div class="form-group"><label>Nom du club</label><input type="text" name="name" value="<?= e($mon_club['name']) ?>" maxlength="100"></div>
              <div class="form-group"><label>Catégorie</label>
                <select name="category_id">
                  <option value="">— Aucune —</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>"<?= ((int)($mon_club['category_id'] ?? 0) === (int)$cat['id']) ? ' selected' : '' ?>><?= e($cat['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group"><label>Description</label><textarea name="description" rows="4"><?= e($mon_club['description'] ?? '') ?></textarea></div>
              <div class="form-group"><label>Image de bannière (https://…)</label><input type="url" name="banner_url" value="<?= e($mon_club['banner_url'] ?? '') ?>"></div>
              <div class="form-group"><label>E-mail</label><input type="email" name="email" value="<?= e($mon_contact['email'] ?? '') ?>"></div>
              <div class="form-group"><label>Instagram</label><input type="text" name="instagram" value="<?= e($mon_contact['instagram'] ?? '') ?>"></div>
              <div class="form-group"><label>Site web</label><input type="url" name="website" value="<?= e($mon_contact['website'] ?? '') ?>"></div>
              <button type="submit" class="btn"> ENREGISTRER</button>
            </div>
          </form>
        </div>

        <div class="form-panel">
          <h3> CHANGER MON MOT DE PASSE</h3>
          <form method="post" action="/">
            <input type="hidden" name="action" value="changer_mdp">
            <div class="form-grid">
              <div class="form-group"><label>Nouveau mot de passe (8 caractères min.) *</label><input type="password" name="new_password" minlength="8" autocomplete="new-password" required></div>
              <div class="form-group"><label>Confirmation *</label><input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required></div>
              <button type="submit" class="btn"> MODIFIER</button>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <!-- STATISTIQUES -->
    <div class="result-card" style="margin: 0 20px 30px 20px;">
      <div class="result-card-header"><h3> STATISTIQUES CLUBS</h3></div>
      <div class="result-card-body">
        <p>Total : <?= count($clubs) ?> clubs affichés</p>
      </div>
    </div>

    <footer>
      <p>E-Trivia — Projet étudiant · Sorbonne Paris Nord · <span> MENU & CLUBS</span> · Menus fournis par <a href="https://croustillant.menu">CROUStillant</a></p>
    </footer>

  </body>
</html>