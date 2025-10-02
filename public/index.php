<?php
session_start();


//session_unset();   // borra todas las variables de sesión
//session_destroy(); // destruye la sesión en el servidor
// configuración
define('MIN_PASSWORD_LENGTH', 12); // cambia a lo que necesites

$template = 'home';
$db_connection = 'sqlite:..\private\users.db';
$configuration = array(
    '{FEEDBACK}'          => '',
    '{LOGIN_LOGOUT_TEXT}' => 'Identificar-me',
    '{LOGIN_LOGOUT_URL}'  => '/?page=login',
    '{METHOD}'            => 'GET',
    '{REGISTER_URL}'      => '/?page=register',
    '{SITE_NAME}'         => 'La meva pàgina'
);

// =============================
// 1. Procesar navegación (GET)
// =============================


// FORZAR REDIRECCIÓN A HOME SI HAY SESIÓN INICIADA
if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Si ya estás en logout, déjalo pasar para poder cerrar sesión
    $currentPage = $_GET['page'] ?? 'home';
    if ($currentPage !== 'home' && $currentPage !== 'logout') {
        header('Location: /?page=home');
        exit;
    }
}

if (isset($_GET['page'])) {
    if ($_GET['page'] === 'register') {
        $template = 'register';
        $configuration['{REGISTER_USERNAME}'] = '';
        $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Ja tinc un compte';
        $configuration['{METHOD}'] = 'POST';
    } else if ($_GET['page'] === 'login') {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            $configuration['{FEEDBACK}'] = '<mark>Ja tens una sessió iniciada com <b>' . htmlentities($_SESSION['username']) . '</b></mark>';
            $template = 'home';
        } else {
            $template = 'login';
            $configuration['{LOGIN_USERNAME}'] = '';
            $configuration['{METHOD}'] = 'POST';
        }
    } elseif ($_GET['page'] === 'logout') {
        // Logout seguro
        session_unset();
        session_destroy();
        header('Location: /?page=home');
        exit;
    }
}

// =============================
// 2. Procesar formulario de registro (POST)
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) 
{
    $db = new PDO($db_connection);
    $username = $_POST["user_name"];
    $password = $_POST["user_password"];

    if ($username === '' || $password === '') 
    {
        $configuration['{FEEDBACK}'] = '<mark>ERROR: Omple tots els camps</mark>';
    } elseif (strlen($password) < MIN_PASSWORD_LENGTH) 
    {
        $configuration['{FEEDBACK}'] = '<mark>ERROR: La contrasenya ha de tenir almenys ' . MIN_PASSWORD_LENGTH . ' caràcters</mark>';
    } 
    else 
    {
        
        // Comprobar si existe el usuario
        $sql = 'SELECT * FROM users WHERE user_name = :username';
        $query = $db->prepare($sql);
        $query->bindValue(':username', $username);
        $query->execute();
        $result_row = $query->fetchObject();

        if ($result_row) 
        {
            $configuration['{FEEDBACK}'] = "<mark>ERROR: L'usuari <b>" . htmlentities($username) . '</b> ja existeix</mark>';
        } 
        else 
        {
            
            // Hash de la contraseña
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = 'INSERT INTO users (user_name, user_password) VALUES (:username, :user_password)';
            $query = $db->prepare($sql);
            $query->bindValue(':username', $username);
            $query->bindValue(':user_password', $hash);
            if ($query->execute()) 
            {
                //Asumiendo que se ejecuta, ya se creo el usuario en la base de datos.
                $configuration['{FEEDBACK}'] = 'Creat el compte <b>' . htmlentities($_POST['user_name']) . '</b>';
                $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar sessió';
            }
            else 
            {
                $configuration['{FEEDBACK}'] = '<mark>ERROR: No s\'ha pogut crear l\'usuari</mark>';
            } 
        }
    }
}

// =============================
// 3. Procesar formulario de login (POST)
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $db = new PDO($db_connection);
    $username = trim($_POST["user_name"] ?? '');
    $password = trim($_POST["user_password"] ?? '');

    if ($username === '' || $password === '') {
        $configuration['{FEEDBACK}'] = '<mark>ERROR: Omple tots els camps</mark>';
    } else {
        // Seleccionamos por nombre de usuario y recuperamos el hash
        $sql = 'SELECT user_id, user_password FROM users WHERE user_name = :user_name LIMIT 1';
        $query = $db->prepare($sql);
        $query->bindValue(':user_name', $username);
        $query->execute();
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if ($row && isset($row['user_password']) && password_verify($password, $row['user_password'])) {
            // Login correcto
            session_regenerate_id(true);
            $_SESSION['username'] = $username;
            $_SESSION['logged_in'] = true;
            $configuration['{LOGIN_USERNAME}'] = htmlentities($username);

            // Opcional: rehash si es necesario
            if (password_needs_rehash($row['user_password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $db->prepare('UPDATE users SET user_password = :p WHERE id = :id');
                $upd->bindValue(':p', $newHash);
                $upd->bindValue(':id', $row['id']);
                $upd->execute();
            }

            header('Location: /?page=home');
            exit;
        } else {
            $configuration['{FEEDBACK}'] = '<mark>ERROR: Usuari desconegut o contrasenya incorrecta</mark>';
        }
    }
}

// =============================
// Estado de sesión: ajustar login/logout
// =============================
if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Si ya está logueado → mostrar logout
    $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar sessió';
    $configuration['{LOGIN_LOGOUT_URL}']  = '/?page=logout';
    $configuration['{LOGIN_USERNAME}'] = htmlentities($_SESSION['username']);
} else {
    // Si NO está logueado → mostrar login
    $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Identificar-me';
    $configuration['{LOGIN_LOGOUT_URL}']  = '/?page=login';
    $configuration['{LOGIN_USERNAME}'] = '';
}

// process template and show output
$html = file_get_contents('plantilla_' . $template . '.html', true);
$html = str_replace(array_keys($configuration), array_values($configuration), $html);
echo $html;
