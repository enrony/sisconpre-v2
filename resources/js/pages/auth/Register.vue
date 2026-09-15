<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Crea una cuenta',
        description: 'Ingresa tus datos para crear tu cuenta',
    },
});
</script>

<template>
    <Head title="Registro" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="codigo_grupo_trabajo"
                    >Código de grupo de trabajo</Label
                >
                <Input
                    id="codigo_grupo_trabajo"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="off"
                    name="codigo_grupo_trabajo"
                    placeholder="Ej: 7K3PQ2"
                    class="uppercase placeholder:normal-case"
                />
                <p class="text-muted-foreground text-xs">
                    Pedíselo a tu supervisor o a alguien de tu equipo — te une a
                    su grupo de trabajo.
                </p>
                <InputError :message="errors.codigo_grupo_trabajo" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Nombre</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    :tabindex="2"
                    autocomplete="name"
                    name="name"
                    placeholder="Nombre completo"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Correo electrónico</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="3"
                    autocomplete="email"
                    name="email"
                    placeholder="correo@ejemplo.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Contraseña</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Contraseña"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirmar contraseña</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="5"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirmar contraseña"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="6"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Crear cuenta
            </Button>
        </div>

        <div class="text-muted-foreground text-center text-sm">
            ¿Ya tienes una cuenta?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="7"
                >Inicia sesión</TextLink
            >
        </div>
    </Form>
</template>
