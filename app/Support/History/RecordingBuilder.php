<?php

namespace App\Support\History;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Operações em massa não disparam eventos. Aqui, cada registro afetado é salvo
 * pelo próprio modelo, para aparecer no histórico. A gravação interna de um
 * modelo chega marcada por forModelSave() e segue o caminho normal.
 */
class RecordingBuilder extends Builder
{
    private bool $forModelSave = false;

    public function forModelSave(): static
    {
        $this->forModelSave = true;

        return $this;
    }

    public function update(array $values)
    {
        return $this->forModelSave
            ? parent::update($values)
            : $this->eachAffected(fn (Model $model) => $model->forceFill($values)->save());
    }

    /**
     * Na exclusão lógica, o escopo do framework chama update(), que já registra.
     */
    public function delete()
    {
        return $this->forModelSave || isset($this->onDelete)
            ? parent::delete()
            : $this->eachAffected(fn (Model $model) => $model->delete());
    }

    private function eachAffected(Closure $operation): int
    {
        return $this->getModel()->getConnection()->transaction(function () use ($operation) {
            $models = (clone $this)->get();

            foreach ($models as $model) {
                $operation($model);
            }

            return $models->count();
        });
    }
}
