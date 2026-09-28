<?php
/** Badge del estado de una promoción. Espera $promo. */
$estadoPromo = Promocion::estado($promo);
$estilosPromo = [
    'vigente'    => ['● Vigente',    'bg-green-100 text-green-800'],
    'programada' => ['◷ Programada', 'bg-blue-100 text-blue-800'],
    'vencida'    => ['Vencida',      'bg-gray-100 text-gray-600'],
    'pausada'    => ['❚❚ Pausada',   'bg-yellow-100 text-yellow-800'],
];
[$txtEstado, $clsEstado] = $estilosPromo[$estadoPromo];
?>
<span class="px-2 py-0.5 text-xs rounded-full whitespace-nowrap <?= $clsEstado ?>"><?= $txtEstado ?></span>