<?php

declare(strict_types=1);

namespace Mecanica;

final class Resultado
{
    private array $medidas = [];
    private array $passos = [];

    public function medir(string $nome, float $valor, string $unidade): self
    {
        if (!is_finite($valor)) {
            throw new \InvalidArgumentException('Os dados excedem o intervalo numérico do cálculo.');
        }
        $this->medidas[] = ['nome' => $nome, 'valor' => $valor == 0 ? 0 : $valor, 'unidade' => $unidade];
        return $this;
    }

    public function explicar(string $texto): self
    {
        $this->passos[] = $texto;
        return $this;
    }

    public function medidas(): array { return $this->medidas; }
    public function passos(): array { return $this->passos; }
}
