<?php

/*
 * Classe responsável pelo gerenciamento de rotas da aplicação.
 * Permite registrar rotas associando método HTTP e URI a uma ação
 * (callback), e posteriormente despachar a requisição correta
 * conforme a URL e o método recebidos.
 */

namespace App\Core;

class Router {
    private array $routes = [];

    public function add(string $method, string $route, callable $action): void {
        $this->routes[] = ['method' => $method, 'route'  => $route, 'action' => $action];
    }

    public function dispatch(string $uri, string $method): void {
        foreach ($this->routes as $r) {
            if ($r['method'] !== $method) continue;

            $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $r['route']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                preg_match_all('/\{([^}]+)\}/', $r['route'], $paramNames);
                foreach ($paramNames[1] as $i => $name) {
                    $_GET[$name] = $matches[$i + 1];
                }
                call_user_func($r['action']);
                return;
            }
        }
        
        http_response_code(404);
        echo json_encode(['error' => 'Rota não encontrada']);
}
}