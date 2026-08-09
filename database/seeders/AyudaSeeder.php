<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AyudaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Pedidos',
                'icono' => 'shopping_cart',
                'orden' => 1,
                'articulos' => [
                    ['pregunta' => '¿Cómo hago un pedido?', 'respuesta' => 'Entra a la app, selecciona un puesto del mapa o del listado, elige tus productos, confirma y paga. Recibirás notificaciones con cada avance de tu pedido.'],
                    ['pregunta' => '¿Puedo cancelar mi pedido?', 'respuesta' => 'Solo puedes cancelar mientras el pedido esté en estado "pendiente de pago". Una vez pagado o confirmado por el vendedor, ya no se puede cancelar.'],
                    ['pregunta' => '¿Cuánto tarda mi pedido?', 'respuesta' => 'El tiempo estimado depende del puesto: base de preparación más el tiempo por producto y por la carga de pedidos activos. Lo verás al confirmar tu pedido.'],
                ],
            ],
            [
                'nombre' => 'Pagos',
                'icono' => 'account_balance_wallet',
                'orden' => 2,
                'articulos' => [
                    ['pregunta' => '¿Qué métodos de pago aceptan?', 'respuesta' => 'Aceptamos pago con Yape, tarjeta y contraentrega según lo que habilite cada puesto. El pago se procesa de forma segura dentro de la app.'],
                    ['pregunta' => '¿Cuándo se aplica el IGV?', 'respuesta' => 'El IGV del 18% se aplica solo cuando solicitas comprobante de tipo "factura". Las boletas no incluyen IGV.'],
                    ['pregunta' => '¿Es seguro pagar con tarjeta?', 'respuesta' => 'Sí. Los pagos se procesan de forma cifrada dentro de la plataforma y jamás compartimos tus datos con el vendedor.'],
                ],
            ],
            [
                'nombre' => 'Entregas',
                'icono' => 'delivery_dining',
                'orden' => 3,
                'articulos' => [
                    ['pregunta' => '¿Puedo cambiar la dirección de entrega?', 'respuesta' => 'Sí. Mientras tu pedido no esté en camino puedes actualizar la dirección desde el detalle de tu pedido.'],
                    ['pregunta' => '¿Cómo sigo mi pedido?', 'respuesta' => 'Recibes notificaciones en cada cambio de estado: confirmado, en preparación, listo, en camino y entregado.'],
                    ['pregunta' => '¿Cuánto cuesta el delivery?', 'respuesta' => 'El costo de delivery lo define cada puesto y lo ves antes de confirmar el pago en el resumen de tu pedido.'],
                ],
            ],
            [
                'nombre' => 'Seguridad',
                'icono' => 'lock',
                'orden' => 4,
                'articulos' => [
                    ['pregunta' => '¿Cómo protejo mis datos?', 'respuesta' => 'Nunca compartas tu contraseña ni códigos de verificación con nadie. Nosotros jamás te los pediremos.'],
                    ['pregunta' => '¿Un vendedor me pide pago fuera de la app?', 'respuesta' => 'Desconfía y repórtalo de inmediato. Todo pago debe realizarse dentro de la plataforma para que esté protegido.'],
                    ['pregunta' => '¿Qué hago si veo una operación que no reconococo?', 'respuesta' => 'Contáctanos de inmediato por WhatsApp o correo y bloqueamos tu cuenta mientras investigamos.'],
                ],
            ],
        ];

        foreach ($categorias as $i => $cat) {
            $categoriaId = DB::table('help_categories')->insertGetId([
                'nombre' => $cat['nombre'],
                'icono' => $cat['icono'],
                'orden' => $cat['orden'],
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($cat['articulos'] as $j => $art) {
                DB::table('help_articles')->insert([
                    'category_id' => $categoriaId,
                    'pregunta' => $art['pregunta'],
                    'respuesta' => $art['respuesta'],
                    'orden' => $j + 1,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $config = [
            ['clave' => 'telefono', 'valor' => '955 123 456'],
            ['clave' => 'correo', 'valor' => 'soporte@altoque.com'],
            ['clave' => 'whatsapp', 'valor' => '+51955123456'],
            ['clave' => 'horario', 'valor' => 'Lun a Dom 9:00 – 22:00'],
        ];

        foreach ($config as $c) {
            DB::table('ayuda_config')->updateOrInsert(
                ['clave' => $c['clave']],
                ['valor' => $c['valor'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}