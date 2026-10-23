
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    Package,
    ShoppingCart,
    Users,
    TrendingUp,
    AlertTriangle,
    Boxes,
} from 'lucide-vue-next';

const estadisticas = [
    { titulo: 'Productos', cantidad: 0, icono: Package, color: '#1675bd' },
    { titulo: 'Ventas', cantidad: 0, icono: ShoppingCart, color: '#ee4767' },
    { titulo: 'Clientes', cantidad: 0, icono: Users, color: '#f59e0b' },
    { titulo: 'Compras', cantidad: 0, icono: TrendingUp, color: '#10b981' },
];
</script>

<template>
    <Head title="Panel Principal" />

    <AppLayout>
        <div class="panel">
            <div class="encabezado">
                <div>
                    <h1>Panel Principal</h1>
                    <p>Bienvenido al SistemaSnack</p>
                </div>
            </div>

            <div class="tarjetas">
                <div
                    v-for="item in estadisticas"
                    :key="item.titulo"
                    class="tarjeta"
                >
                    <div
                        class="icono"
                        :style="{ background: item.color }"
                    >
                        <component :is="item.icono" :size="25" />
                    </div>

                    <div>
                        <h2>{{ item.cantidad }}</h2>
                        <p>{{ item.titulo }}</p>
                    </div>
                </div>
            </div>

            <div class="contenido">
                <div class="grafico">
                    <h3>Productos y Servicios Más Vendidos</h3>
                    <div class="sin-datos">
                        Todavía no existen ventas registradas.
                    </div>
                </div>

                <div class="alertas">
                    <div class="alerta">
                        <AlertTriangle :size="22" />
                        PRODUCTOS CON STOCK EN CERO
                    </div>

                    <div class="alerta">
                        <Boxes :size="22" />
                        PRODUCTOS CON STOCK MÍNIMO
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.panel {
    min-height: 100vh;
    padding: 25px;
    background: #f5f7fb;
    color: #253245;
}

.encabezado {
    margin-bottom: 25px;
}

.encabezado h1 {
    font-size: 25px;
    font-weight: 700;
}

.encabezado p {
    color: #64748b;
    margin-top: 5px;
}

.tarjetas {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.tarjeta {
    background: white;
    border-radius: 12px;
    padding: 22px;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: 0 2px 12px #0000000d;
}

.icono {
    width: 55px;
    height: 55px;
    border-radius: 12px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: white;
}

.tarjeta h2 {
    font-size: 28px;
    font-weight: bold;
}

.tarjeta p {
    color: #64748b;
    font-size: 14px;
}

.contenido {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.grafico {
    background: white;
    padding: 24px;
    border-radius: 12px;
    min-height: 310px;
}

.grafico h3 {
    font-weight: 700;
    margin-bottom: 20px;
}

.sin-datos {
    height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
}

.alertas {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.alerta {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #173d56;
    color: white;
    padding: 24px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: bold;
}

@media (max-width: 900px) {
    .tarjetas {
        grid-template-columns: repeat(2, 1fr);
    }

    .contenido {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 500px) {
    .tarjetas {
        grid-template-columns: 1fr;
    }
}
</style>
