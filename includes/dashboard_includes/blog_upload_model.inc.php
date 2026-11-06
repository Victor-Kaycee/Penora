<?php

declare(strict_types=1);

function set_post(object $pdo, int $userId, string $category, string $title, string $report, array $postImg)
{
    if ($postImg['error'] !== UPLOAD_ERR_OK) {
        $errors["upload_error"] = '
             <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            Swal.fire({
                icon: "error",
                title: "Upload Error",
                text: "File upload error!"
            });
            </script>';
    }

    $fileName = $postImg['name'];
    $tmpName = $postImg['tmp_name'];

    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
    $allowed = ["jpg", "jpeg", "png", "gif"];

    if (!in_array(strtolower($fileExt), $allowed)) {
        $errors["file_type"] = '
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            Swal.fire({
                icon: "error",
                title: "Invalid File Type!",
                text: "Only JPG, JPEG, PNG, GIF allowed!"
            });
            </script>';
    }


    if (!empty($tmpName)) {

        $imageInfo = getimagesize($tmpName);

        if ($imageInfo === false) {
            $errors["invalid_image"] =  '
                 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            Swal.fire({
                icon: "error",
                title: "Invalid Image!",
                text: "File is not an image!"
            });
            </script>';
        } else {
            $width = $imageInfo[0];
            $height = $imageInfo[1];

            if ($width !== 2880 || $height !== 1440) {
                $errors["dimension"] =  '
                     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            Swal.fire({
                icon: "error",
                title: "Wrong Dimension",
                text: "Image must be exactly 2880 x 1440 pixels!"
            });
            </script>';
            }
        }
    } else {
        // $errors["upload"] = "File upload failed (empty path).";
    }


    $newFileName = time() . "_" . bin2hex(random_bytes(5)) . "." . $fileExt;

    if (!is_dir("uploads")) {
        mkdir("uploads", 0777, true);
    }

    $destination = "uploads/" . $newFileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        $errors["upload_error"] = '
         <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            Swal.fire({
                icon: "error",
                title: "Upload Error",
                text: "Image upload failed!"
            });
            </script>';
    }



    $query = "INSERT INTO posts (users_id, category, title, report, post_img) VALUES (:user_id, :category, :title, :report, :post_img);";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":user_id", $userId);
    $stmt->bindParam(":category", $category);
    $stmt->bindParam(":title", $title);
    $stmt->bindParam(":report", $report);
    $stmt->bindParam(":post_img", $destination);
    $stmt->execute();
}
