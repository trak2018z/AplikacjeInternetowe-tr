<?php
session_start();

if (isset($_POST['email_reg'])) {
    $successful_validation = true;

    //email
    $email = $_POST['email_reg'];
    $email_val = filter_var($email, FILTER_SANITIZE_EMAIL);

    if ((filter_var($email_val, FILTER_VALIDATE_EMAIL) == false)) {
        $successful_validation = false;
        $_SESSION['error_email'] = "Niepoprawny adres email.";
    }

    //hasło
    $password = $_POST['password1_registration'];
    $password2 = $_POST['password2_registration'];

    if ((strlen($password) < 8) || (strlen($password) > 20)) {
        $successful_validation = false;
        $_SESSION['error_password'] = "Hasło musi zawierać od 8 do 20 znaków";
    }

    if (($password != $password2)) {
        $successful_validation = false;
        $_SESSION['error_password'] = "Wprowadzone hasła nie są takie same.";
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    //Imie
    $name = $_POST['name_registration'];
    if ((strlen($name) < 3) || (strlen($name) > 20) || !preg_match('/^[A-ZŁŚ]{1}[a-ząęśżźćń]+$/', $name)) {
        $successful_validation = false;
        $_SESSION['error_name'] = "Wprowadzone imie nie jest prawidłowe.";
    }

    //Nazwisko
    $surname = $_POST['surname_registration'];
    if ((strlen($surname) < 3) || (strlen($surname) > 20) || !preg_match('/^[A-ZŁŚ]{1}[a-ząęśżźćń]+$/', $name)) {
        $successful_validation = false;
        $_SESSION['error_surname'] = "Wprowadzone nazwisko nie jest prawidłowe.";
    }

    //typ
    $type = $_POST['type_registration'];

    //indeks
    $indexNM = $_POST['indexNM_registration'];
    if ($type == "Student" && (strlen($indexNM) != 6 || !preg_match('/^[0-9]+$/', $indexNM))) {
        $successful_validation = false;
        $_SESSION['error_indexNM'] = "Podany index jest nieprawidłowy.";
    }

    $study = $_POST['study_db'];
    if ($type == "Student" && $study == "") {
        $successful_validation = false;
        $_SESSION['error_study'] = "Wybierz swój kierunek studiów.";
    }

    $department = $_POST['department_db'];
    if ($type == "Nauczyciel" && $department == "") {
        $successful_validation = false;
        $_SESSION['error_department'] = "Wybierz swój wydział.";
    }


    //checkbox
    if (!isset($_POST['reulations'])) {
        $successful_validation = false;
        $_SESSION['error_reulations'] = "Potwierdź akceptację regulaminu.";
    }

    //CAPTCHA
    $captcha = $_POST['g-recaptcha-response'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $secretKey = "6Ld3YjoUAAAAAB_YAqJM2OP794f18mYiiE3F7szh";

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://www.google.com/recaptcha/api/siteverify',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'secret' => $secretKey,
            'response' => $captcha,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ],
        CURLOPT_RETURNTRANSFER => true
    ]);

    $output = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($output);

    if (!$json->success) {
        $successful_validation = false;
        $_SESSION['error_captcha'] = "Potwierdź, że nie jesteś botem.";
    }

    //
    require_once 'connect.php';
    $connect = @new mysqli($host, $db_user, $db_password, $db_name);

    if ($connect->connect_errno != 0) {
        echo "Błąd połączeniea z bazą. Spróbuj ponownie później";
    } else {
        $result = $connect->query("SELECT id FROM user WHERE email='$email'");
        $email_check = $result->num_rows;
        if ($email_check > 0) {
            $successful_validation = false;
            $_SESSION['error_email'] = "Konto już istnieje.";
        }
        if ($successful_validation == true) {
            if ($type === "Student") {
                $actCode = str_shuffle("qwertyuiopasdfghjklzxcvbnm1234567890");
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=iso-8859-2\r\n";
                $content = "Witaj " . $name . ". Aby aktywowac swoje konto kliknij ponizszy link aktywujacy:<br>
                                           <a href=\"http://localhost/test/index.php?active=" . $actCode . "\"> http://localhost/test/index.php?active=" . $actCode . " </a><br>
                                                ";
                mail($email, "Link Aktywacyjny", $content, $headers);
                if ($connect->query("INSERT INTO `user`(`id`, `email`, `password`, `name`, `surname`, `type`, `indexNM`, `id_study`, `id_department`,`activation_key`, `active`) VALUES(NULL,'$email','$passwordHash','$name','$surname','$type',NULL,'$study',NULL,'$actCode',0)"))
                    echo "Zostałeś zarejestrowany. Na twój e-mail została wysłana wiadomość z kodem aktywayjnym.";
                else
                    echo "REJESTRACJA NIE POWIODŁĄ SIĘ." . $connect->error;
            } else {
                $actCode = str_shuffle("qwertyuiopasdfghjklzxcvbnm1234567890");
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=iso-8859-2\r\n";
                $content = "Witaj " . $name . ". Aby aktywowac swoje konto kliknij ponizszy link aktywujacy:<br>
                                           <a href=\"http://localhost/test/index.php?active=" . $actCode . "\"> http://localhost/test/index.php?active=" . $actCode . " </a><br>
                                                ";
                mail($email, "Link Aktywacyjny", $content, $headers);
                if ($connect->query("INSERT INTO `user`(`id`, `email`, `password`, `name`, `surname`, `type`, `indexNM`, `id_study`, `id_department`,`activation_key`, `active`) VALUES(NULL,'$email','$passwordHash','$name','$surname','$type',NULL,NULL,$department,'$actCode',0)"))
                    echo "Zostałeś zarejestrowany. Na twój e-mail została wysłana wiadomość z kodem aktywayjnym.";
                else
                    echo "REJESTRACJA NIE POWIODŁĄ SIĘ." . $connect->error;
            }
        }
        $connect->close();
    }
}
?>

<!DOCTYPE HTML>
<html lang="pl">
    <head>
        <meta charset="utf-8"/> 
        <meta http-equiv="X-UA-Compatible" content="IE = edge, chrome = 1"/>
        <title>Rejestracja</title>
        <script src='https://www.google.com/recaptcha/api.js'></script>
        <style>
            .error
            {
                color:red;
                margin-top: 10px;           
                margin-bottom: 10px;                    
            }
        </style>
    </head>
    <body>
        <form method="post">
            <br/>

            E-mail: <br/> <input type="text" name="email_reg" /> <br/>

            <?php
            if (isset($_SESSION['error_email'])) {
                echo '<div class="error">' . $_SESSION['error_email'] . '</div>';
                unset($_SESSION['error_email']);
            }
            ?>

            Hasło: </br> <input type="password" name="password1_registration"/><br/>

            <?php
            if (isset($_SESSION['error_password'])) {
                echo '<div class="error">' . $_SESSION['error_password'] . '</div>';
                unset($_SESSION['error_password']);
            }
            ?>

            Powtórz hasło: <br/> <input type="password" name="password2_registration"/><br/>

            Imię: <br/> <input type="text" name="name_registration" /> <br/>
            <?php
            if (isset($_SESSION['error_name'])) {
                echo '<div class="error">' . $_SESSION['error_name'] . '</div>';
                unset($_SESSION['error_name']);
            }
            ?>

            Nazwisko: <br/> <input type="text" name="surname_registration" /> <br/>
            <?php
            if (isset($_SESSION['error_surname'])) {
                echo '<div class="error">' . $_SESSION['error_surname'] . '</div>';
                unset($_SESSION['error_surname']);
            }
            ?>

            Typ: <br/> 

            <select name="type_registration">
                <option>Student</option>
                <option>Nauczyciel</option>
            </select>
            <br/>
            Numer indeksu:<br/> <input type="text" name="indexNM_registration" /> <br/>
            <?php
            if (isset($_SESSION['error_indexNM'])) {
                echo '<div class="error">' . $_SESSION['error_indexNM'] . '</div>';
                unset($_SESSION['error_indexNM']);
            }
            ?>

            <br/>Kierunek studiów:
            <br/>
            <?php
            require_once 'connect.php';
            $connect = @new mysqli($host, $db_user, $db_password, $db_name);

            if ($connect->connect_errno != 0) {
                echo "Błąd połączeniea z bazą. Spróbuj ponownie później";
            } else {
                $result = $connect->query("SELECT * FROM study");
                echo '<select name="study_db">';
                echo '<option value=""> Kierunek studiów </option>';
                while ($row = $result->fetch_assoc()) {
                    echo '<option value="' . $row['id_study'] . '">' . $row['name'] . '</option>';
                }
                echo '</select>';
                $result->free_result();
                if (isset($_SESSION['error_study'])) {
                    echo '<div class="error">' . $_SESSION['error_study'] . '</div>';
                    unset($_SESSION['error_study']);
                }
            }
            ?>

            <?php
            if ($connect->connect_errno != 0) {
                echo "Błąd połączeniea z bazą. Spróbuj ponownie później";
            } else {
                $result = $connect->query("SELECT * FROM department");
                echo "<br/><br/>Wydział: <br/>";
                echo '<select name="department_db">';
                echo '<option value=""> Wydział </option>';
                while ($row = $result->fetch_assoc()) {
                    echo '<option value="' . $row['id_department'] . '">' . $row['name'] . '</option>';
                }
                echo '</select><br/>';
                $result->free_result();
                if (isset($_SESSION['error_department'])) {
                    echo '<div class="error">' . $_SESSION['error_department'] . '</div>';
                    unset($_SESSION['error_department']);
                }
            }
            ?>

            </br>
            <label>
                <input type="checkbox" name="reulations"/> Akceptuje regulamin
            </label>

            <?php
            if (isset($_SESSION['error_reulations'])) {
                echo '<div class="error">' . $_SESSION['error_reulations'] . '</div>';
                unset($_SESSION['error_reulations']);
            }
            ?>
            <br/>
            <div class="g-recaptcha" data-sitekey="6Ld3YjoUAAAAAMlA7-JvXqp2ulkBPAucq5oMvvE5"></div>
            <?php
            if (isset($_SESSION['error_captcha'])) {
                echo '<div class="error">' . $_SESSION['error_captcha'] . '</div>';
                unset($_SESSION['error_captcha']);
            }
            ?>
            </br>
            <input type="submit" value="Zarejestruj się"/>


        </form>



    </body>
</html>
