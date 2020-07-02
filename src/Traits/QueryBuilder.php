<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Traits;

trait QueryBuilder
{
    private string $operator_equal = '=';
    private string $operator_gt = '>';
    private string $operator_gte = '>=';
    private string $operator_lt = '<';
    private string $operator_lte = '<=';
    private string $operator_between = '><';
    private string $operator_not_equal = '!=';
    private string $operator_like = '~';
    private string $operator_not_like = '!~';
    private string $operator_in = 'IN';
    private string $operator_or = 'or';

    private function operatorMapping(): array
    {
        return [
            $this->operator_equal => '=',
            $this->operator_gt => '%3E',
            $this->operator_gte => '%3E%3D',
            $this->operator_lt => '%3C',
            $this->operator_lte => '%3C%3D',
            $this->operator_between => '%3E%3C',
            $this->operator_not_equal => '!%3D',
            $this->operator_like => '~',
            $this->operator_not_like => '!~',
            $this->operator_in => 'IN',
            $this->operator_or => 'or',
        ];
    }

    private function buildQueryString(array $parameters = []) : string
    {
        $parameters = $this->buildQueryParameters($parameters);
        return $parameters ? '?' . $parameters : '';
    }

    private function buildQueryParameters(array $parameters = []): string
    {
        $queryParameters = [];
dump($parameters);
        foreach ($parameters as $column => $query) {

            if ($column === 'load_relations' && is_array($query)) {
                $queryParameters[] = $column . $this->_getOperator($this->operator_equal) . sprintf('["%s"]', implode(',', $query));
                continue;
            }

            if (is_array($query) && !array_key_exists('operator', $query)) {
                $queryParameters[] = $column . $this->_getOperator($this->operator_in) . sprintf('["%s"]', implode(',', $query));
                continue;
            }

            if (!is_array($query)) {
                $queryParameters[] = $column . $this->_getOperator($this->operator_equal) . urlencode((string)$query);
                continue;
            }

            if (!array_key_exists('operator', $query)) {
                $query['operator'] = $this->operator_equal;
            }

            if (!array_key_exists('value', $query)) {
                $query['value'] = '';
            }

            $queryParameters[] = $column . $this->_getOperator($query['operator']) . urlencode((string)$query['value']);
        }

        return implode('&', $queryParameters);
    }

    private function _getOperator($operator): string
    {
        if (array_key_exists($operator, $this->operatorMapping())) {

            $parsedOperator = $this->operatorMapping()[$operator];

            if ($operator != $this->operator_equal) {
                $parsedOperator = '=' . $parsedOperator . ',';
            }

            return $parsedOperator;
        }

        return $this->operator_equal;
    }
}
