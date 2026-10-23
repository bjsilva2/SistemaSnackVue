
<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';

import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import {
    House,
    LayoutGrid,
    ShoppingCart,
    Store,
    ChartNoAxesCombined,
    Settings,
    ChevronDown,
    ChevronRight,
    Tags,
    Package,
    Ruler,
    Truck,
    ClipboardList,
    CreditCard,
    Wallet,
    UtensilsCrossed,
    ChefHat,
    Users,
    Receipt,
} from 'lucide-vue-next';

const page = usePage();

const rutaActual = computed(() => page.url.split('?')[0]);

const secciones = [
    {
        titulo: 'CATÁLOGOS',
        icono: LayoutGrid,
        items: [
            { titulo: 'Grupos de Productos', ruta: '/catalogos/grupos', icono: Tags },
            { titulo: 'Marcas de Insumos', ruta: '/catalogos/marcas', icono: Package },
            { titulo: 'Unidades de Medida', ruta: '/catalogos/unidades', icono: Ruler },
            { titulo: 'Productos y Servicios', ruta: '/catalogos/productos', icono: Store },
        ],
    },
    {
        titulo: 'COMPRAS',
        icono: ShoppingCart,
        items: [
            { titulo: 'Proveedores', ruta: '/compras/proveedores', icono: Truck },
            { titulo: 'Órdenes de Compra', ruta: '/compras/ordenes', icono: ClipboardList },
            { titulo: 'Recepción de Compras', ruta: '/compras/recepciones', icono: Package },
            { titulo: 'Pago a Proveedores', ruta: '/compras/pagos', icono: CreditCard },
        ],
    },
    {
        titulo: 'VENTAS',
        icono: Store,
        items: [
            { titulo: 'Administrar Caja', ruta: '/ventas/cajas', icono: Wallet },
            { titulo: 'Punto de Venta', ruta: '/ventas/pos', icono: ShoppingCart },
            { titulo: 'Plano de Mesas', ruta: '/ventas/mesas', icono: UtensilsCrossed },
            { titulo: 'Pedidos de Cocina', ruta: '/ventas/cocina', icono: ChefHat },
            { titulo: 'Clientes', ruta: '/ventas/clientes', icono: Users },
        ],
    },
    {
        titulo: 'REPORTES',
        icono: ChartNoAxesCombined,
        items: [
            { titulo: 'Reporte de Ventas', ruta: '/reportes/ventas', icono: Receipt },
            { titulo: 'Informe Mensual', ruta: '/reportes/mensual', icono: ChartNoAxesCombined },
            { titulo: 'Reporte de Compras', ruta: '/reportes/compras', icono: ClipboardList },
            { titulo: 'Reporte de Stock', ruta: '/reportes/stock', icono: Package },
            { titulo: 'Reporte de Cajas', ruta: '/reportes/cajas', icono: Wallet },
        ],
    },
    {
        titulo: 'AJUSTES',
        icono: Settings,
        items: [
            { titulo: 'Empresa', ruta: '/ajustes/empresa', icono: Store },
            { titulo: 'Sucursales', ruta: '/ajustes/sucursales', icono: House },
            { titulo: 'Usuarios', ruta: '/ajustes/usuarios', icono: Users },
            { titulo: 'Roles y Permisos', ruta: '/ajustes/roles', icono: Settings },
        ],
    },
];

const abiertos = ref<string[]>(
    secciones
        .filter((seccion) =>
            seccion.items.some((item) => rutaActual.value.startsWith(item.ruta)),
        )
        .map((seccion) => seccion.titulo),
);

function alternar(titulo: string) {
    if (abiertos.value.includes(titulo)) {
        abiertos.value = abiertos.value.filter((item) => item !== titulo);
    } else {
        abiertos.value.push(titulo);
    }
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="sidebar-snack">
        <SidebarHeader class="cabecera">
            <Link href="/dashboard" class="marca">
                <div class="logo">S</div>
                <div class="nombre-marca">
                    <strong>SistemaSnack</strong>
                    <span>Sistema de ventas</span>
                </div>
            </Link>
        </SidebarHeader>

        <SidebarContent class="contenido-menu">
            <div class="titulo-menu">MENÚ PRINCIPAL</div>

            <Link
                href="/dashboard"
                class="enlace-principal"
                :class="{ seleccionado: rutaActual === '/dashboard' }"
            >
                <House :size="18" />
                <span>INICIO</span>
            </Link>

            <div v-for="seccion in secciones" :key="seccion.titulo" class="seccion">
                <button
                    type="button"
                    class="boton-seccion"
                    :class="{
                        seleccionado: seccion.items.some(
                            (item) => rutaActual.startsWith(item.ruta),
                        ),
                    }"
                    @click="alternar(seccion.titulo)"
                >
                    <component :is="seccion.icono" :size="18" />
                    <span>{{ seccion.titulo }}</span>

                    <ChevronDown
                        v-if="abiertos.includes(seccion.titulo)"
                        :size="15"
                        class="flecha"
                    />
                    <ChevronRight v-else :size="15" class="flecha" />
                </button>

                <div v-if="abiertos.includes(seccion.titulo)" class="submenus">
                    <Link
                        v-for="item in seccion.items"
                        :key="item.ruta"
                        :href="item.ruta"
                        class="submenu"
                        :class="{ activo: rutaActual === item.ruta }"
                    >
                        <component :is="item.icono" :size="15" />
                        <span>{{ item.titulo }}</span>
                    </Link>
                </div>
            </div>
        </SidebarContent>

        <SidebarFooter class="pie-menu">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>

<style scoped>
.sidebar-snack {
    --sidebar: #ffffff;
    --sidebar-foreground: #334155;
    --sidebar-border: #e2e8f0;
    --sidebar-accent: #e8f5f8;
    --sidebar-accent-foreground: #0e4054;
}

.cabecera {
    padding: 19px 13px;
    background: white;
    border-bottom: 1px solid #e2e8f0;
}

.marca {
    display: flex;
    align-items: center;
    gap: 11px;
    text-decoration: none;
}

.logo {
    width: 39px;
    height: 39px;
    border-radius: 50%;
    color: white;
    background: #2e9c62;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 23px;
    font-weight: 800;
    flex-shrink: 0;
}

.nombre-marca {
    display: flex;
    flex-direction: column;
    color: #173c52;
    white-space: nowrap;
}

.nombre-marca strong {
    font-size: 15px;
}

.nombre-marca span {
    color: #64748b;
    font-size: 11px;
}

.contenido-menu {
    padding: 14px 9px;
    background: linear-gradient(180deg, #ffffff, #f6f2ff);
    overflow-y: auto;
}

.titulo-menu {
    font-size: 10px;
    font-weight: 800;
    color: #64748b;
    margin: 8px 11px 15px;
}

.enlace-principal,
.boton-seccion {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px;
    border-radius: 6px;
    color: #334155;
    font-size: 12px;
    font-weight: 700;
    text-align: left;
    text-decoration: none;
    cursor: pointer;
}

.enlace-principal:hover,
.boton-seccion:hover {
    background: #e5eff5;
}

.boton-seccion span {
    flex: 1;
}

.flecha {
    margin-left: auto;
}

.seccion {
    margin-top: 5px;
}

.seleccionado {
    color: white;
    background: #073e50 !important;
}

.submenus {
    padding: 5px 0 8px 14px;
}

.submenu {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 10px 8px;
    color: #475569;
    font-size: 12px;
    border-radius: 5px;
    text-decoration: none;
}

.submenu:hover {
    background: #e3eaf1;
}

.submenu.activo {
    color: #0369a1;
    background: #dbeafe;
    font-weight: 700;
}

.pie-menu {
    border-top: 1px solid #e2e8f0;
    background: white;
    padding: 12px;
}

:deep([data-sidebar="sidebar"]) {
    background: white;
}
</style>
