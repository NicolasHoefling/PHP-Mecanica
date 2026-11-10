<?php

declare(strict_types=1);

use Mecanica\Fisica;
use Mecanica\Operacoes\{Conversao, Energia, Forcas, Potencia, Situacao, Trabalho};

spl_autoload_register(static function (string $classe): void {
    $prefixo = 'Mecanica\\';
    if (str_starts_with($classe, $prefixo)) {
        $arquivo = __DIR__ . '/src/' . str_replace('\\', '/', substr($classe, strlen($prefixo))) . '.php';
        if (is_file($arquivo)) {
            require $arquivo;
        }
    }
});

$fisica = new Fisica();
$operacoes = [
    new Forcas($fisica),
    new Trabalho($fisica),
    new Trabalho($fisica, true),
    new Energia($fisica, 'cinetica'),
    new Energia($fisica, 'potencial'),
    new Energia($fisica, 'mecanica'),
    new Potencia(),
    new Situacao($fisica),
    new Conversao(),
];
$menu = [];
foreach ($operacoes as $operacao) {
    $menu[$operacao->id()] = $operacao;
}
return $menu;
