<?php

namespace App\Support\History;

use Illuminate\Database\Eloquent\Builder;
use LogicException;

/**
 * Consultas do histórico: leem e inserem, mas não alteram nem excluem.
 */
class ImmutableBuilder extends Builder
{
    public function update(array $values)
    {
        $this->refuse();
    }

    public function delete()
    {
        $this->refuse();
    }

    private function refuse(): never
    {
        throw new LogicException('O histórico de alterações não pode ser alterado.');
    }
}
