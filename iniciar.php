<?php

declare(strict_types=1);

// Este arquivo inicia o servidor local. As classes da calculadora ficam em src/.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (PHP_VERSION_ID < 80000) {
    fwrite(STDERR, "Esta aplicacao requer PHP 8.0 ou superior.\n");
    exit(1);
}

$pastaPublica = __DIR__ . DIRECTORY_SEPARATOR . 'public';
if (!is_file($pastaPublica . DIRECTORY_SEPARATOR . 'index.php')) {
    fwrite(STDERR, "Extraia o ZIP inteiro antes de iniciar. A pasta public nao foi encontrada.\n");
    exit(1);
}

$argumentos = array_slice($argv, 1);
if (in_array('--verificar', $argumentos, true)) {
    echo 'PHP ' . PHP_VERSION . "; arquivos encontrados. Iniciador pronto.\n";
    exit(0);
}
$teste = in_array('--teste', $argumentos, true);
$abrirNavegador = !$teste && !in_array('--sem-navegador', $argumentos, true);

// Encontra uma porta livre sem encerrar nenhum programa que ja esteja aberto.
$porta = null;
for ($candidata = 8021; $candidata <= 8040; $candidata++) {
    // No Windows, testar somente o bind pode permitir reutilizar uma porta.
    $conexao = @fsockopen('127.0.0.1', $candidata, $codigo, $mensagem, 0.1);
    if (is_resource($conexao)) {
        fclose($conexao);
        continue;
    }
    $reserva = @stream_socket_server('tcp://127.0.0.1:' . $candidata, $codigo, $mensagem);
    if (is_resource($reserva)) {
        fclose($reserva);
        $porta = $candidata;
        break;
    }
}
if ($porta === null) {
    fwrite(STDERR, "Nao foi encontrada uma porta livre entre 8021 e 8040.\n");
    exit(1);
}

$url = 'http://127.0.0.1:' . $porta . '/';
$servidor = proc_open(
    // A pasta relativa evita problemas de codificacao nos argumentos do Windows.
    [PHP_BINARY, '-n', '-S', '127.0.0.1:' . $porta, '-t', 'public'],
    [0 => STDIN, 1 => STDOUT, 2 => STDERR],
    $pipes,
    __DIR__,
    null,
    ['bypass_shell' => true]
);
if (!is_resource($servidor)) {
    fwrite(STDERR, "Nao foi possivel iniciar o servidor PHP.\n");
    exit(1);
}

register_shutdown_function(function () use ($servidor): void {
    if (is_resource($servidor) && proc_get_status($servidor)['running']) {
        proc_terminate($servidor);
    }
});

// Espera a pagina responder antes de abrir o navegador.
$contexto = stream_context_create(['http' => ['timeout' => 0.5]]);
$pronto = false;
for ($tentativa = 0; $tentativa < 40; $tentativa++) {
    if (!proc_get_status($servidor)['running']) {
        break;
    }
    $pagina = @file_get_contents($url, false, $contexto);
    if (is_string($pagina) && strpos($pagina, 'data-app="mecanica"') !== false
        && proc_get_status($servidor)['running']) {
        $pronto = true;
        break;
    }
    usleep(100000);
}
if (!$pronto) {
    proc_terminate($servidor);
    proc_close($servidor);
    fwrite(STDERR, "O servidor nao respondeu. Confira as mensagens acima.\n");
    exit(1);
}

echo "\nCALCULADORA DE MECANICA - TEMA II\n";
echo 'Endereco: ' . $url . "\n";
echo "Mantenha esta janela aberta durante o uso.\n";
echo "Para encerrar, pressione Ctrl+C ou feche esta janela.\n\n";

if ($teste) {
    $contextoPost = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query(['operacao' => 'trabalho', 'f' => 100, 'd' => 10, 'angulo' => 30]),
        'timeout' => 3,
    ]]);
    $pagina = @file_get_contents($url, false, $contextoPost);
    $ok = is_string($pagina) && strpos($pagina, '866,025') !== false;
    proc_terminate($servidor);
    proc_close($servidor);
    echo $ok ? "OK: servidor local e formulario POST verificados.\n" : "Falha no teste do formulario POST.\n";
    exit($ok ? 0 : 1);
}

if ($abrirNavegador && PHP_OS_FAMILY === 'Windows') {
    // A URL e gerada acima, com endereco local e porta numerica.
    exec('start "" "' . $url . '"');
}

do {
    usleep(200000);
    $estado = proc_get_status($servidor);
} while ($estado['running']);
proc_close($servidor);
exit($estado['exitcode'] === 0 ? 0 : 1);
