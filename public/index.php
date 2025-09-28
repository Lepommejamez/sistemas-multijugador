<?php

session_start();

// defaults
$template = 'home';
$db_connection = 'sqlite:..\private\users.db';
$configuration = array(
    '{FEEDBACK}'          => '',
    '{LOGIN_LOGOUT_TEXT}' => 'Identificar-me',
    '{LOGIN_LOGOUT_URL}'  => '/?page=login',
    '{METHOD}'            => 'GET', // es veuen els paràmetres a l'URL i a la consola (???)
    '{REGISTER_URL}'      => '/?page=register',
    '{SITE_NAME}'         => 'La meva pàgina'
);
// =============================
// 1. Procesar navegación (GET)
// =============================
if (isset($_GET['page'])) {
    if ($_GET['page'] === 'register') {
        $template = 'register';
        $configuration['{REGISTER_USERNAME}'] = '';
        $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Ja tinc un compte';
        $configuration['{METHOD}'] = 'POST'; // formulario de registro usa POST
    } else if ($_GET['page'] === 'login') {
        $template = 'login';
        $configuration['{LOGIN_USERNAME}'] = '';
        $configuration['{METHOD}'] = 'POST'; // formulario de login usa POST
    }
}
    
// =============================
// 2. Procesar formulario de registro (POST)
// ============================= 
    
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register']))
{
    echo "Procesant registre...";
    $db = new PDO($db_connection);
    $username = $_POST["user_name"];
    $password = $_POST["user_password"];

    $sql = 'SELECT * FROM users WHERE user_name = :username';
    $query = $db->prepare($sql);
    $query->bindValue(':username', $username);
    $query->execute();
    $result_row = $query->fetchObject();
    if ($result_row) 
    {
        $configuration['{FEEDBACK}'] = "<mark>ERROR: L'usuari <b>"
            . htmlentities($username) . '</b> ja existeix</mark>';
    } 
    else 
    {
        $sql = 'INSERT INTO users (user_name, user_password) VALUES (:username, :user_password)';
        $query = $db->prepare($sql);
        $query->bindValue(':username', $username);
        $query->bindValue(':user_password', $password);

        //TODO: no se si query execute retorne algo
        if ($query->execute()) 
        {
            echo "Hola mundo";
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login']))
{
    echo "aaaa...";
    $username = $_POST["user_name"];
    $password = $_POST["user_password"];


    $db = new PDO($db_connection);
    $sql = 'SELECT * FROM users WHERE user_name = :user_name and user_password = :user_password';
    $query = $db->prepare($sql);
    $query->bindValue(':user_name', $username);
    $query->bindValue(':user_password', $password);
    $query->execute();
    $result_row = $query->fetchObject();
    if ($result_row) 
    {
        $_SESSION['username'] = $username;
        $_SESSION['logged_in'] = true;
        $configuration['{FEEDBACK}'] = '"Sessió" iniciada com <b>' . htmlentities($username) . '</b>';
        $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar "sessió"';
        $configuration['{LOGIN_LOGOUT_URL}'] = '/?page=logout';
    } 
    else 
    {
        $configuration['{FEEDBACK}'] = '<mark>ERROR: Usuari desconegut o contrasenya incorrecta</mark>';
    }
}

// process template and show output
$html = file_get_contents('plantilla_' . $template . '.html', true);
$html = str_replace(array_keys($configuration), array_values($configuration), $html);
echo $html;
