<?php

declare(strict_types=1);

namespace Mecanica\Operacoes;

use Mecanica\{Operacao, Resultado};

final class Potencia extends Operacao
{
    public function __construct()
    {
        parent::__construct('potencia', 'Potência', 'Compare a taxa média de realização de trabalho com a potência instantânea de uma força paralela à velocidade.');
    }

    public function campos(): array
    {
        return [
            $this->seletor('modo', 'Método', ['media' => 'Trabalho e intervalo de tempo', 'instantanea' => 'Força e velocidade na mesma direção'], 'media'),
            $this->campo('w', 'Trabalho', 'J', '866.0254', ['media']),
            $this->campo('t', 'Intervalo de tempo', 's', '5', ['media']),
            $this->campo('f', 'Força orientada ao longo da velocidade', 'N', '100', ['instantanea']),
            $this->campo('v', 'Módulo da velocidade', 'm/s', '4', ['instantanea']),
        ];
    }

    public function calcular(array $dados): Resultado
    {
        $modo = $this->escolha($dados, 'modo', ['media', 'instantanea']);
        return $modo === 'media'
            ? (new Resultado())->medir('Potência média', $this->numero($dados, 'w', 'trabalho') / $this->positivo($dados, 't', 'tempo'), 'W')
                ->explicar('P = W / Δt. Um watt equivale a um joule por segundo.')
            : (new Resultado())->medir('Potência instantânea', $this->numero($dados, 'f', 'força') * $this->numero($dados, 'v', 'velocidade', 0), 'W')
                ->explicar('P = F∥ · v. Se a força aponta contra a velocidade, informe F∥ negativa.');
    }
}
