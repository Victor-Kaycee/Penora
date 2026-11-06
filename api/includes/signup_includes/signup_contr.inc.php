<?php

declare(strict_types=1);

function is_input_empty(string $firstname, string $middlename, string $lastname, string $email, string $pwd, array $imageFile)
{
    if (empty($firstname) || empty($middlename) || empty($lastname) || empty($email) || empty($pwd) || empty($imageFile)) {
        return true;
    } else {
        return false;
    }
}

function is_email_invalid(string $email)
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return true;
    } else {
        return false;
    }
}

function is_email_registered(object $pdo, string $email)
{
    if (get_email($pdo, $email)) {
        return true;
    } else {
        return false;
    }
}

function create_user(object $pdo, string $firstname, string $middlename, string $lastname, string $email, string $pwd, array $imageFile)
{
    set_user($pdo, $firstname, $middlename, $lastname, $email, $pwd, $imageFile);
}
