<?php

namespace App\Models\Concerns;

use App\Observers\ChangeRecorder;
use App\Support\History\RecordingBuilder;

/**
 * Monitora a entidade no histórico de alterações. A alteração e o seu registro
 * ficam na mesma transação: se um falhar, o outro é desfeito.
 */
trait RecordsChanges
{
    public static function bootRecordsChanges(): void
    {
        static::whenBooted(fn () => static::observe(ChangeRecorder::class));
    }

    public function newEloquentBuilder($query): RecordingBuilder
    {
        return new RecordingBuilder($query);
    }

    public function save(array $options = []): bool
    {
        return $this->getConnection()->transaction(fn () => parent::save($options));
    }

    public function delete(): ?bool
    {
        return $this->getConnection()->transaction(fn () => parent::delete());
    }

    protected function setKeysForSaveQuery($query)
    {
        return parent::setKeysForSaveQuery($query)->forModelSave();
    }
}
