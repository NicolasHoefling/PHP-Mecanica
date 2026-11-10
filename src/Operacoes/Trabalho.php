<?php

declare(strict_types=1);

namespace Mecanica\Operacoes;

use Mecanica\{Fisica, Operacao, Resultado};

final class Trabalho extends Operacao
{
    public function __construct(private Fisica $fisica, private bool $resultante = false)
    {
        parent::__construct($resultante ? 'trabalho-resultante' : 'trabalho',
            $resultante ? 'Trabalho resultante' : 'Trabalho',
            $resultante ? 'Some o trabalho das forças constantes sobre um mesmo deslocamento.' : 'Só a componente da força ao longo do deslocamento realiza trabalho.');
    }

    public function campos(): array
    {
        return [
            $this->campo('d', 'Módulo do deslocamento', 'm', '10'),
            ...($this->resultante ? [[
                'id' => 'forcas', 'rotulo' => 'Forças e ângulos (uma força por linha: módulo; ângulo)',
                'exemplo' => "100; 30\n20; 180\n196,2; 90", 'modos' => [], 'opcional' => false, 'multilinha' => true,
            ]] : [
                $this->campo('f', 'Módulo da força', 'N', '100'),
                $this->campo('angulo', 'Ângulo entre força e deslocamento', '°', '30'),
            ]),
        ];
    }

    public function calcular(array $dados): Resultado
    {
        $d = $this->numero($dados, 'd', 'deslocamento', 0);
        $r = new Resultado();
        if (!$this->resultante) {
            $f = $this->numero($dados, 'f', 'força', 0);
            $theta = $this->numero($dados, 'angulo', 'ângulo', 0, 180);
            $w = $this->fisica->trabalho($f, $d, $theta);
            return $r->medir('Trabalho da força', $w, 'J')
                ->explicar("W = F · d · cos θ = {$f} · {$d} · cos({$theta}°).")
                ->explicar($w > 0 ? 'A força favorece o deslocamento.' : ($w < 0 ? 'A força se opõe ao deslocamento.' : 'A força não realiza trabalho nesse deslocamento.'));
        }
        $texto = $dados['forcas'] ?? null;
        if (!is_string($texto) || strlen($texto) > 4000) {
            throw new \InvalidArgumentException('Informe de 1 a 30 forças no formato módulo; ângulo.');
        }
        $linhas = preg_split('/\R/u', trim($texto));
        $linhas = $linhas === false ? [] : array_values(array_filter($linhas, static fn (string $linha): bool => trim($linha) !== ''));
        if (count($linhas) === 0 || count($linhas) > 30) {
            throw new \InvalidArgumentException('Informe de 1 a 30 forças, uma por linha.');
        }
        $total = 0.0;
        $parcelas = [];
        foreach ($linhas as $i => $linha) {
            $par = explode(';', $linha);
            $n = $i + 1;
            if (count($par) !== 2) {
                throw new \InvalidArgumentException("Linha {$n}: use módulo; ângulo.");
            }
            $f = $this->numero($par, '0', "força {$n}", 0);
            $theta = $this->numero($par, '1', "ângulo da força {$n}", 0, 180);
            $w = $this->fisica->trabalho($f, $d, $theta);
            $total += $w;
            $parcelas[] = $w;
        }
        $r->medir('Trabalho resultante', $total, 'J');
        foreach ($parcelas as $i => $w) {
            $r->medir('Trabalho da força ' . ($i + 1), $w, 'J');
        }
        return $r->explicar('Calcule Wᵢ = Fᵢ · d · cos θᵢ para cada linha. Depois some os trabalhos com seus sinais.')
            ->explicar('Quando a lista contém todas as forças que atuam no corpo, ΣW corresponde à variação da energia cinética.');
    }
}
