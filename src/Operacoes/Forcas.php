<?php

declare(strict_types=1);

namespace Mecanica\Operacoes;

use Mecanica\{Fisica, Operacao, Resultado};

final class Forcas extends Operacao
{
    private const MODELOS = [
        'newton' => 'Segunda lei de Newton',
        'horizontal' => 'Plano horizontal',
        'inclinada' => 'Plano horizontal com força inclinada',
        'rampa' => 'Plano inclinado',
        'polia' => 'Dois blocos com polia',
        'cabos' => 'Equilíbrio com dois cabos',
        'trelica' => 'Nó de treliça com duas barras',
    ];

    public function __construct(private Fisica $fisica)
    {
        parent::__construct('forca', 'Força', 'Escolha a situação física. As forças são decompostas nos eixos do problema antes de calcular a resultante.');
    }

    public function campos(): array
    {
        $apoios = ['horizontal', 'inclinada', 'rampa', 'polia'];
        return [
            $this->seletor('modo', 'Situação física', self::MODELOS, 'horizontal'),
            $this->campo('m', 'Massa do bloco', 'kg', '20', ['newton', 'horizontal', 'inclinada', 'rampa']),
            $this->campo('a', 'Aceleração orientada', 'm/s²', '2', ['newton']),
            $this->campo('g', 'Gravidade', 'm/s²', '9.81', $apoios),
            $this->campo('f', 'Força aplicada', 'N', '100', ['horizontal', 'inclinada']),
            $this->campo('angulo', 'Ângulo (acima da horizontal é positivo)', '°', '30', ['inclinada']),
            $this->campo('rampa', 'Inclinação da rampa', '°', '30', ['rampa']),
            $this->campo('mu', 'Coeficiente de atrito (0 para sem atrito)', '', '0.20', $apoios),
            $this->seletor('fase', 'Estado do movimento', [
                'repouso' => 'Inicialmente em repouso',
                'positivo' => 'Já desliza no sentido positivo',
                'negativo' => 'Já desliza no sentido negativo',
            ], 'repouso', ['horizontal', 'inclinada', 'rampa']),
            $this->seletor('fasePolia', 'Estado do sistema', [
                'repouso' => 'Inicialmente em repouso',
                'positivo' => 'O bloco pendurado já está descendo',
            ], 'repouso', ['polia']),
            $this->campo('m1', 'Massa sobre a mesa', 'kg', '3', ['polia']),
            $this->campo('m2', 'Massa pendurada', 'kg', '2', ['polia']),
            $this->campo('carga', 'Carga vertical para baixo', 'N', '100', ['cabos']),
            $this->campo('esq', 'Ângulo do cabo esquerdo com a horizontal', '°', '45', ['cabos']),
            $this->campo('dir', 'Ângulo do cabo direito com a horizontal', '°', '45', ['cabos']),
            $this->campo('barraA', 'Direção da barra A a partir de +x', '°', '0', ['trelica']),
            $this->campo('barraB', 'Direção da barra B a partir de +x', '°', '90', ['trelica']),
            $this->campo('fx', 'Componente x da carga externa', 'N', '50', ['trelica']),
            $this->campo('fy', 'Componente y da carga externa', 'N', '-100', ['trelica']),
        ];
    }

    public function calcular(array $dados): Resultado
    {
        $modo = $this->escolha($dados, 'modo', array_keys(self::MODELOS));
        return match ($modo) {
            'newton' => (new Resultado())->medir('Força resultante', $this->positivo($dados, 'm', 'massa') * $this->numero($dados, 'a', 'aceleração'), 'N')->explicar('Escolha um eixo. A segunda lei de Newton é ΣF = m · a; o sinal indica o sentido.'),
            'horizontal', 'inclinada' => $this->plano($dados, $modo),
            'rampa' => $this->rampa($dados),
            'polia' => $this->polia($dados),
            'cabos', 'trelica' => $this->equilibrio($dados, $modo),
        };
    }

    private function plano(array $d, string $modo): Resultado
    {
        $m = $this->positivo($d, 'm', 'massa');
        $g = $this->positivo($d, 'g', 'gravidade');
        $f = $this->numero($d, 'f', 'força aplicada', 0);
        $theta = $modo === 'horizontal' ? 0 : $this->numero($d, 'angulo', 'ângulo', -90, 90);
        $mu = $this->numero($d, 'mu', 'coeficiente de atrito', 0);
        $fase = $this->escolha($d, 'fase', ['repouso', 'positivo', 'negativo']);
        $p = $this->fisica->apoio($m, $g, $f, $theta, $mu, $fase);
        $r = (new Resultado())->medir('Resultante horizontal', $p['rx'], 'N')
            ->medir('Aceleração horizontal', $p['rx'] / $m, 'm/s²')->medir('Peso', $p['peso'], 'N')
            ->medir('Normal', $p['normal'], 'N')->medir('Atrito (com sinal)', $p['atrito'], 'N')
            ->medir('Força aplicada · x', $p['fx'], 'N')->medir('Força aplicada · y', $p['fy'], 'N')
            ->medir('Resultante vertical', $p['ry'], 'N')
            ->medir('Resultante total (módulo)', hypot($p['rx'], $p['ry']), 'N')
            ->explicar('Eixos: +x para a direita e +y para cima. Decomponha F em F cos θ e F sen θ. O peso é mg e aponta para baixo.')
            ->explicar('Com contato, N = mg − Fy. No repouso, o atrito equilibra a força até o limite µN. Durante o deslizamento, o atrito se opõe ao movimento.')
            ->explicar('Some as forças em cada eixo e divida por m para obter a aceleração. Foi usado o mesmo µ no limiar e no deslizamento.');
        if ($p['ry'] > 0) {
            $r->medir('Aceleração vertical', $p['ry'] / $m, 'm/s²')->explicar('Fy supera o peso: o bloco perde contato. Normal e atrito são zero.');
        }
        return $r;
    }

    private function rampa(array $d): Resultado
    {
        $m = $this->positivo($d, 'm', 'massa');
        $g = $this->positivo($d, 'g', 'gravidade');
        $theta = $this->numero($d, 'rampa', 'inclinação', 0, 90);
        $mu = $this->numero($d, 'mu', 'coeficiente de atrito', 0);
        $fase = $this->escolha($d, 'fase', ['repouso', 'positivo', 'negativo']);
        $peso = $m * $g;
        [$n, $paralela] = $this->fisica->componentes($peso, $theta);
        $atrito = $this->fisica->atrito($paralela, $n, $mu, $fase);
        $fr = $paralela + $atrito;
        return (new Resultado())->medir('Resultante ao longo da rampa', $fr, 'N')
            ->medir('Aceleração', $fr / $m, 'm/s²')->medir('Peso', $peso, 'N')
            ->medir('Peso paralelo', $paralela, 'N')->medir('Peso perpendicular', $n, 'N')
            ->medir('Normal', $n, 'N')->medir('Atrito (com sinal)', $atrito, 'N')
            ->explicar('Sentido positivo: descendo a rampa. P∥ = mg sen θ e N = P⊥ = mg cos θ.')
            ->explicar('No repouso o atrito pode equilibrar P∥ até µN. Se já estiver deslizando, o atrito aponta no sentido oposto à velocidade.')
            ->explicar($fr > 0 ? 'A aceleração aponta para baixo da rampa.' : ($fr < 0
                ? 'A aceleração aponta para cima da rampa. Isso não determina, por si só, o sentido da velocidade.'
                : ($fase === 'repouso' ? 'O bloco permanece em equilíbrio.' : 'A aceleração é nula; o deslizamento pode continuar com velocidade constante.')));
    }

    private function polia(array $d): Resultado
    {
        $m1 = $this->positivo($d, 'm1', 'massa sobre a mesa');
        $m2 = $this->positivo($d, 'm2', 'massa pendurada');
        $g = $this->positivo($d, 'g', 'gravidade');
        $mu = $this->numero($d, 'mu', 'coeficiente de atrito', 0);
        $fase = $this->escolha($d, 'fasePolia', ['repouso', 'positivo']);
        $n = $m1 * $g;
        $p2 = $m2 * $g;
        $atrito = $this->fisica->atrito($p2, $n, $mu, $fase);
        $a = ($p2 + $atrito) / ($m1 + $m2);
        return (new Resultado())->medir('Aceleração do sistema', $a, 'm/s²')
            ->medir('Tensão na corda', $p2 - $m2 * $a, 'N')
            ->medir('Resultante do bloco na mesa', $m1 * $a, 'N')->medir('Resultante do bloco pendurado', $m2 * $a, 'N')
            ->medir('Normal na mesa', $n, 'N')->medir('Atrito na mesa (com sinal)', $atrito, 'N')
            ->explicar('Corda inextensível e polia ideais. O sentido positivo é para a polia no bloco da mesa e para baixo no bloco pendurado.')
            ->explicar('Some T + Fat = m₁a e m₂g − T = m₂a para encontrar a aceleração. Depois use T = m₂(g − a).');
    }

    private function equilibrio(array $d, string $modo): Resultado
    {
        if ($modo === 'cabos') {
            [$a, $b] = $this->fisica->equilibrio(
                180 - $this->numero($d, 'esq', 'ângulo esquerdo', 0, 90),
                $this->numero($d, 'dir', 'ângulo direito', 0, 90),
                0, -$this->numero($d, 'carga', 'carga vertical', 0)
            );
            return (new Resultado())->medir('Tensão no cabo esquerdo', abs($a), 'N')->medir('Tensão no cabo direito', abs($b), 'N')
                ->explicar('Converta os ângulos para o mesmo eixo x. Monte ΣFx = 0 e ΣFy = 0: as componentes horizontais se cancelam e as verticais sustentam a carga.');
        }
        [$a, $b] = $this->fisica->equilibrio($this->numero($d, 'barraA', 'ângulo da barra A', -360, 360),
            $this->numero($d, 'barraB', 'ângulo da barra B', -360, 360),
            $this->numero($d, 'fx', 'componente x'), $this->numero($d, 'fy', 'componente y'));
        $estado = static fn (float $f): string => $f > 1e-9 ? 'tração' : ($f < -1e-9 ? 'compressão' : 'sem esforço');
        return (new Resultado())->medir('Barra A · ' . $estado($a), $a, 'N')->medir('Barra B · ' . $estado($b), $b, 'N')
            ->explicar('Método dos nós para um único nó com duas barras incógnitas. As direções apontam do nó para fora. Resolva ΣFx = 0 e ΣFy = 0.')
            ->explicar('Sinal positivo indica tração; sinal negativo, compressão. As cargas externas devem incluir as demais forças já conhecidas.');
    }
}
