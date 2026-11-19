<?php
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();

    require_once 'config/config.php';
    require_once 'services/TriviaService.php';

    // ========== OUTILS ==========
    function e($valeur){
        return htmlspecialchars((string)$valeur, ENT_QUOTES, 'UTF-8');
    }

    function nullable($valeur){
        $valeur = trim((string)$valeur);
        return $valeur === '' ? null : $valeur;
    }

    function safe_url($url){
        return ($url && preg_match('#^https?://#i', $url)) ? $url : null;
    }

    function format_date($iso){
        $ts = strtotime($iso);
        return $ts ? date('d/m/Y H:i', $ts) : $iso;
    }

    // Transforme le champ "detail" d'une erreur de l'API en texte lisible
    function detail_en_texte($detail){
        if (is_string($detail)) return $detail;
        if (is_array($detail)) {
            $messages = [];
            foreach ($detail as $d) {
                $messages[] = is_array($d) ? ($d['msg'] ?? 'Valeur invalide') : (string)$d;
            }
            return implode(' / ', $messages);
        }
        return 'Une erreur est survenue';
    }

    $resultat = '';
    $donnees = null;
    $vue = 'accueil';
    $auth = $_SESSION['auth'] ?? null;

    $action = $_POST['action'] ?? ($_GET['action'] ?? 'accueil');
    $est_post = ($_SERVER['REQUEST_METHOD'] === 'POST');

    // Les actions qui modifient des données ne passent QUE par un formulaire POST
    $actions_post = ['connexion', 'deconnexion', 'ajouter_club', 'modifier_club', 'changer_mdp', 'supprimer_club',
                    'ajouter_evenement', 'supprimer_evenement', 'participer', 'retirer_participation', 'ajouter_categorie'];
    // Celles qui demandent d'être connecté
    $actions_connectees = array_diff($actions_post, ['connexion', 'deconnexion']);

    if (in_array($action, $actions_post, true) && !$est_post) {
        $action = 'accueil';
    }
    if (in_array($action, $actions_connectees, true) && !$auth) {
        $action = 'refuse';
    }

    switch ($action) {
        case 'accueil': // retour accueil
            $vue = 'accueil';
            break;

        // ========== REQUÊTES GET ==========
        case 'liste':
            $resultat = "Liste des clubs et associations";
            $vue = 'clubs';
            break;

        case 'detail':
            $id = (int)($_GET['id'] ?? 0);
            $donnees = TriviaService::getClubById($id);
            $resultat = "Présentation du club ID $id";
            $vue = 'clubs';
            break;

        case 'evenements':
            $resultat = "Liste des événements";
            $vue = 'evenements';
            break;

        case 'categories':
            $resultat = "Liste des catégories";
            $vue = 'categories';
            break;

        case 'menu':
            // Le restaurant choisi est mémorisé dans la session
            if (!empty($_GET['restaurant']) && preg_match('/^[A-Za-z0-9\_-]{1,50}$/', $_GET['restaurant'])) {
                $_SESSION['restaurant'] = $_GET['restaurant'];
            }
            $code = $_SESSION['restaurant'] ?? '';
            $date = $_GET['date'] ?? '';
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = '';
            $recherche = trim($_GET['q'] ?? '');

            $donnees = TriviaService::getMenu($code ?: null, $date ?: null);
            $restaurants = $recherche !== '' ? TriviaService::searchRestaurants($recherche) : null;
            $resultat = "Menu du restaurant universitaire";
            $vue = 'menu';
            break;

        // ========== CONNEXION / DECONNEXION ==========
        case 'connexion':
            $identifiants = ['username' => trim($_POST['username'] ?? ''), 'password' => $_POST['password'] ?? ''];
            $reponse = TriviaService::getMe($identifiants);
            if (is_array($reponse) && isset($reponse['role'])) {
                session_regenerate_id(true);
                $_SESSION['auth'] = $identifiants + ['role' => $reponse['role'], 'club_id' => $reponse['club_id'], 'club_name' => $reponse['club_name']];
                $auth = $_SESSION['auth'];
                $donnees = ['message' => 'Connexion réussie'];
                $resultat = "Connecté";
            } else {
                $donnees = ['detail' => 'Identifiant ou mot de passe incorrect'];
            }
            $vue = 'clubs';
            break;

        case 'deconnexion':
            unset($_SESSION['auth']);
            session_regenerate_id(true);
            $auth = null;
            $donnees = ['message' => 'Vous êtes déconnecté'];
            $resultat = "Déconnecté";
            $vue = 'accueil';
            break;

        case 'refuse':
            $donnees = ['detail' => 'Connectez-vous pour effectuer cette action (formulaire « Connexion » dans la page Clubs)'];
            $vue = 'clubs';
            break;

        // ========== REQUÊTES POST ==========
        case 'ajouter_club': // administrateur
            $nom = trim($_POST['name'] ?? '');
            $donnees = TriviaService::addClub(
                $nom, trim($_POST['username'] ?? ''), $_POST['password'] ?? '',
                nullable($_POST['description'] ?? ''), nullable($_POST['banner_url'] ?? ''), $_POST['category_id'] ?? null,
                nullable($_POST['email'] ?? ''), nullable($_POST['instagram'] ?? ''), nullable($_POST['website'] ?? ''),
                $auth
            );
            $resultat = "POST - Club créé : $nom";
            $vue = 'clubs';
            break;

        case 'ajouter_evenement': // club connecté
            $titre = trim($_POST['title'] ?? '');
            $donnees = TriviaService::addEvent($titre, $_POST['date'] ?? '', nullable($_POST['location'] ?? ''), nullable($_POST['description'] ?? ''), $auth);
            $resultat = "POST - Événement ajouté : $titre";
            $vue = 'evenements';
            break;

        case 'ajouter_categorie': // administrateur
            $nom = trim($_POST['name'] ?? '');
            $donnees = TriviaService::addCategory($nom, $auth);
            $resultat = "POST - Catégorie ajoutée : $nom";
            $vue = 'categories';
            break;

        case 'participer': // le club connecté devient co-organisateur
            $event_id = (int)($_POST['event_id'] ?? 0);
            $donnees = TriviaService::assignClubToEvent($auth['club_id'] ?? 0, $event_id, $auth);
            $resultat = "POST - Votre club co-organise l'événement $event_id";
            $vue = 'evenements';
            break;

        // ========== REQUÊTES PUT ==========
        case 'modifier_club': // club connecté
            $nom = nullable($_POST['name'] ?? '');
            $donnees = TriviaService::updateClub(
                $auth['club_id'] ?? 0, $nom,
                nullable($_POST['description'] ?? ''), nullable($_POST['banner_url'] ?? ''), $_POST['category_id'] ?? null,
                nullable($_POST['email'] ?? ''), nullable($_POST['instagram'] ?? ''), nullable($_POST['website'] ?? ''),
                $auth
            );
            $resultat = "PUT - Club modifié";
            $vue = 'clubs';
            break;

        case 'changer_mdp': // club connecté
            $nouveau = $_POST['new_password'] ?? '';
            if ($nouveau !== ($_POST['confirm_password'] ?? '')) {
                $donnees = ['detail' => 'Les deux mots de passe ne correspondent pas'];
            } else {
                $donnees = TriviaService::changePassword($auth['club_id'] ?? 0, $nouveau, $auth);
                if (is_array($donnees) && isset($donnees['message'])) {
                    $_SESSION['auth']['password'] = $nouveau; // on garde la session valide
                }
            }
            $resultat = "PUT - Mot de passe modifié";
            $vue = 'clubs';
            break;

        // ========== REQUÊTES DELETE ==========
        case 'supprimer_club': // administrateur
            $id = (int)($_POST['id'] ?? 0);
            $donnees = TriviaService::deleteClub($id, $auth);
            $resultat = "DELETE - Club ID $id supprimé";
            $vue = 'clubs';
            break;

        case 'supprimer_evenement':
            $id = (int)($_POST['id'] ?? 0);
            $donnees = TriviaService::deleteEvent($id, $auth);
            $resultat = "DELETE - Événement ID $id supprimé";
            $vue = 'evenements';
            break;

        case 'retirer_participation': // le club connecté ne co-organise plus
            $event_id = (int)($_POST['event_id'] ?? 0);
            $donnees = TriviaService::removeClubFromEvent($auth['club_id'] ?? 0, $event_id, $auth);
            $resultat = "DELETE - Votre club ne co-organise plus l'événement $event_id";
            $vue = 'evenements';
            break;
    }

    // ========== MESSAGE DE RETOUR D'ACTION (affiché dans les vues) ==========
    $message = '';
    $message_type = 'info';
    if ($est_post || $action === 'refuse') {
        if ($donnees === null) {
            $message = "L'API est injoignable pour le moment";
            $message_type = 'error';
        } elseif (isset($donnees['detail'])) {
            $message = detail_en_texte($donnees['detail']);
            $message_type = 'error';
        } elseif (isset($donnees['id']) || isset($donnees['message'])) {
            $message = $resultat ?: $donnees['message'];
            $message_type = 'success';
        }
    }

    if ($vue == 'clubs') {
        require_once 'views/clubs.php';
    } elseif ($vue == 'evenements') {
        require_once 'views/evenements.php';
    } elseif ($vue == 'categories') {
        require_once 'views/categories.php';
    } elseif ($vue == 'menu') {
        require_once 'views/menu.php';
    } else {
        require_once 'views/accueil.php';
    }
?>