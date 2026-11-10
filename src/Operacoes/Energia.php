<?php

declare(strict_types=1);

namespace Mecanica\Operacoes;

use Mecanica\{Fisica, Operacao, Resultado};

final class Energia extends Operacao
{
    private const TITULOS = ['cinetica' => 'Energia cinética', 'potencial' => 'Energia potencial', 'mecanica' => 'Energia mecânica'];

    public function __construct(private Fisica $fisica, private string $tipo)
    {
        parent::__construct($tipo, self::TITULOS[$tipo], match ($tipo) {
            'cinetica' => 'A energia cinética depende da massa e do quadrado da velocidade.',
            'potencial' => 'A energia potencial gravitacional depende da altura em relação ao nível de referência.',
            'mecanica' => 'A energia mecânica é a soma da energia cinética e da potencial gravitacional.',
        });
    }

    public function campos(): array
    {
        $campos = [$this->campo('m', 'Massa', 'kg', '20')];
        if ($this->tipo !== 'potencial') {
            $campos[] = $this->campo('v', 'Módulo da velocidade', 'm/s', '4');
        }
        if ($this->tipo !== 'cinetica') {
            $campos[] = $this->campo('g', 'Gravidade', 'm/s²', '9.81');
            $campos[] = $this->campo('h', 'Altura relativa ao nível de referência', 'm', '2');
        }
        return $campos;
    }

    public function calcular(array $dados): Resultado
    {
        $m = $this->positivo($dados, 'm', 'massa');
        $ec = $this->tipo === 'potencial' ? null : $this->fisica->cinetica($m, $this->numero($dados, 'v', 'velocidade', 0));
        $ep = $this->tipo === 'cinetica' ? null : $this->fisica->potencial($m,
            $this->positivo($dados, 'g', 'gravidade'), $this->numero($dados, 'h', 'altura'));
        $r = new Resultado();
        if ($ec !== null && $ep !== null) {
            $r->medir('Energia mecânica', $ec + $ep, 'J');
        }
        if ($ec !== null) {
            $r->medir('Energia cinética', $ec, 'J')->explicar('Ec = m · v² / 2. Eleve a velocidade ao quadrado antes de multiplicar pela massa.');
        }
        if ($ep !== null) {
            $r->medir('Energia potencial gravitacional', $ep, 'J')->explicar('Epg = m · g · h. A altura pode ser negativa abaixo do nível escolhido como referência.');
        }
        return $this->tipo === 'mecanica' ? $r->explicar('Em = Ec + Epg, no mesmo instante e com o mesmo nível de referência.') : $r;
    }
}
