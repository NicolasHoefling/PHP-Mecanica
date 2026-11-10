<?php

declare(strict_types=1);

namespace Mecanica;

final class Fisica
{
    public function componentes(float $forca, float $angulo): array
    {
        $r = deg2rad($angulo);
        return [$forca * $this->trig(cos($r)), $forca * $this->trig(sin($r))];
    }

    public function atrito(float $impulso, float $normal, float $mu, string $fase): float
    {
        $limite = $mu * $normal;
        if ($fase === 'negativo') {
            return $limite;
        }
        if ($fase === 'positivo') {
            return -$limite;
        }
        // Neste modelo didático o mesmo µ é usado no limiar e no deslizamento.
        return $impulso >= 0 ? -min($impulso, $limite) : min(-$impulso, $limite);
    }

    public function apoio(float $m, float $g, float $f, float $angulo, float $mu, string $fase): array
    {
        [$fx, $fy] = $this->componentes($f, $angulo);
        $peso = $m * $g;
        $normal = max(0, $peso - $fy);
        $atrito = $this->atrito($fx, $normal, $mu, $fase);
        $rx = $fx + $atrito;
        $ry = max(0, $fy - $peso);
        return compact('fx', 'fy', 'peso', 'normal', 'atrito', 'rx', 'ry');
    }

    public function equilibrio(float $anguloA, float $anguloB, float $fx, float $fy): array
    {
        [$ax, $ay] = $this->componentes(1, $anguloA);
        [$bx, $by] = $this->componentes(1, $anguloB);
        $det = $ax * $by - $ay * $bx;
        if (abs($det) < 1e-9) {
            throw new \InvalidArgumentException('As direções são colineares ou quase colineares; não há solução única para as duas forças.');
        }
        return [(-$fx * $by + $fy * $bx) / $det, (-$fy * $ax + $fx * $ay) / $det];
    }

    public function trabalho(float $f, float $d, float $angulo): float
    {
        return $this->componentes($f, $angulo)[0] * $d;
    }

    public function cinetica(float $m, float $v): float { return $m * $v ** 2 / 2; }
    public function potencial(float $m, float $g, float $h): float { return $m * $g * $h; }

    private function trig(float $valor): float { return abs($valor) < 1e-14 ? 0 : $valor; }
}
