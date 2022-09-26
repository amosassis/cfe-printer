<?php

namespace CfePrinter\Common;

class PaymentTypes
{
    public static $paymentTypes = [
        '01' => 'Dinheiro',
        '02' => 'Cheque',
        '03' => 'Cartão de Credito',
        '04' => 'Cartão de Debito',
        '05' => 'Credito Loja',
        '10' => 'Vale Alimentação',
        '11' => 'Vale Refeição',
        '12' => 'Vale Presente',
        '13' => 'Vale Combustível',
        '14' => 'Duplicata Mercantil',
        '15' => 'Boleto Bancario',
        '16' => 'Deposito Bancario',
        '17' => 'Pagamento Instantaneo (PIX)',
        '18' => 'Carteira Digital',
        '19' => 'Fidelidade/Cashback/Credito Virtual',
        '90' => 'Sem pagamento',
        '99' => 'Outros',
    ];


    public static function byCode(string $paymentCode)
    {
        return isset(self::$paymentTypes[$paymentCode]) ? self::$paymentTypes[$paymentCode] : 'Outros';
    }

    public static function all()
    {
        return self::$paymentTypes;
    }
}
