<?php
class TriviaService
{
    // En-têtes communs : JSON + identifiants (HTTP Basic) quand on est connecté
    private static function entetes($auth = null)
    {
        $h = "Content-Type: application/json\r\nAccept: application/json\r\n";
        if ($auth) {
            $h .= 'Authorization: Basic ' . base64_encode($auth['username'] . ':' . $auth['password']) . "\r\n";
        }
        return $h;
    }

    // ========== GET ==========
    public static function getAllClubs($category_id = null)
    {
        $url = API_BASE_URL . '/clubs' . ($category_id ? '?category_id=' . (int)$category_id : '');
        $response = @file_get_contents($url);
        return $response ? json_decode($response, true) : [];
    }

    public static function getClubById($id)
    {
        $url = API_BASE_URL . '/clubs/' . (int)$id;
        $options = ['http' => ['ignore_errors' => true, 'timeout' => 10]];
        $response = @file_get_contents($url, false, stream_context_create($options));
        return $response ? json_decode($response, true) : null;
    }

    public static function getAllEvents($passes = false)
    {
        $url = API_BASE_URL . '/events' . ($passes ? '?include_past=true' : '');
        $response = @file_get_contents($url);
        return $response ? json_decode($response, true) : [];
    }

    public static function getAllCategories()
    {
        $url = API_BASE_URL . '/categories';
        $response = @file_get_contents($url);
        return $response ? json_decode($response, true) : [];
    }

    public static function getMenu($restaurant = null, $date = null)
    {
        $params = array_filter(['restaurant' => $restaurant, 'date' => $date]);
        $url = API_BASE_URL . '/menu' . ($params ? '?' . http_build_query($params) : '');
        $options = ['http' => ['ignore_errors' => true, 'timeout' => 15]];
        $response = @file_get_contents($url, false, stream_context_create($options));
        return $response ? json_decode($response, true) : null;
    }

    public static function searchRestaurants($recherche)
    {
        $url = API_BASE_URL . '/menu/restaurants?' . http_build_query(['search' => $recherche]);
        $options = ['http' => ['ignore_errors' => true, 'timeout' => 15]];
        $response = @file_get_contents($url, false, stream_context_create($options));
        return $response ? json_decode($response, true) : null;
    }

    // Vérifie les identifiants et renvoie le rôle (admin ou club)
    public static function getMe($auth)
    {
        $url = API_BASE_URL . '/auth/me';
        $options = ['http' => ['header' => self::entetes($auth), 'method' => 'GET', 'ignore_errors' => true, 'timeout' => 10]];
        $response = @file_get_contents($url, false, stream_context_create($options));
        return $response ? json_decode($response, true) : null;
    }

    // ========== POST ==========
    public static function addClub($name, $username, $password, $description, $banner_url, $category_id, $email, $instagram, $website, $auth)
    {
        $url = API_BASE_URL . '/clubs';
        $data = json_encode([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'description' => $description,
            'banner_url' => $banner_url,
            'category_id' => $category_id ? (int)$category_id : null,
            'contact' => ['email' => $email, 'instagram' => $instagram, 'website' => $website]
        ]);

        $options = ['http' => ['header' => self::entetes($auth), 'method' => 'POST', 'content' => $data, 'ignore_errors' => true, 'timeout' => 10]];
        $response = @file_get_contents($url, false, stream_context_create($options));
        return $response ? json_decode($response, true) : null;
    }

    public static function addEvent($title, $date, $location, $description, $auth)
    {
        $url = API_BASE_URL . '/events';
        $data = json_encode([
            'title' => $title,
            'date' => $date,
            'location' => $location,
            'description' => $description
        ]);

        $options = ['http' => ['header' => self::entetes($auth), 'method' => 'POST', 'content' => $data, 'ignore_errors' => true, 'timeout' => 10]];
        $response = @file_get_contents($url, false, stream_context_create($options));
        return $response ? json_decode($response, true) : null;
    }

    public static function addCategory($name, $auth)
    {
        $url = API_BASE_URL . '/categories';
        $data = json_encode(['name' => $name]);

        $options = ['http' => ['header' => self::entetes($auth), 'method' => 'POST', 'content' => $data, 'ignore_errors' => true, 'timeout' => 10]];
        $response = @file_get_contents($url, false, stream_context_create($options));
        return $response ? json_decode($response, true) : null;
    }

    public static function assignClubToEvent($club_id, $event_id, $auth)
    {
        $url = API_BASE_URL . '/events/' . (int)$event_id . '/clubs/' . (int)$club_id;

        $options = ['http' => ['header' => self::entetes($auth), 'method' => 'POST', 'content' => '', 'ignore_errors' => true, 'timeout' => 10]];
        $response = @file_get_contents($url, false, stream_context_create($options));
        return $response ? json_decode($response, true) : null;
    }

    public static function removeClubFromEvent($club_id, $event_id, $auth)
    {
        $url = API_BASE_URL . '/events/' . (int)$event_id . '/clubs/' . (int)$club_id;

        $options = ['http' => ['header' => self::entetes($auth), 'method' => 'DELETE', 'ignore_errors' => true, 'timeout' => 10]];
        $response = @file_get_contents($url, false, stream_context_create($options));
        return $response ? json_decode($response, true) : null;
    }

    // ========== PUT ==========
    public static function updateClub($id, $name, $description, $banner_url, $category_id, $email, $instagram, $website, $auth)
    {
        $url = API_BASE_URL . '/clubs/' . (int)$id;
        $data = [
            'description' => $description,
            'banner_url' => $banner_url,
            'category_id' => $category_id ? (int)$category_id : null,
            'contact' => ['email' => $email, 'instagram' => $instagram, 'website' => $website]
        ];
        if ($name !== null) $data['name'] = $name;

        $options = ['http' => ['header' => self::entetes($auth), 'method' => 'PUT', 'content' => json_encode($data), 'ignore_errors' => true, 'timeout' => 10]];
        $response = @file_get_contents($url, false, stream_context_create($options));
        return $response ? json_decode($response, true) : null;
    }

    public static function changePassword($id, $new_password, $auth)
    {
        $url = API_BASE_URL . '/clubs/' . (int)$id . '/password';
        $data = json_encode(['new_password' => $new_password]);

        $options = ['http' => ['header' => self::entetes($auth), 'method' => 'PUT', 'content' => $data, 'ignore_errors' => true, 'timeout' => 10]];
        $response = @file_get_contents($url, false, stream_context_create($options));
        return $response ? json_decode($response, true) : null;
    }

    // ========== DELETE ==========
    public static function deleteClub($id, $auth)
    {
        $url = API_BASE_URL . '/clubs/' . (int)$id;
        $options = ['http' => ['header' => self::entetes($auth), 'method' => 'DELETE', 'ignore_errors' => true, 'timeout' => 10]];
        $response = @file_get_contents($url, false, stream_context_create($options));
        return $response ? json_decode($response, true) : null;
    }

    public static function deleteEvent($id, $auth)
    {
        $url = API_BASE_URL . '/events/' . (int)$id;
        $options = ['http' => ['header' => self::entetes($auth), 'method' => 'DELETE', 'ignore_errors' => true, 'timeout' => 10]];
        $response = @file_get_contents($url, false, stream_context_create($options));
        return $response ? json_decode($response, true) : null;
    }
}
?>
