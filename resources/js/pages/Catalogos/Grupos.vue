<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Search, Pencil, Power } from 'lucide-vue-next';

type Grupo = {
    id: number;
    nombre: string;
    estado: boolean;
    created_at: string;
};

type Pagina = {
    data: Grupo[];
    current_page: number;
    last_page: number;
    total: number;
};

const props = defineProps<{
    grupos: Pagina;
    filtros: { buscar: string };
}>();

const buscar = ref(props.filtros.buscar || '');
const modal = ref(false);
const editando = ref<number | null>(null);

const form = useForm<{ nombre: string; estado: boolean }>({
    nombre: '',
    estado: true,
});

function buscarGrupos() {
    router.get(
        '/catalogos/grupos',
        { buscar: buscar.value },
        { preserveState: true, replace: true },
    );
}

function nuevo() {
    editando.value = null;
    form.reset();
    form.clearErrors();
    modal.value = true;
}

function editar(grupo: Grupo) {
    editando.value = grupo.id;
    form.nombre = grupo.nombre;
    form.estado = grupo.estado;
    form.clearErrors();
    modal.value = true;
}

function guardar() {
    const opciones = {
        preserveScroll: true,
        onSuccess: () => {
            modal.value = false;
            form.reset();
        },
    };

    if (editando.value !== null) {
        form.put(`/catalogos/grupos/${editando.value}`, opciones);
    } else {
        form.post('/catalogos/grupos', opciones);
    }
}

function cambiarEstado(grupo: Grupo) {
    const accion = grupo.estado ? 'desactivar' : 'activar';

    if (!confirm(`¿Deseas ${accion} este grupo?`)) return;

    router.patch(`/catalogos/grupos/${grupo.id}/estado`, {}, {
        preserveScroll: true,
    });
}

function cambiarPagina(numero: number) {
    router.get(
        '/catalogos/grupos',
        { buscar: buscar.value, page: numero },
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Grupos de Productos" />

    <AppLayout>
        <div class="pagina">
            <div class="encabezado">
                <div>
                    <p class="ruta">Catálogos / Productos - Grupos</p>
                    <h1>Administrar Grupos</h1>
                </div>

                <button class="btn-nuevo" @click="nuevo">
                    <Plus :size="17" />
                    Nuevo
                </button>
            </div>

            <div class="tarjeta">
                <form class="buscador" @submit.prevent="buscarGrupos">
                    <input
                        v-model="buscar"
                        placeholder="Buscar grupo..."
                    />

                    <button type="submit">
                        <Search :size="17" />
                        Buscar
                    </button>
                </form>

                <div class="tabla-contenedor">
                    <table>
                        <thead>
                            <tr>
                                <th>CÓDIGO</th>
                                <th>NOMBRE / DESCRIPCIÓN</th>
                                <th>ESTADO</th>
                                <th>FECHA DE CREACIÓN</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="grupo in grupos.data" :key="grupo.id">
                                <td>{{ grupo.id }}</td>
                                <td>{{ grupo.nombre }}</td>

                                <td>
                                    <span :class="grupo.estado ? 'activo' : 'inactivo'">
                                        {{ grupo.estado ? 'ACTIVO' : 'INACTIVO' }}
                                    </span>
                                </td>

                                <td>
                                    {{ new Date(grupo.created_at).toLocaleString('es-BO') }}
                                </td>

                                <td>
                                    <div class="acciones">
                                        <button class="btn-editar" @click="editar(grupo)" title="Editar">
                                            <Pencil :size="16" />
                                        </button>

                                        <button class="btn-estado" @click="cambiarEstado(grupo)" title="Cambiar estado">
                                            <Power :size="16" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="grupos.data.length === 0">
                                <td colspan="5" class="sin-datos">
                                    No se encontraron grupos registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pie-tabla">
                    <span>Total de registros: {{ grupos.total }}</span>

                    <div class="paginacion">
                        <button
                            :disabled="grupos.current_page <= 1"
                            @click="cambiarPagina(grupos.current_page - 1)"
                        >
                            Anterior
                        </button>

                        <span>
                            {{ grupos.current_page }} / {{ grupos.last_page }}
                        </span>

                        <button
                            :disabled="grupos.current_page >= grupos.last_page"
                            @click="cambiarPagina(grupos.current_page + 1)"
                        >
                            Siguiente
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="modal" class="fondo-modal" @click.self="modal = false">
                <div class="modal">
                    <h2>{{ editando !== null ? 'Editar Grupo' : 'Nuevo Grupo' }}</h2>

                    <form @submit.prevent="guardar">
                        <label>Nombre / Descripción *</label>
                        <input v-model="form.nombre" maxlength="150" required />
                        <p v-if="form.errors.nombre" class="error">
                            {{ form.errors.nombre }}
                        </p>

                        <label>Estado</label>
                        <select v-model="form.estado">
                            <option :value="true">ACTIVO</option>
                            <option :value="false">INACTIVO</option>
                        </select>

                        <div class="botones-modal">
                            <button type="button" class="cancelar" @click="modal = false">
                                Cancelar
                            </button>

                            <button type="submit" class="guardar" :disabled="form.processing">
                                {{ form.processing ? 'Guardando...' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.pagina {
    padding: 26px;
    min-height: 100vh;
    background: #f5f7fb;
    color: #243447;
}

.encabezado {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
}

.ruta {
    color: #8793a5;
    font-size: 12px;
    margin-bottom: 9px;
}

h1 {
    font-size: 22px;
    font-weight: 700;
}

.btn-nuevo,
.buscador button,
.guardar {
    background: #1685c5;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 10px 16px;
    display: flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
}

.tarjeta {
    background: white;
    border-radius: 9px;
    padding: 20px;
    box-shadow: 0 2px 12px #0000000c;
}

.buscador {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-bottom: 16px;
}

.buscador input,
.modal input,
.modal select {
    border: 1px solid #d5dce5;
    border-radius: 5px;
    padding: 10px;
    background: white;
    color: #334155;
}

.buscador input {
    width: 250px;
}

.tabla-contenedor {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 13px;
}

thead {
    background: #073e50;
    color: white;
}

th, td {
    padding: 13px 12px;
    border-bottom: 1px solid #edf0f4;
}

.activo {
    color: #16803d;
    background: #dbfce7;
    padding: 5px 9px;
    border-radius: 4px;
    font-weight: 700;
}

.inactivo {
    color: #b91c1c;
    background: #fee2e2;
    padding: 5px 9px;
    border-radius: 4px;
    font-weight: 700;
}

.acciones {
    display: flex;
    gap: 7px;
}

.btn-editar,
.btn-estado {
    background: #1685c5;
    color: white;
    border-radius: 5px;
    padding: 8px;
    cursor: pointer;
}

.btn-estado {
    background: #64748b;
}

.sin-datos {
    text-align: center;
    padding: 28px;
    color: #94a3b8;
}

.pie-tabla {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding-top: 18px;
    color: #64748b;
    font-size: 12px;
}

.paginacion {
    display: flex;
    gap: 10px;
    align-items: center;
}

.paginacion button {
    padding: 7px 12px;
    border: 1px solid #d5dce5;
    border-radius: 5px;
}

.paginacion button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.fondo-modal {
    position: fixed;
    inset: 0;
    z-index: 100;
    background: #0008;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.modal {
    width: 100%;
    max-width: 460px;
    background: white;
    padding: 26px;
    border-radius: 10px;
    box-shadow: 0 10px 40px #0003;
}

.modal h2 {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
}

.modal form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.error {
    color: #dc2626;
    font-size: 12px;
}

.botones-modal {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 18px;
}

.cancelar {
    background: #64748b;
    color: white;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
}
</style>
