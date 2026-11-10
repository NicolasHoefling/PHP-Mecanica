<?php

declare(strict_types=1);

namespace Mecanica\Operacoes;

use Mecanica\{Operacao, Resultado};

final class Conversao extends Operacao
{
    private const UNIDADES = [
        'massa' => ['kg' => 1, 'g' => 0.001, 't' => 1000],
        'distancia' => ['m' => 1, 'cm' => 0.01, 'km' => 1000],
        'tempo' => ['s' => 1, 'min' => 60, 'h' => 3600],
        'velocidade' => ['m/s' => 1, 'km/h' => 1 / 3.6, 'cm/s' => 0.01],
        'forca' => ['N' => 1, 'kN' => 1000, 'mN' => 0.001],
        'energia' => ['J' => 1, 'kJ' => 1000, 'Wh' => 3600, 'kWh' => 3600000],
        'potencia' => ['W' => 1, 'kW' => 1000, 'mW' => 0.001],
        'angulo' => ['rad' => 1, '°' => M_PI / 180],
    ];

    public function __construct()
    {
        parent::__construct('conversoes', 'Conversões de unidades', 'Converta valores entre unidades da mesma grandeza antes de usar uma fórmula.');
    }

    public function campos(): array
    {
        $campos = [
            $this->seletor('modo', 'Grandeza', ['massa' => 'Massa', 'distancia' => 'Distância', 'tempo' => 'Tempo', 'velocidade' => 'Velocidade',
                'forca' => 'Força', 'energia' => 'Energia ou trabalho', 'potencia' => 'Potência', 'angulo' => 'Ângulo'], 'velocidade'),
            $this->campo('valor', 'Valor de origem', '', '72'),
        ];
        foreach (self::UNIDADES as $grupo => $fatores) {
            $unidades = array_keys($fatores);
            $opcoes = array_combine($unidades, $unidades);
            $campos[] = $this->seletor('de_' . $grupo, 'Unidade de origem', $opcoes, $unidades[1], [$grupo]);
            $campos[] = $this->seletor('para_' . $grupo, 'Unidade de destino', $opcoes, $unidades[0], [$grupo]);
        }
        return $campos;
    }

    public function calcular(array $dados): Resultado
    {
        $modo = $this->escolha($dados, 'modo', array_keys(self::UNIDADES));
        $fatores = self::UNIDADES[$modo];
        $de = $this->escolha($dados, 'de_' . $modo, array_keys($fatores));
        $para = $this->escolha($dados, 'para_' . $modo, array_keys($fatores));
        $valor = $this->numero($dados, 'valor', 'valor de origem');
        $razao = $fatores[$de] / $fatores[$para];
        return (new Resultado())->medir('Valor convertido', $valor * $razao, $para)
            ->explicar("Aplique o fator {$razao} de {$de} para {$para}: valor convertido = {$valor} · {$razao}.");
    }
}
