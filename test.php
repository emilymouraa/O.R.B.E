<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
App\Core\Env::load(__DIR__ . '/.env');
$conn = App\Core\Database::getConnection();
$m = new App\Models\ServidorModel($conn);
var_dump($m->getNextRa());

$id = $m->create([
    'ra'              => 'PRF99998',
    'nome'            => 'Teste Debug',
    'cpf'             => '00000000000',
    'data_nascimento' => '1990-01-01',
    'data_ingresso'   => date('Y-m-d'),
    'cargo'           => '3a_classe',
    'unidade_id'      => 1,
    'situacao'        => 'ativo',
]);
var_dump($id);