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
// parameter processing
$parameters = $_GET;
if (isset($parameters['page'])) {
    if ($parameters['page'] == 'register') {
        $template = 'register';
        $configuration['{REGISTER_USERNAME}'] = '';
        $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Ja tinc un compte';
        $configuration['{METHOD}'] = 'POST';
    } else if ($parameters['page'] == 'login') {
        $template = 'login';
        $configuration['{LOGIN_USERNAME}'] = '';
        $configuration['{METHOD}'] = 'POST';
    }
} else if (isset($parameters['register'])) {
    $db = new PDO($db_connection);

    //todo: agregar verificacion
    $sql = 'INSERT INTO users (user_name, user_password) VALUES (:user_name, :user_password)';
    $query = $db->prepare($sql);
    $query->bindValue(':user_name', $_POST["user_name"]);
    $query->bindValue(':user_password', $_POST["user_password"]);

    //TODO: no se si query execute retorne algo
    if ($query->execute()) {

        echo "Hola mundo";


        //Asumiendo que se ejecuta, ya se creo el usuario en la base de datos.
        $configuration['{FEEDBACK}'] = 'Creat el compte <b>' . htmlentities($_POST['user_name']) . '</b>';
        $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar sessió';
    } else {
        // Esto no se ejecuta
        $configuration['{FEEDBACK}'] = "<mark>ERROR: No s'ha pogut crear el compte <b>"
            . htmlentities($parameters['user_name']) . '</b></mark>';
    }
} else if (isset($parameters['login'])) {
    $db = new PDO($db_connection);
    $sql = 'SELECT * FROM users WHERE user_name = :user_name and user_password = :user_password';
    $query = $db->prepare($sql);
    $query->bindValue(':user_name', $parameters['user_name']);
    $query->bindValue(':user_password', $parameters['user_password']);
    $query->execute();
    $result_row = $query->fetchObject();
    if ($result_row) {
        $_SESSION['username'] = $parameters['user_name'];
        $_SESSION['logged_in'] = true;

        $configuration['{FEEDBACK}'] = '"Sessió" iniciada com <b>' . htmlentities($parameters['user_name']) . '</b>';
        $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar "sessió"';
        $configuration['{LOGIN_LOGOUT_URL}'] = '/?page=logout';
    } else {
        $configuration['{FEEDBACK}'] = '<mark>ERROR: Usuari desconegut o contrasenya incorrecta</mark>';
    }
}
// process template and show output
$html = file_get_contents('plantilla_' . $template . '.html', true);
$html = str_replace(array_keys($configuration), array_values($configuration), $html);
echo $html;
