<?php

declare(strict_types=1);

namespace AtlasRelay\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model that reads its table name from the atlas-relay config map used by every PRD-driven data structure.
 */
abstract class AtlasModel extends Model
{
    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config($this->tableNameConfigKey(), $this->defaultTableName()));

        parent::__construct($attributes);
    }

    abstract protected function tableNameConfigKey(): string;

    abstract protected function defaultTableName(): string;
}
