<?php

namespace Paw\Core;

use Paw\Core\Database\QueryBuilder;
use Paw\Core\Traits\Loggable;


class Model 
{
    use Loggable;

    public $queryBuilder;
    public $logger;
    
    public function setQueryBuilder(QueryBuilder $qb)
    {
        $this->queryBuilder = $qb;
    }
    public function toArray()
    {
        // 🔥 Obtiene todas las propiedades públicas y filtra las excluidas
        return array_filter(get_object_vars($this), fn($key) => !in_array($key, ['queryBuilder', 'logger']), ARRAY_FILTER_USE_KEY);
    }
}

