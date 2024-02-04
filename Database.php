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
                if (!isset($query[$i + 1]) || (isset($query[$i + 1]) && $query[$i + 1] != 'a' && $query[$i + 1] != '#' && $query[$i + 1] != 'd' && $query[$i + 1] != 'f')) {
                    if (!is_string($param) && !is_bool($param) && !is_int($param) && !is_float($param) && !is_null($param)) {
                        throw new Exception('Неверный тип спецификатора');
                    }
                }
                if ($args[$argsKey] === null && (!isset($query[$i + 1]) || (isset($query[$i + 1]) && $query[$i + 1] != 'a' && $query[$i + 1] != '#'))) {
                    $param = 'NULL';
                } elseif (isset($query[$i + 1])) {
                    if ($query[$i + 1] != 'a' && $query[$i + 1] != '#' && $query[$i + 1] != 'd' && $query[$i + 1] != 'f') {
                        $param = $this->convertParam($args[$argsKey]);
                    } else {
                        if ($query[$i + 1] == 'd') {
                            $param = intval($param);
                        } elseif ($query[$i + 1] == 'f') {
                            $param = floatval($param);
                        } elseif ($query[$i + 1] == '#' && is_array($args[$argsKey])) {
                            $param = "";
                            foreach ($args[$argsKey] as $key => $value) {
                                if (is_string($value)) {
                                    $param .= '`' . $value . '`';
                                } else {
                                    $param .= $value;
                                }
                                if ($key != (count($args[$argsKey]) - 1)) {
                                    $param .= ', ';
                                }
                            }
                        } elseif ($query[$i + 1] == '#' && is_string($args[$argsKey])) {
                            $param = '`' . $param . '`';
                        } elseif ($query[$i + 1] == 'a' && is_array($args[$argsKey])) {
                            $param = "";
                            if (array_is_list($args[$argsKey])) {
                                foreach ($args[$argsKey] as $key => $value) {
                                    $param .= $value;
                                    if ($key != (count($args[$argsKey]) - 1)) {
                                        $param .= ', ';
                                    }
                                }
                            } else {
                                $n = 0;
                                foreach ($args[$argsKey] as $key => $value) {
                                    $n++;
                                    $param .= '`' . $key . '` = ' . $this->convertParam($value);
                                    if ($n != (count($args[$argsKey]))) {
                                        $param .= ', ';
                                    }

                                }
                            }
                        }
                        $i++;
                        $argsKey++;
                    }
                } else {
                    $param = $this->convertParam($args[$argsKey]);
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
