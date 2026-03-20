<?php

/*
 * Classe responsável por carregar e disponibilizar variáveis de ambiente
 * definidas no arquivo .env da aplicação. Essas variáveis normalmente
 * contêm configurações sensíveis ou específicas do ambiente, como
 * credenciais de banco de dados, modo de debug, portas, etc.
 */

namespace App\Core;

class Env {

    public static function load(string $path): void{

        // Verifica se o arquivo .env existe antes de tentar carregá-lo
        if (!file_exists($path)) {
            throw new \Exception(".env file not found");
        }

        // Lê todas as linhas do arquivo ignorando linhas vazias
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {

            // Ignora linhas de comentário (iniciadas com #)
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Separa chave e valor da variável de ambiente
            [$key, $value] = explode('=', $line, 2);

            $key = trim($key);
            $value = trim($value);

            // Armazena no array global de variáveis de ambiente
            $_ENV[$key] = $value;
        }
    }

    public static function get(string $key, $default = null){

        // Retorna o valor da variável de ambiente ou um valor padrão caso não exista
        return $_ENV[$key] ?? $default;
    }
}