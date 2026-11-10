<?php

declare(strict_types=1);

namespace Mecanica;

abstract class Operacao
{
    public function __construct(private string $id, private string $titulo, private string $descricao) {}
    public function id(): string { return $this->id; }
    public function titulo(): string { return $this->titulo; }
    public function descricao(): string { return $this->descricao; }

    abstract public function campos(): array;
    abstract public function calcular(array $dados): Resultado;

    protected function campo(string $id, string $rotulo, string $unidade, string $exemplo, array $modos = [], bool $opcional = false): array
    {
        return compact('id', 'rotulo', 'unidade', 'exemplo', 'modos', 'opcional');
    }

    protected function seletor(string $id, string $rotulo, array $opcoes, string $exemplo, array $modos = []): array
    {
        return compact('id', 'rotulo', 'opcoes', 'exemplo', 'modos') + ['opcional' => false];
    }

    protected function escolha(array $dados, string $id, array $opcoes): string
    {
        $valor = $dados[$id] ?? '';
        if (!is_string($valor) || !in_array($valor, $opcoes, true)) {
            throw new \InvalidArgumentException('Selecione uma opção válida.');
        }
        return $valor;
    }

    protected function numero(array $dados, string $id, string $nome, ?float $minimo = null, ?float $maximo = null): float
    {
        $bruto = $dados[$id] ?? null;
        if (!is_string($bruto) && !is_int($bruto) && !is_float($bruto)) {
            throw new \InvalidArgumentException("Informe um número para {$nome}.");
        }
        $texto = str_replace(',', '.', trim((string) $bruto));
        if (!is_numeric($texto) || !is_finite($valor = (float) $texto)) {
            throw new \InvalidArgumentException("Informe um número válido para {$nome}.");
        }
        if ($minimo !== null && $valor < $minimo || $maximo !== null && $valor > $maximo) {
            throw new \InvalidArgumentException("Confira o intervalo permitido para {$nome}.");
        }
        return $valor;
    }

    protected function positivo(array $dados, string $id, string $nome): float
    {
        $valor = $this->numero($dados, $id, $nome);
        if ($valor <= 0) {
            throw new \InvalidArgumentException("O valor de {$nome} deve ser maior que zero.");
        }
        return $valor;
    }

    protected function opcional(array $dados, string $id, string $nome, ?float $minimo = null): ?float
    {
        $valor = $dados[$id] ?? '';
        return is_string($valor) && trim($valor) === '' ? null : $this->numero($dados, $id, $nome, $minimo);
    }
}
