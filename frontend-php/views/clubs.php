<?php
$filtre = (int)($_GET['categorie'] ?? 0);
$clubs = TriviaService::getAllClubs($filtre ?: null);
if (!is_array($clubs) || isset($clubs['detail'])) { $clubs = []; }
$categories = TriviaService::getAllCategories();
if (!is_array($categories) || isset($categories['detail'])) { $categories = []; }

$couleurs = [
    ['gradient' => 'linear-gradient(90deg, #2457A6, #2457A6)', 'color' => '#2457A6'],
    ['gradient' => 'linear-gradient(90deg, #2457A6, #2457A6)', 'color' => '#2457A6'],
    ['gradient' => 'linear-gradient(90deg, #2457A6, #2457A6)', 'color' => '#2457A6'],
    ['gradient' => 'linear-gradient(90deg, #2457A6, #2457A6)', 'color' => '#2457A6'],
    ['gradient' => 'linear-gradient(90deg, #2457A6, #2457A6)', 'color' => '#2457A6']
];

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
    <div class="woohp-sub">CLUBS ET ASSOCIATIONS</div>
    <div class="woohp-desc">Présentations remplies par les clubs eux-mêmes</div>
    <div class="scroll-hint">▼ SCROLL POUR CONTINUER ▼</div>
  </div>
</section>

<div class="section">
  <h2 class="section-title"> CLUBS ET ASSOCIATIONS</h2>

  <?php if (!empty($message)): ?>
    <div class="alert alert-<?= e($message_type) ?>"><?= e($message) ?></div>
  <?php endif; ?>

  <!-- Filtre par catégorie -->
  <div class="agent-tags" style="justify-content:center; margin-bottom: 30px;">
    <a class="tag" href="?action=liste" style="text-decoration:none;<?= !$filtre ? ' background:#EFF4FC; color:white;' : '' ?>">Toutes</a>
    <?php foreach ($categories as $cat): ?>
      <a class="tag" href="?action=liste&amp;categorie=<?= (int)$cat['id'] ?>" style="text-decoration:none;<?= $filtre === (int)$cat['id'] ? ' background:#EFF4FC; color:white;' : '' ?>"><?= e($cat['name']) ?></a>
    <?php endforeach; ?>
  </div>

  <!-- ========== DETAIL D'UN CLUB ========== -->
  <?php if ($action === 'detail'): ?>
    <?php if (is_array($donnees) && isset($donnees['id'])): $club = $donnees; $contact = $club['contact'] ?? []; ?>
    <div class="detail-card">
      <h3> <?= e($club['name']) ?></h3>
      <table class="detail-table">
        <tr><td>ID</td><td><?= (int)$club['id'] ?></td></tr>
        <tr><td>Catégorie</td><td><?= e($club['category']['name'] ?? 'Non classé') ?></td></tr>
        <tr><td>Description</td><td><?= nl2br(e($club['description'] ?? 'Aucune description')) ?></td></tr>
        <tr><td>E-mail</td><td><?= !empty($contact['email']) ? '<a href="mailto:' . e($contact['email']) . '" style="color:var(--primary);">' . e($contact['email']) . '</a>' : '-' ?></td></tr>
        <tr><td>Instagram</td><td><?= e($contact['instagram'] ?? '-') ?></td></tr>
        <tr><td>Site web</td><td><?php $site = safe_url($contact['website'] ?? null); echo $site ? '<a href="' . e($site) . '" style="color:var(--primary);">' . e($site) . '</a>' : '-'; ?></td></tr>
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
  <div class="agents-grid">
    <?php foreach ($clubs as $i => $club):
        $couleur = $couleurs[$i % count($couleurs)];
        $banniere = safe_url($club['banner_url'] ?? null);
        $contact = $club['contact'] ?? [];
    ?>
    <div class="agent-card">
      <div class="agent-img-wrap">
        <?php if ($banniere): ?>
          <img src="<?= e($banniere) ?>" alt="Bannière de <?= e($club['name']) ?>" loading="lazy">
        <?php else: ?>
          <div class="agent-img-fallback"></div>
        <?php endif; ?>
      </div>
      <div class="agent-color-bar" style="background: <?= $couleur['gradient'] ?>;"></div>
      <div class="agent-info">
        <div class="agent-name" style="color:<?= $couleur['color'] ?>;"><?= e(mb_strtoupper($club['name'])) ?></div>
        <div class="agent-id" style="font-size: 0.8rem; color: #ffd700; margin-bottom: 5px;">ID: <?= (int)$club['id'] ?></div>
        <div class="agent-role">Club · <?= e($club['category']['name'] ?? 'Non classé') ?></div>
        <p style="font-size:0.9rem; color:rgba(255,255,255,0.7); margin-bottom:14px;"><?= e(mb_strimwidth($club['description'] ?? '', 0, 110, '…')) ?></p>
        <div class="agent-gadget"> <?= e($contact['email'] ?? 'Pas de contact') ?></div>
        <div style="margin-top: 12px;">
          <a href="?action=detail&amp;id=<?= (int)$club['id'] ?>" class="execute-btn" style="padding: 5px 15px; font-size: 0.8rem; text-decoration: none; background: linear-gradient(135deg, #ff69b4, #ff1493);"> Voir la présentation</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div style="display: flex; gap: 15px; justify-content: center; margin: 50px 0 30px 0; flex-wrap: wrap;">
    <a href="?action=liste" class="execute-btn" style="background: linear-gradient(135deg, #ff69b4, #ff1493); text-decoration: none;"> Rafraîchir</a>
  </div>

  <!-- ========== FORMULAIRES ========== -->

  <?php if (!$auth): ?>
  <div class="mission-panel" id="connexion">
    <h3> CONNEXION (ESPACE CLUB)</h3>
    <p style="color:rgba(255,255,255,0.6); margin-bottom:16px;">Identifiant et mot de passe fournis par l'administrateur de l'université.</p>
    <form method="post" action="/">
      <input type="hidden" name="action" value="connexion">
      <div class="form-grid">
        <div class="form-group"><label>Identifiant *</label><input type="text" name="username" autocomplete="username" required></div>
        <div class="form-group"><label>Mot de passe *</label><input type="password" name="password" autocomplete="current-password" required></div>
        <button type="submit" class="execute-btn"> SE CONNECTER</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <?php if ($est_admin): ?>
  <div class="mission-panel">
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
        <button type="submit" class="execute-btn"> CRÉER</button>
      </div>
    </form>
  </div>

  <div class="mission-panel">
    <h3> SUPPRIMER UN CLUB</h3>
    <form method="post" action="/" onsubmit="return confirm('Supprimer ce club ? Ses événements seront aussi supprimés.')">
      <input type="hidden" name="action" value="supprimer_club">
      <div class="form-grid">
        <div class="form-group"><label>ID du club *</label><input type="number" name="id" placeholder="ID" required></div>
        <button type="submit" class="execute-btn"> SUPPRIMER</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <?php if ($est_club && $mon_club): $mon_contact = $mon_club['contact'] ?? []; ?>
  <div class="mission-panel">
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
        <button type="submit" class="execute-btn"> ENREGISTRER</button>
      </div>
    </form>
  </div>

  <div class="mission-panel">
    <h3> CHANGER MON MOT DE PASSE</h3>
    <form method="post" action="/">
      <input type="hidden" name="action" value="changer_mdp">
      <div class="form-grid">
        <div class="form-group"><label>Nouveau mot de passe (8 caractères min.) *</label><input type="password" name="new_password" minlength="8" autocomplete="new-password" required></div>
        <div class="form-group"><label>Confirmation *</label><input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required></div>
        <button type="submit" class="execute-btn"> MODIFIER</button>
      </div>
    </form>
  </div>
  <?php endif; ?>
</div>

<!-- STATISTIQUES -->
<div class="result-card" style="margin: 0 20px 30px 20px;">
  <div class="result-card-header"><h3 style="color:white; margin:0;"> STATISTIQUES CLUBS</h3></div>
  <div class="result-card-body">
    <p>Total : <?= count($clubs) ?> clubs affichés</p>
  </div>
</div>

<footer>
  <p>E-Trivia — Projet étudiant · Sorbonne Paris Nord · <span> MENU & CLUBS</span> · Menus fournis par <a href="https://croustillant.menu" style="color:var(--primary);">CROUStillant</a></p>
</footer>

</body>
</html>
