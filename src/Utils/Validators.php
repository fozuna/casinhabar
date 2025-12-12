<?php
namespace App\Utils;

class Validators
{
    public static function onlyDigits(string $v): string
    {
        return preg_replace('/\D+/', '', $v);
    }

    public static function isCPF(string $v): bool
    {
        $cpf = self::onlyDigits($v);
        if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$t] != $d) return false;
        }
        return true;
    }

    public static function isCNPJ(string $v): bool
    {
        $cnpj = self::onlyDigits($v);
        if (strlen($cnpj) != 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) return false;
        $b1 = [5,4,3,2,9,8,7,6,5,4,3,2];
        $b2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        $sum = 0;
        for ($i=0;$i<12;$i++) $sum += intval($cnpj[$i]) * $b1[$i];
        $d1 = $sum % 11; $d1 = $d1 < 2 ? 0 : 11 - $d1;
        if (intval($cnpj[12]) != $d1) return false;
        $sum = 0;
        for ($i=0;$i<13;$i++) $sum += intval($cnpj[$i]) * $b2[$i];
        $d2 = $sum % 11; $d2 = $d2 < 2 ? 0 : 11 - $d2;
        return intval($cnpj[13]) == $d2;
    }
}

