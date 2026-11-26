<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LockKeyhole, Mail, LoaderCircle, ShieldCheck, ArrowRight } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const mostrarClave = ref(false);
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Iniciar sesión | SistemaSnack" />
    <main class="login-page">
        <div class="login-shell">
            <aside class="brand-panel">
                <img src="/logo-sistema.svg" alt="Logo de SistemaSnack" class="brand-logo-large" />
            </aside>

            <section class="form-panel">
                <div class="form-inner">
                    <div class="mobile-brand">
                        <img src="/logo-sistema.svg" alt="" />
                        <span>SistemaSnack</span>
                    </div>
                    <div class="form-heading">
                        <span class="overline">BIENVENIDO</span>
                        <h2>Iniciar sesión</h2>
                        <p>Ingresa tus credenciales para acceder al sistema.</p>
                    </div>

                    <div v-if="status" class="notice" role="status">{{ status }}</div>

                    <form class="login-form" @submit.prevent="submit">
                        <div class="field">
                            <label for="email">Correo electrónico</label>
                            <div class="input-wrap" :class="{ invalid: !!form.errors.email }">
                                <Mail :size="18" class="field-icon" aria-hidden="true" />
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="Ingresa tu correo electrónico"
                                    autocomplete="username"
                                    inputmode="email"
                                    required
                                    autofocus
                                    :aria-invalid="!!form.errors.email"
                                    tabindex="1"
                                />
                            </div>
                            <InputError :message="form.errors.email" />
                        </div>
                        <div class="field">
                            <div class="label-row">
                                <label for="password">Contraseña</label>
                                <a v-if="canResetPassword" :href="route('password.request')" class="forgot" tabindex="5">¿Olvidaste tu contraseña?</a>
                            </div>
                            <div class="input-wrap" :class="{ invalid: !!form.errors.password }">
                                <LockKeyhole :size="18" class="field-icon" aria-hidden="true" />
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="mostrarClave ? 'text' : 'password'"
                                    placeholder="Ingresa tu contraseña"
                                    autocomplete="current-password"
                                    required
                                    :aria-invalid="!!form.errors.password"
                                    tabindex="2"
                                />
                                <button class="reveal" type="button" :aria-label="mostrarClave ? 'Ocultar contraseña' : 'Mostrar contraseña'" :aria-pressed="mostrarClave" @click="mostrarClave = !mostrarClave">
                                    <EyeOff v-if="mostrarClave" :size="18" aria-hidden="true" />
                                    <Eye v-else :size="18" aria-hidden="true" />
                                </button>
                            </div>
                            <InputError :message="form.errors.password" />
                        </div>
                        <label class="remember">
                            <input v-model="form.remember" type="checkbox" tabindex="3" />
                            <span>Recordar mi sesión</span>
                        </label>

                        <button class="submit-button" type="submit" :disabled="form.processing" tabindex="4">
                            <LoaderCircle v-if="form.processing" class="animate-spin" :size="19" aria-hidden="true" />
                            <span>{{ form.processing ? 'Ingresando...' : 'INGRESAR AL SISTEMA' }}</span>
                            <ArrowRight v-if="!form.processing" :size="18" aria-hidden="true" />
                        </button>
                    </form>

                    <div class="access-note">
                        <ShieldCheck :size="17" aria-hidden="true" />
                        <span>¿No tienes una cuenta? Solicita tu acceso al administrador.</span>
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>

<style scoped>
.login-page{min-height:100dvh;display:flex;align-items:center;justify-content:center;background:#edf3f7;padding:28px;color:#25384e;font-family:inherit}
.login-shell{width:min(1080px,100%);min-height:630px;display:grid;grid-template-columns:1.02fr 1fr;overflow:hidden;border-radius:18px;background:white;box-shadow:0 20px 60px rgba(8,40,60,.13)}
.brand-panel{display:flex;align-items:center;justify-content:center;background:linear-gradient(150deg,#07354a 4%,#07516a 56%,#087d7e 100%);padding:45px;min-width:0}
.brand-logo-large{display:block;width:min(350px,88%);height:auto;max-height:390px;object-fit:contain;filter:drop-shadow(0 8px 20px rgba(0,0,0,.14))}
.form-panel{display:flex;align-items:center;justify-content:center;padding:45px 45px 40px;background:#fff}
.form-inner{max-width:388px;width:100%}
.mobile-brand{display:none}
.form-heading{margin-bottom:34px}
.overline{font-size:11px;letter-spacing:1.8px;color:#168e75;font-weight:800}
.form-heading h2{font-size:32px;font-weight:800;letter-spacing:-.7px;color:#102f43;margin:9px 0}
.form-heading p{font-size:13px;color:#718196;line-height:1.55}
.notice{padding:11px 13px;border:1px solid #b7e4c9;background:#f0fff5;color:#176039;border-radius:8px;font-size:12px;margin-bottom:20px}
.login-form{display:flex;flex-direction:column;gap:20px}
.field label{font-size:12px;font-weight:750;color:#31465c;display:block;margin-bottom:9px}
.label-row{display:flex;align-items:center;justify-content:space-between;gap:10px}
.label-row label{margin-bottom:9px}
.forgot{font-size:11px;color:#0784ad;font-weight:650;text-decoration:none;margin-bottom:9px}
.forgot:hover{text-decoration:underline}
.input-wrap{display:flex;align-items:center;gap:10px;min-height:48px;border:1px solid #d6e1eb;border-radius:9px;padding:0 13px;background:#f9fbfd;transition:box-shadow .15s,border-color .15s}
.input-wrap:focus-within{border-color:#168baa;box-shadow:0 0 0 3px #168baa18;background:white}
.input-wrap.invalid{border-color:#db5959}
.field-icon{color:#7a96a7;flex-shrink:0}
.input-wrap input{flex:1;min-width:0;background:transparent;border:0;outline:0;font-size:13px;color:#123348;height:45px}
.input-wrap input::placeholder{color:#9aaabc}
.reveal{border:0;background:none;display:flex;align-items:center;justify-content:center;color:#8395a6;padding:4px;cursor:pointer}
.reveal:hover{color:#0f698b}
.remember{display:flex;align-items:center;gap:9px;color:#53677b;font-size:12px;cursor:pointer}
.remember input{height:15px;width:15px;accent-color:#0787ab}
.submit-button{height:49px;border:0;border-radius:9px;background:linear-gradient(110deg,#067f9b,#076c84);color:#fff;font-size:12px;font-weight:800;letter-spacing:.4px;display:flex;align-items:center;justify-content:center;gap:10px;cursor:pointer;box-shadow:0 7px 17px #08748c26;transition:filter .15s,transform .15s}
.submit-button:hover{filter:brightness(1.07)}
.submit-button:active{transform:translateY(1px)}
.submit-button:disabled{opacity:.6;cursor:not-allowed}
.access-note{border-top:1px solid #e8eef2;margin-top:32px;padding-top:21px;display:flex;align-items:center;justify-content:center;gap:8px;font-size:11px;color:#75899b;text-align:center}
.access-note svg{color:#249377;flex-shrink:0}
@media(max-width:780px){.login-page{padding:17px}.login-shell{grid-template-columns:1fr;width:min(480px,100%);min-height:auto}.brand-panel{display:none}.form-panel{padding:35px 30px 30px}.mobile-brand{display:flex;align-items:center;gap:10px;margin-bottom:30px;font-size:20px;font-weight:800;color:#0a5266}.mobile-brand img{width:48px;height:48px;object-fit:contain}.form-heading h2{font-size:28px}}
@media(max-width:420px){.login-page{padding:0;background:white}.login-shell{min-height:100dvh;border-radius:0;box-shadow:none}.form-panel{padding:28px 23px}.form-heading{margin-bottom:28px}}
</style>
