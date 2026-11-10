<?php

declare(strict_types=1);

$menu = require dirname(__DIR__) . '/bootstrap.php';
$verificacoes = 0;

function conferir(bool $ok, string $descricao): void
{
    global $verificacoes;
    if (!$ok) {
        throw new RuntimeException('Falhou: ' . $descricao);
    }
    $verificacoes++;
}

function valor(Mecanica\Resultado $r, string $nome, float $esperado): void
{
    $medidas = array_column($r->medidas(), 'valor', 'nome');
    conferir(isset($medidas[$nome]) && abs($medidas[$nome] - $esperado) < 1e-8, $nome . ' = ' . $esperado);
}

conferir(array_keys($menu) === ['forca', 'trabalho', 'trabalho-resultante', 'cinetica', 'potencial', 'mecanica', 'potencia', 'situacao', 'conversoes'], 'menu do enunciado');

$plano = ['modo' => 'horizontal', 'm' => 20, 'g' => '9,81', 'f' => 100, 'mu' => '0,20', 'fase' => 'repouso'];
$r = $menu['forca']->calcular($plano);
valor($r, 'Peso', 196.2);
valor($r, 'Normal', 196.2);
valor($r, 'Atrito (com sinal)', -39.24);
valor($r, 'Resultante horizontal', 60.76);
valor($r, 'Aceleração horizontal', 3.038);
valor($menu['forca']->calcular(array_replace($plano, ['f' => 30])), 'Resultante horizontal', 0);
valor($menu['forca']->calcular(['modo' => 'newton', 'm' => 4, 'a' => -3]), 'Força resultante', -12);

$r = $menu['forca']->calcular(array_replace($plano, ['modo' => 'inclinada', 'm' => 30, 'f' => 150, 'angulo' => 30]));
valor($r, 'Força aplicada · x', 129.9038105677);
valor($r, 'Força aplicada · y', 75);
valor($r, 'Normal', 219.3);
valor($r, 'Aceleração horizontal', 2.8681270189);
$r = $menu['forca']->calcular(array_replace($plano, ['modo' => 'inclinada', 'f' => 300, 'angulo' => 90]));
valor($r, 'Normal', 0);
valor($r, 'Aceleração vertical', 5.19);

$rampa = ['modo' => 'rampa', 'm' => 10, 'g' => 9.81, 'rampa' => 30, 'mu' => 0, 'fase' => 'repouso'];
$r = $menu['forca']->calcular($rampa);
valor($r, 'Peso paralelo', 49.05);
valor($r, 'Normal', 84.9570921113);
valor($r, 'Aceleração', 4.905);
valor($menu['forca']->calcular(array_replace($rampa, ['mu' => 0.6])), 'Aceleração', 0);
$r = $menu['forca']->calcular(array_replace($rampa, ['mu' => 0.8, 'fase' => 'positivo']));
conferir($r->medidas()[1]['valor'] < 0, 'deslizamento para baixo pode ter aceleração para cima');

$polia = ['modo' => 'polia', 'm1' => 3, 'm2' => 2, 'g' => 9.81, 'mu' => 0, 'fasePolia' => 'repouso'];
$r = $menu['forca']->calcular($polia);
valor($r, 'Aceleração do sistema', 3.924);
valor($r, 'Tensão na corda', 11.772);
valor($r, 'Resultante do bloco na mesa', 11.772);
valor($r, 'Resultante do bloco pendurado', 7.848);
valor($menu['forca']->calcular(array_replace($polia, ['mu' => 0.8])), 'Tensão na corda', 19.62);
$r = $menu['forca']->calcular(['modo' => 'cabos', 'esq' => 45, 'dir' => 45, 'carga' => 100]);
valor($r, 'Tensão no cabo esquerdo', 70.71067811865);
valor($r, 'Tensão no cabo direito', 70.71067811865);
$r = $menu['forca']->calcular(['modo' => 'trelica', 'barraA' => 0, 'barraB' => 90, 'fx' => 50, 'fy' => -100]);
valor($r, 'Barra A · compressão', -50);
valor($r, 'Barra B · tração', 100);

valor($menu['trabalho']->calcular(['f' => 100, 'd' => 10, 'angulo' => 30]), 'Trabalho da força', 866.0254037844);
valor($menu['trabalho']->calcular(['f' => 20, 'd' => 10, 'angulo' => 180]), 'Trabalho da força', -200);
valor($menu['trabalho-resultante']->calcular(['d' => 10, 'forcas' => "100;30\r\n20;180\r\n196,2;90"]), 'Trabalho resultante', 666.0254037844);
valor($menu['cinetica']->calcular(['m' => 20, 'v' => 4]), 'Energia cinética', 160);
valor($menu['potencial']->calcular(['m' => 20, 'g' => 9.81, 'h' => -2]), 'Energia potencial gravitacional', -392.4);
valor($menu['mecanica']->calcular(['m' => 20, 'v' => 4, 'g' => 9.81, 'h' => 2]), 'Energia mecânica', 552.4);
valor($menu['potencia']->calcular(['modo' => 'media', 'w' => 866.0254037844, 't' => 5]), 'Potência média', 173.20508075688);
valor($menu['potencia']->calcular(['modo' => 'instantanea', 'f' => -20, 'v' => 3]), 'Potência instantânea', -60);
valor($menu['conversoes']->calcular(['modo' => 'velocidade', 'de_velocidade' => 'km/h', 'para_velocidade' => 'm/s', 'valor' => 72]), 'Valor convertido', 20);
valor($menu['conversoes']->calcular(['modo' => 'angulo', 'de_angulo' => '°', 'para_angulo' => 'rad', 'valor' => 180]), 'Valor convertido', M_PI);

$base = ['m' => 20, 'f' => 100, 'angulo' => 30, 'd' => 10, 't' => 5, 'v0' => 0, 'g' => 9.81, 'modelo' => 'nenhum'];
$r = $menu['situacao']->calcular($base);
valor($r, 'Componente horizontal', 86.60254037844);
valor($r, 'Trabalho da força aplicada', 866.0254037844);
valor($r, 'Potência média da força aplicada', 173.20508075688);
conferir(count($r->medidas()) === 4, 'não inventar velocidade, altura ou atrito');
$r = $menu['situacao']->calcular(array_replace($base, ['modelo' => 'mruv', 'h' => 2, 'mu' => 0]));
valor($r, 'Velocidade final', 4);
valor($r, 'Energia cinética final', 160);
valor($r, 'Energia mecânica final', 552.4);
valor($r, 'Resultante horizontal pelas forças', 86.60254037844);
conferir(str_contains(implode(' ', $r->passos()), 'não coincidem'), 'explicar conflito entre hipóteses');
$r = $menu['situacao']->calcular(array_replace($base, ['modelo' => 'mruv', 'v' => 7]));
valor($r, 'Velocidade final', 7);
conferir(!in_array('Aceleração pela hipótese de MRUV', array_column($r->medidas(), 'nome'), true), 'não usar MRUV incompatível com a velocidade medida');

$invalidos = [
    ['forca', array_replace($plano, ['m' => 0])],
    ['forca', ['modo' => 'cabos', 'esq' => 0, 'dir' => 0, 'carga' => 100]],
    ['trabalho', ['f' => 100, 'd' => 10, 'angulo' => 181]],
    ['trabalho-resultante', ['d' => 10, 'forcas' => "100;30\nlinha inválida"]],
    ['potencia', ['modo' => 'media', 'w' => 100, 't' => 0]],
    ['potencia', ['modo' => ['media']]],
    ['cinetica', ['m' => 1e300, 'v' => 1e100]],
    ['conversoes', ['modo' => 'velocidade', 'valor' => 72, 'de_velocidade' => 'kg', 'para_velocidade' => 'm/s']],
];
foreach ($invalidos as [$id, $dados]) {
    try {
        $menu[$id]->calcular($dados);
        conferir(false, 'rejeitar dados inválidos em ' . $id);
    } catch (InvalidArgumentException $e) {
        conferir(true, 'entrada inválida tratada');
    }
}
echo "OK: {$verificacoes} verificações concluídas.\n";
