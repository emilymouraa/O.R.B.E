<?php

namespace App\Core;

class Controller {
    protected function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function view(string $view, array $data = []): void {
        extract($data);
        $viewPath = __DIR__ . "/../Views/{$view}.php";
        if (!file_exists($viewPath)) {
            http_response_code(404);
            echo "View não encontrada.";
            exit;
        }
        require $viewPath;
    }

    protected function redirect(string $url): void {
        header("Location: {$url}");
        exit;
    }
}