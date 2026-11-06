<?php

declare(strict_types=1);

function is_input_empty(string $category, string $title, string $report, array $postImg)
{
    if (empty($category) || empty($title) || empty($report) || empty($postImg)) {
        return true;
    } else {
        return false;
    }
}

function create_post(object $pdo, int $userId, string $category, string $title, string $report, array $postImg)
{
    set_post($pdo, $userId, $category, $title, $report, $postImg);
}
