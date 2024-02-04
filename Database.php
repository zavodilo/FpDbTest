<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        $newQuery = "";
        $argsKey = 0;
        $query = mb_str_split($query);
        for ($i = 0; $i < count($query); $i++) {
            if ($query[$i] == '?') {
                $param = $args[$argsKey];
                $this->checkQuerySpecifierByIterator($query, $i, $param);
                $param = $this->convertQueryParamByIterator($query, $i, $args[$argsKey]);
                $param = $this->convertSpecifierDByIterator($query, $i, $param);
                $param = $this->convertSpecifierFByIterator($query, $i, $param);
                $param = $this->convertSpecifierPoundByIterator($query, $i, $param);
                $param = $this->convertSpecifierAByIterator($query, $i, $param);
                $param = $this->convertParamNullByIterator($query, $i, $param);
                if ($this->checkIsSpecifierPlusByIterator($query, $i)) {
                    $i++;
                    $argsKey++;
                }
                $newQuery .= $param;
            } elseif ($query[$i] == '{') {
                if ($args[$argsKey] === $this->skip()) {
                    for ($i2 = $i; $i2 < count($query); $i2++) {
                        if ($query[$i2] == '}') {
                            $i = $i2;
                            $argsKey++;
                            break;
                        }
                    }
                }
            } elseif ($query[$i] == '}') {
            } else {
                $newQuery .= $query[$i];
            }
        }

        return $newQuery;
    }


    private function checkQuerySpecifierByIterator(array $query, int $i, $param)
    {
        if (!isset($query[$i + 1]) || (isset($query[$i + 1]) && $query[$i + 1] != 'a' && $query[$i + 1] != '#' && $query[$i + 1] != 'd' && $query[$i + 1] != 'f')) {
            if (!is_string($param) && !is_bool($param) && !is_int($param) && !is_float($param) && !is_null($param)) {
                throw new Exception('Неверный тип спецификатора');
            }
        }
    }

    private function convertParamNullByIterator(array $query, int $i, $param)
    {
        if ($param === null && (!isset($query[$i + 1]) || (isset($query[$i + 1]) && $query[$i + 1] != 'a' && $query[$i + 1] != '#'))) {
            return 'NULL';
        }
        return $param;
    }


    private function convertQueryParamByIterator(array $query, int $i, $param)
    {
        if (isset($query[$i + 1]) && $query[$i + 1] != 'a' && $query[$i + 1] != '#' && $query[$i + 1] != 'd' && $query[$i + 1] != 'f') {
            return $this->convertParam($param);
        }
        return $param;
    }

    private function checkIsSpecifierPlusByIterator(array $query, int $i): bool
    {
        if (isset($query[$i + 1]) && ($query[$i + 1] == 'a' || $query[$i + 1] == '#' || $query[$i + 1] == 'd' || $query[$i + 1] == 'f')) {
            return true;
        }
        return false;
    }

    private function convertSpecifierDByIterator(array $query, int $i, $param)
    {
        if (isset($query[$i + 1]) && $query[$i + 1] == 'd') {
            $param = intval($param);
        }
        return $param;
    }

    private function convertSpecifierFByIterator(array $query, int $i, $param)
    {
        if (isset($query[$i + 1]) && $query[$i + 1] == 'f') {
            $param = floatval($param);
        }
        return $param;
    }

    private function convertSpecifierPoundByIterator(array $query, int $i, $param)
    {
        if (!isset($query[$i + 1]) || $query[$i + 1] != '#') {
            return $param;
        }
        if (is_array($param)) {
            $paramHelp = "";
            foreach ($param as $key => $value) {
                if (is_string($value)) {
                    $paramHelp .= '`' . $value . '`';
                } else {
                    $paramHelp .= $value;
                }
                if ($key != (count($param) - 1)) {
                    $paramHelp .= ', ';
                }
            }
            return $paramHelp;
        }
        if (is_string($param)) {
            $param = '`' . $param . '`';
        }
        return $param;
    }

    private function convertSpecifierAByIterator(array $query, int $i, $param)
    {
        if (isset($query[$i + 1]) && $query[$i + 1] == 'a' && is_array($param)) {
            $paramHelp = "";
            if (array_is_list($param)) {
                foreach ($param as $key => $value) {
                    $paramHelp .= $value;
                    if ($key != (count($param) - 1)) {
                        $paramHelp .= ', ';
                    }
                }
            } else {
                $n = 0;
                foreach ($param as $key => $value) {
                    $n++;
                    $paramHelp .= '`' . $key . '` = ' . $this->convertParam($value);
                    if ($n != (count($param))) {
                        $paramHelp .= ', ';
                    }

                }
            }
            return $paramHelp;
        }
        return $param;
    }

    private function convertParam($param)
    {
        if (is_string($param)) {
            $param = "'" . $param . "'";
        }
        if (is_bool($param)) {
            $param = intval($param);
        }
        if (is_null($param)) {
            $param = "NULL";
        }
        return $param;
    }

    public function skip()
    {
        return "#skip";
    }
}
