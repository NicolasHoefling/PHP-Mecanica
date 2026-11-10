# Tema II — Força, Trabalho e Energia Mecânica

Projeto de Técnicas de Programação · Nícolas Hoefling de C.

No Windows, execute **INICIAR.bat**. Ele detecta o PHP portátil do pacote, o PHP no PATH ou o PHP do XAMPP. Abre a calculadora no navegador e usa a primeira porta disponível de 8021 a 8040. Mantenha a janela do iniciador aberta enquanto usar a página.

Para executar manualmente com PHP 8 ou superior, abra um terminal nesta pasta:

```sh
php -S 127.0.0.1:8021 -t public
php -n tests/validar-calculos.php
```

O formulário envia POST ao PHP; o JavaScript apenas mostra os campos pertinentes ao modelo selecionado. Não há API externa, banco de dados ou dependências do Composer.

## Menu

1. Força: segunda lei de Newton, plano horizontal, força inclinada, plano inclinado, dois blocos com polia, dois cabos e nó de treliça com duas barras.
2. Trabalho de uma força constante.
3. Trabalho resultante para uma lista de forças.
4. Energia cinética.
5. Energia potencial gravitacional.
6. Energia mecânica.
7. Potência média ou instantânea.
8. Situação-problema do enunciado.
9. Conversões entre unidades da mesma grandeza.
0. Sair da calculadora (para parar o servidor, feche o iniciador).

## Como o código usa POO

- `Operacao` define o contrato, os campos e a validação compartilhada. As operações concretas herdam dessa classe.
- `bootstrap.php` monta o menu. A página chama `calcular()` na operação selecionada, sem conhecer a implementação da fórmula.
- `Fisica` reúne as equações reaproveitadas pelas operações. `Resultado` encapsula as medidas, unidades e passos exibidos.
- `tests/validar-calculos.php` verifica casos do material, casos limite e entradas inválidas.

## Hipóteses do modelo

- Atrito de Coulomb simplificado, usando o mesmo µ como limite no repouso e durante o deslizamento. A força de atrito recebe sinal no eixo do movimento.
- A polia tem um bloco sobre uma mesa e outro pendurado, com corda e polia ideais. Cabos e barras resolvem equilíbrio de um único ponto em 2D.
- No exemplo integrado, a força, o deslocamento e o tempo bastam para calcular trabalho e potência média da força aplicada. Para deduzir a velocidade final, indique a hipótese de aceleração constante ou informe a velocidade medida. Altura e atrito são opcionais: valores ausentes não são tratados como zero.
- Ao fornecer dados dinâmicos e selecionar MRUV, o programa compara as acelerações e aponta inconsistências. O modelo da resultante pressupõe deslocamento horizontal durante o deslizamento.

O enunciado acompanha o ZIP na pasta `docs`.
