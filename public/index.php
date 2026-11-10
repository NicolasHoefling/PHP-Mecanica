<?php

declare(strict_types=1);

$menu = require dirname(__DIR__) . '/bootstrap.php';
$post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$entrada = $post ? ($_POST['operacao'] ?? '') : ($_GET['operacao'] ?? 'forca');
$valida = is_string($entrada) && isset($menu[$entrada]);
$atual = $menu[$valida ? $entrada : 'forca'];
$sair = !$post && ($_GET['sair'] ?? '') === '1';
$resultado = null;
$erro = null;
if ($post) {
    try {
        if (!$valida) {
            throw new InvalidArgumentException('Escolha uma operação no menu.');
        }
        $resultado = $atual->calcular($_POST);
    } catch (InvalidArgumentException $e) {
        $erro = $e->getMessage();
    }
}

function h(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formato(float $valor): string
{
    if (abs($valor) >= 1e12 || ($valor != 0 && abs($valor) < 0.001)) {
        return str_replace('.', ',', sprintf('%.5g', $valor));
    }
    return rtrim(rtrim(number_format($valor, 3, ',', '.'), '0'), ',');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Calculadora didática de força, trabalho e energia mecânica.">
    <title><?= h($atual->titulo()) ?> · Mecânica | Tema II</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/app.js" defer></script>
</head>
<body data-app="mecanica">
<header class="cabecalho">
        <!-- <span aria-hidden="true">Ⅱ</span>  -->
        <!-- <strong>Mecânica</strong></a> -->
        <!-- <p>Técnicas de Programação <span>·</span> Nícolas Hoefling de C</p> -->
        <span class="nome-etapa">Nícolas Hoefling de C</span>
        <span class="identidade">Mecânica</span>
        <span class="nome-etapa">Trabalho II · Tecnicas De Programação</span>
</header>
<main class="layout">
    <section class="conteudo" aria-labelledby="titulo">
        <?php if ($sair): ?>
            <p class="sobrelinha">TEMA II</p>
            <h1 id="titulo">Calculadora encerrada</h1>
            <p class="descricao">Volte ao menu para iniciar um novo cálculo.</p>
            <a class="botao" href="./">Voltar à calculadora</a>
        <?php else: ?>
            <p class="sobrelinha">TEMA II <span> / </span> FORÇA, TRABALHO E ENERGIA</p>
            <h1 id="titulo"><?= h($atual->titulo()) ?></h1>
            <p class="descricao"><?= h($atual->descricao()) ?></p>
            <nav aria-label="Operações da calculadora" class="menu">
                <?php foreach ($menu as $op): ?>
                    <a href="?operacao=<?= h($op->id()) ?>" <?= $atual->id() === $op->id() ? 'aria-current="page"' : '' ?>><?= h($op->titulo()) ?></a>
                <?php endforeach; ?>
                <a href="?sair=1">Sair</a>
            </nav>
            <div class="paineis">
                <section class="cartao" aria-labelledby="entrada">
                    <h2 id="entrada">Dados de entrada</h2>
                    <form method="post" action="?operacao=<?= h($atual->id()) ?>">
                        <input type="hidden" name="operacao" value="<?= h($atual->id()) ?>">
                        <div class="campos">
                            <?php foreach ($atual->campos() as $campo):
                                $id = $campo['id'];
                                $valor = $post && array_key_exists($id, $_POST) ? $_POST[$id] : $campo['exemplo'];
                                $valor = is_scalar($valor) ? (string) $valor : '';
                                $modo = implode(' ', $campo['modos']);
                                $amplo = isset($campo['opcoes']) || isset($campo['multilinha']);
                            ?>
                            <div class="campo<?= $amplo ? ' amplo' : '' ?>" <?= $modo !== '' ? 'data-modos="' . h($modo) . '"' : '' ?>>
                                <label for="<?= h($id) ?>"><?= h($campo['rotulo']) ?><?= $campo['opcional'] ? ' <small>opcional</small>' : '' ?></label>
                                <?php if (isset($campo['opcoes'])): ?>
                                    <select id="<?= h($id) ?>" name="<?= h($id) ?>">
                                        <?php foreach ($campo['opcoes'] as $chave => $rotulo): ?>
                                            <option value="<?= h($chave) ?>" <?= $valor === $chave ? 'selected' : '' ?>><?= h($rotulo) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif (isset($campo['multilinha'])): ?>
                                    <textarea id="<?= h($id) ?>" name="<?= h($id) ?>" rows="4" required><?= h($valor) ?></textarea>
                                <?php else: ?>
                                    <div class="numero">
                                        <input id="<?= h($id) ?>" name="<?= h($id) ?>" type="text" inputmode="decimal"
                                            value="<?= h($valor) ?>" <?= !$campo['opcional'] && $modo === '' ? 'required' : '' ?>>
                                        <?php if ($campo['unidade'] !== ''): ?><span><?= h($campo['unidade']) ?></span><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($atual->id() === 'forca'): ?>
                            <p class="nota">Nos planos, o sentido positivo é para a direita ou para baixo da rampa. Use µ = 0 para desprezar o atrito.</p>
                        <?php endif; ?>
                        <?php if ($erro !== null): ?><p class="erro" role="alert"><?= h($erro) ?></p><?php endif; ?>
                        <div class="acoes">
                            <button class="botao" type="submit">Calcular <span aria-hidden="true">→</span></button>
                            <a href="?operacao=<?= h($atual->id()) ?>">Restaurar exemplo</a>
                        </div>
                    </form>
                </section>
                <section class="cartao resultado" aria-labelledby="saida">
                    <h2 id="saida">Resultado e resolução</h2>
                    <?php if ($resultado !== null): ?>
                        <dl class="medidas">
                            <?php foreach ($resultado->medidas() as $medida): ?>
                                <div><dt><?= h($medida['nome']) ?></dt><dd><?= h(formato((float) $medida['valor'])) ?> <span><?= h($medida['unidade']) ?></span></dd></div>
                            <?php endforeach; ?>
                        </dl>
                        <h3>Como chegamos lá</h3>
                        <ol><?php foreach ($resultado->passos() as $passo): ?><li><?= h($passo) ?></li><?php endforeach; ?></ol>
                    <?php else: ?>
                        <?php if (in_array($atual->id(), ['forca', 'situacao'], true)): ?>
                        <figure>
                            <svg viewBox="0 0 300 150" role="img" aria-label="Esquema de bloco sobre plano horizontal com força inclinada e deslocamento para a direita">
                                <defs><marker id="seta" markerWidth="7" markerHeight="7" refX="6" refY="3.5" orient="auto"><path d="M0 0 L7 3.5 L0 7Z" fill="currentColor"/></marker></defs>
                                <path d="M26 110H274M43 121l8-11m17 11l8-11m17 11l8-11m17 11l8-11m17 11l8-11m17 11l8-11m17 11l8-11m17 11l8-11m17 11l8-11" stroke="#77878b" fill="none"/>
                                <rect x="69" y="59" width="76" height="51" rx="4" class="bloco" stroke="currentColor"/>
                                <path d="M145 80L232 32M165 98H249" stroke="currentColor" stroke-width="2" marker-end="url(#seta)"/>
                                <text x="236" y="31">F</text><text x="255" y="102">d</text>
                            </svg>
                            <figcaption>Esquema de referência: força inclinada e deslocamento horizontal.</figcaption>
                        </figure>
                        <?php endif; ?>
                        <p class="descricao">Use os valores do exemplo ou informe os seus. Aqui aparecem as grandezas calculadas, suas unidades e as hipóteses usadas na resolução.</p>
                    <?php endif; ?>
                </section>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
