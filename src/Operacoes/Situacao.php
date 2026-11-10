<?php

declare(strict_types=1);

namespace Mecanica\Operacoes;

use Mecanica\{Fisica, Operacao, Resultado};

final class Situacao extends Operacao
{
    public function __construct(private Fisica $fisica)
    {
        parent::__construct('situacao', 'Situação-problema', 'Um bloco é puxado por uma força inclinada enquanto se desloca horizontalmente. Os valores iniciais são os do enunciado.');
    }

    public function campos(): array
    {
        return [
            $this->campo('m', 'Massa do bloco', 'kg', '20'),
            $this->campo('f', 'Módulo da força aplicada', 'N', '100'),
            $this->campo('angulo', 'Ângulo da força com a horizontal', '°', '30'),
            $this->campo('d', 'Deslocamento horizontal', 'm', '10'),
            $this->campo('t', 'Intervalo de tempo', 's', '5'),
            $this->campo('v0', 'Velocidade inicial no sentido do deslocamento', 'm/s', '0'),
            $this->seletor('modelo', 'Hipótese para a velocidade final', ['nenhum' => 'Não assumir aceleração constante', 'mruv' => 'Assumir aceleração constante'], 'nenhum'),
            $this->campo('v', 'Velocidade final medida (se conhecida)', 'm/s', '', [], true),
            $this->campo('h', 'Altura relativa ao nível de referência', 'm', '', [], true),
            $this->campo('mu', 'Coeficiente de atrito durante o deslizamento', '', '', [], true),
            $this->campo('g', 'Gravidade', 'm/s²', '9.81'),
        ];
    }

    public function calcular(array $d): Resultado
    {
        $m = $this->positivo($d, 'm', 'massa');
        $f = $this->numero($d, 'f', 'força', 0);
        $theta = $this->numero($d, 'angulo', 'ângulo', -90, 90);
        $dist = $this->numero($d, 'd', 'deslocamento', 0);
        $t = $this->positivo($d, 't', 'tempo');
        $v0 = $this->numero($d, 'v0', 'velocidade inicial', 0);
        $g = $this->positivo($d, 'g', 'gravidade');
        $modelo = $this->escolha($d, 'modelo', ['nenhum', 'mruv']);
        $v = $this->opcional($d, 'v', 'velocidade final', 0);
        $h = $this->opcional($d, 'h', 'altura');
        $mu = $this->opcional($d, 'mu', 'coeficiente de atrito', 0);
        [$fx, $fy] = $this->fisica->componentes($f, $theta);
        $w = $fx * $dist;
        $r = (new Resultado())->medir('Componente horizontal', $fx, 'N')->medir('Trabalho da força aplicada', $w, 'J')
            ->medir('Potência média da força aplicada', $w / $t, 'W')->medir('Componente vertical', $fy, 'N')
            ->explicar('Decomponha F: Fx = F cos θ e Fy = F sen θ. O deslocamento informado é horizontal; o trabalho da força aplicada é Fx · d e sua potência média é W/Δt.');
        $aceleracao = null;
        if ($modelo === 'mruv') {
            $estimada = 2 * ($dist / $t) - $v0;
            if ($estimada < 0 || ($v !== null && abs($v - $estimada) > max(0.01, 0.01 * abs($estimada)))) {
                $r->explicar('Os valores não são compatíveis com aceleração constante e deslocamento sempre no sentido positivo. A hipótese não foi usada.');
            } else {
                $v ??= $estimada;
                $aceleracao = ($v - $v0) / $t;
                $r->medir('Aceleração pela hipótese de MRUV', $aceleracao, 'm/s²')
                    ->explicar('Hipótese: aceleração constante. De d = (v₀ + v)·Δt/2 obtemos v = 2d/Δt − v₀.');
            }
        }
        $ec = $v === null ? null : $this->fisica->cinetica($m, $v);
        $ep = $h === null ? null : $this->fisica->potencial($m, $g, $h);
        if ($ec === null) {
            $r->explicar('A velocidade final e a energia cinética não são determinadas apenas por distância e tempo. Informe a velocidade medida ou selecione a hipótese de aceleração constante.');
        } else {
            $r->medir('Velocidade final', $v, 'm/s')->medir('Energia cinética final', $ec, 'J')
                ->explicar('Com a velocidade final, Ec = m·v²/2.');
        }
        if ($ep === null) {
            $r->explicar('Sem a altura de referência não calculamos Epg nem a energia mecânica total.');
        } else {
            $r->medir('Energia potencial gravitacional', $ep, 'J')->explicar('Com a altura informada, Epg = m·g·h.');
            if ($ec !== null) {
                $r->medir('Energia mecânica final', $ec + $ep, 'J');
            }
        }
        if ($fy > $m * $g) {
            $r->explicar('A componente vertical supera o peso. A hipótese de movimento mantendo contato com a superfície não se aplica.');
        } elseif ($mu === null) {
            $r->explicar('A resultante pelo modelo de forças fica em aberto sem informação sobre o atrito. Informe µ = 0 explicitamente para um plano sem atrito.');
        } else {
            $apoio = $this->fisica->apoio($m, $g, $f, $theta, $mu, 'positivo');
            $dinamica = $apoio['rx'] / $m;
            $r->medir('Normal', $apoio['normal'], 'N')->medir('Atrito (com sinal)', $apoio['atrito'], 'N')
                ->medir('Resultante horizontal pelas forças', $apoio['rx'], 'N')
                ->medir('Aceleração pelo modelo de forças', $dinamica, 'm/s²')
                ->explicar('Durante o deslizamento, N = mg − Fy e ΣFx = Fx − µN. Esse cálculo pressupõe contato e ausência de outras forças.');
            if ($aceleracao !== null && abs($aceleracao - $dinamica) > max(0.01, 0.01 * abs($aceleracao))) {
                $r->explicar('As acelerações dos dois modelos não coincidem: os dados de deslocamento/tempo e as forças informadas não descrevem o mesmo movimento com aceleração constante.');
            }
        }
        return $r;
    }
}
