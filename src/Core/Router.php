<?php

namespace App\Core;
class Router {
    private array $routes = [];
    public function add(string $method, string $route, callable $action): void {
        $this->routes[] = ['method' => $method, 'route'  => $route, 'action' => $action];
    }

    public function dispatch(string $uri, string $method): void {
        foreach ($this->routes as $r) {
            if ($r['route'] === $uri && $r['method'] === $method) {
                call_user_func($r['action']);
                return;
            }
        }
        http_response_code(404);
        echo json_encode(['error' => 'Rota não encontrada']);
    }
}