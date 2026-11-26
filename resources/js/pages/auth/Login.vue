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
                <div class="brand-identity">
                    <img src="/logo-sistema.svg" alt="Logotipo original de SistemaSnack" class="brand-logo" />
                    <h1>Sistema<span>Snack</span></h1>
                    <p>Sistema de ventas</p>
                </div>
                <div class="wave wave-back" aria-hidden="true"></div>
                <div class="wave wave-front" aria-hidden="true"></div>
            </aside>

            <section class="form-panel">
                <div class="form-inner">
                    <div class="mobile-brand">
                        <img src="/logo-sistema.svg" alt="" />
                        <span>SistemaSnack</span>
                    </div>
                    <div class="form-heading">
                        <h2>Iniciar sesión</h2>
                        <p>Accede a tu cuenta para continuar</p>
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
                            <span>{{ form.processing ? 'Ingresando...' : 'Iniciar sesión' }}</span>
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
.login-page{min-height:100dvh;display:flex;align-items:center;justify-content:center;padding:22px;background:#eef4f6;color:#15354b}
.login-shell{width:min(1050px,100%);min-height:590px;display:grid;grid-template-columns:1.04fr 1fr;overflow:hidden;border-radius:16px;background:#063d55;box-shadow:0 22px 65px #05283a24}
.brand-panel{position:relative;display:flex;align-items:center;justify-content:center;min-width:0;overflow:hidden;background:radial-gradient(circle at 85% 10%,#126681 0%,transparent 42%),linear-gradient(145deg,#04374e,#07516d 75%,#074259)}
.brand-identity{position:relative;z-index:2;display:flex;flex-direction:column;align-items:center;margin-bottom:65px;text-align:center;color:#fff}
.brand-logo{display:block;width:clamp(138px,16vw,196px);height:clamp(138px,16vw,196px);object-fit:contain;filter:drop-shadow(0 9px 18px #001c2f2e)}
.brand-identity h1{margin-top:14px;font-size:clamp(30px,3.3vw,40px);font-weight:850;letter-spacing:-1.3px;line-height:1.1;white-space:nowrap}
.brand-identity h1 span{color:#4cdb76}
.brand-identity p{margin-top:9px;font-size:14px;color:#e3f1f4}
.wave{position:absolute;pointer-events:none;left:-24%;width:150%;height:220px;bottom:-160px;border-radius:50% 60% 0 0 / 60% 75% 0 0;transform:rotate(-5deg)}
.wave-back{background:#1bac63;bottom:-125px;transform:rotate(10deg);opacity:.92}
.wave-front{background:#43cb5b;bottom:-168px;transform:rotate(-9deg)}
.form-panel{display:flex;align-items:center;justify-content:center;padding:44px 42px;background:#fff}
.form-inner{width:100%;max-width:364px}
.mobile-brand{display:none}
.form-heading{margin-bottom:30px;text-align:center}
.form-heading h2{color:#0c3048;font-size:29px;font-weight:800;letter-spacing:-.7px;line-height:1.2}
.form-heading p{margin-top:10px;font-size:13px;color:#637b8c}
.notice{border:1px solid #b7e4c9;border-radius:7px;background:#f0fff5;color:#176039;padding:10px 12px;font-size:12px;margin-bottom:15px}
.login-form{display:flex;flex-direction:column;gap:16px}
.field label{display:block;font-size:12px;color:#263d52;font-weight:700;margin-bottom:7px}
.label-row{display:flex;align-items:center;justify-content:space-between;gap:8px}
.forgot{font-size:11px;color:#087aa9;text-decoration:none;margin-bottom:7px}
.forgot:hover{text-decoration:underline}
.input-wrap{display:flex;align-items:center;gap:9px;border:1px solid #cedbe6;border-radius:8px;background:#fff;min-height:46px;padding:0 12px;transition:border-color .15s,box-shadow .15s}
.input-wrap:focus-within{border-color:#1285a8;box-shadow:0 0 0 3px #1285a81c}
.input-wrap.invalid{border-color:#db5959}
.field-icon{flex-shrink:0;color:#365c72}
.input-wrap input{width:100%;min-width:0;flex:1;height:44px;background:transparent;border:0;outline:none;font-size:13px;color:#18374a}
.input-wrap input::placeholder{color:#8399a9}
.reveal{padding:5px;color:#637f92;border:0;background:none;cursor:pointer;display:flex;align-items:center;justify-content:center}
.reveal:hover{color:#0d617b}
.remember{display:flex;align-items:center;gap:8px;font-size:12px;color:#4c6576;cursor:pointer}
.remember input{width:15px;height:15px;accent-color:#0fa653}
.submit-button{display:flex;align-items:center;justify-content:center;gap:10px;margin-top:3px;min-height:46px;width:100%;border:0;border-radius:7px;background:linear-gradient(95deg,#0ea74f,#11b765);box-shadow:0 7px 17px #16a24b23;color:#fff;font-size:14px;font-weight:800;cursor:pointer}
.submit-button:hover{filter:brightness(1.05)}
.submit-button:disabled{opacity:.6;cursor:not-allowed}
.access-note{margin-top:23px;border-top:1px solid #e6edf2;padding-top:16px;display:flex;align-items:center;justify-content:center;gap:7px;color:#75899b;text-align:center;font-size:11px}
.access-note svg{color:#24a372;flex-shrink:0}
@media(max-width:780px){.login-page{padding:15px}.login-shell{grid-template-columns:1fr;width:min(460px,100%);min-height:auto;background:white}.brand-panel{display:none}.form-panel{padding:34px 27px}.mobile-brand{display:flex;align-items:center;justify-content:center;gap:9px;margin-bottom:23px;color:#073d55;font-size:20px;font-weight:800}.mobile-brand img{width:49px;height:49px;object-fit:contain}.form-heading h2{font-size:26px}}
@media(max-width:420px){.login-page{padding:0;background:white}.login-shell{min-height:100dvh;border-radius:0;box-shadow:none}.form-panel{padding:28px 22px}}
</style>