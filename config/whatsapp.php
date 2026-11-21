<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp
    |--------------------------------------------------------------------------
    |
    | Número internacional (con código de país, sin + ni espacios) al que se
    | envían los pedidos del menú digital. Ej: 59165318803
    |
    */

    'phone' => env('WHATSAPP_PHONE', '59165318803'),

    /*
    |--------------------------------------------------------------------------
    | Negocio
    |--------------------------------------------------------------------------
    */

    'negocio' => [
        'nombre' => env('NEGOCIO_NOMBRE', 'Azúcar y Yo'),
        'direccion' => env('NEGOCIO_DIRECCION', ''),
        'telefono' => env('NEGOCIO_TELEFONO', ''),
    ],

    'horarios' => [
        'entre_semana' => env('WHATSAPP_HORARIO_SEMANA', 'Lun a Sáb: 09:00 – 20:00'),
        'domingo' => env('WHATSAPP_HORARIO_DOMINGO', 'Domingo: 10:00 – 14:00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Impresión de fichas
    |--------------------------------------------------------------------------
    |
    | Tamaño de la hoja de la ficha en centímetros (ancho x alto). 5 x 7
    | corresponde a la impresora térmica de 58 mm; usa 8 x 12 para A6 o
    | 21 x 29.7 para A4.
    |
    | La ficha se imprime siempre en una sola hoja: si el contenido no cabe
    | se compacta y se reduce proporcionalmente. Cuanto más alta la hoja,
    | menos se reduce el texto.
    |
    */

    'ficha' => [
        'ancho_cm' => env('FICHA_ANCHO_CM', 5),
        'alto_cm' => env('FICHA_ALTO_CM', 7),
    ],

];
