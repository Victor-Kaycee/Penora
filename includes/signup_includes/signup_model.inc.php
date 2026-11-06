<?php

declare(strict_types=1);

function get_email(object $pdo, string $email)
{
    $query = "SELECT email FROM users WHERE email = :email;";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}


function set_user(object $pdo, string $firstname, string $middlename, string $lastname, string $email, string $pwd, array $imageFile)
{
    $options = [
        "cost" => 12
    ];

    $hashedPwd = password_hash($pwd, PASSWORD_BCRYPT, $options);

    if ($imageFile['error'] !== UPLOAD_ERR_OK) {
        die("File upload error!");
    }

    $fileName = $imageFile['name'];
    $tmpName = $imageFile['tmp_name'];

    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
    $allowed = ["jpg", "jpeg", "png", "gif"];

    if (!in_array(strtolower($fileExt), $allowed)) {
        die("Invalid file type!");
    }

    $newFileName = time() . "_" . bin2hex(random_bytes(5)) . "." . $fileExt;

    if (!is_dir("uploads")) {
        mkdir("uploads", 0777, true);
    }

    $destination = "uploads/" . $newFileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        die("Image upload failed!");
    }


    $query = "INSERT INTO users (firstname, middlename, lastname, email, pwd, file_name) VALUES (:firstname, :middlename, :lastname, :email, :pwd, :file_name);";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":firstname", $firstname);
    $stmt->bindParam(":middlename", $middlename);
    $stmt->bindParam(":lastname", $lastname);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":pwd", $hashedPwd);
    $stmt->bindParam(":file_name", $destination);
    $stmt->execute();
}
